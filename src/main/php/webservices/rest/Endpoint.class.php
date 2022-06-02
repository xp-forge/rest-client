<?php namespace webservices\rest;

use Traversable;
use io\streams\Compression;
use io\streams\compress\Algorithm;
use lang\{Throwable, IllegalArgumentException};
use peer\URL;
use peer\http\{HttpConnection, HttpRequest};
use util\data\Marshalling;
use util\log\Traceable;
use util\{URI, Authority};
use webservices\rest\io\{Buffered, Reader, Streamed, Traced, Transmission};

/**
 * Entry point class
 *
 * @test  webservices.rest.unittest.EndpointTest
 * @test  webservices.rest.unittest.ExecuteTest
 */
class Endpoint implements Traceable {
  protected $base, $formats, $transfer, $marshalling, $connections;
  private $headers= [];
  private $cat= null;

  /**
   * Creates a new REST endpoint with a given base URI. The base URI may contain
   * credentials and a path, which is treated as a directory.
   *
   * Examples:
   * - Inside the root directory: `https://api.example.org/`
   * - Including path: `https://example.org/api/v1`
   * - With basic authentication: `https://user:pass@example.org/api/v1`
   * - Passing a bearer token: `https://token@example.org/api/v1`
   * 
   * @param  string|util.URI|peer.URL $base
   * @param  ?webservices.rest.Formats $formats
   * @param  ?string|io.streams.compress.Algorithm|string[]|io.streams.compress.Algorithm[]|iterable $compressing
   * @throws lang.IllegalArgumentException if URI does not have an authority
   */
  public function __construct($base, Formats $formats= null, $compressing= null) {
    if ($base instanceof URI) {
      $uri= $base;
    } else if ($base instanceof URL) {
      $uri= new URI($base->getURL());
    } else {
      $uri= new URI((string)$base);
    }

    if (null === ($authority= $uri->authority())) {
      throw new IllegalArgumentException('Given URI does not have an authority');
    }

    // Extract credentials embedded in authority into authorization header
    if (($user= $authority->user()) && ($password= $authority->password())) {
      $this->headers['Authorization']= 'Basic '.base64_encode($user.':'.$password->reveal());
    } else if ($user) {
      $this->headers['Authorization']= 'Bearer '.$user;
    }

    $this->base= $uri->using()
      ->authority(new Authority($authority->host(), $authority->port()))
      ->path(rtrim($uri->path() ?? '', '/').'/')
      ->create()
    ;

    $this->formats= $formats ?: Formats::defaults();
    $this->transfer= new Streamed($this);
    $this->marshalling= new Marshalling();
    $this->connections= function($uri) { return new HttpConnection($uri); };
    $this->compressing($compressing ?? Compression::algorithms()->supported());
  }

  /**
   * Use buffering for sending requests; ensuring they have a "Content-Length"
   * header. This will be slower, especially for big requests, but is more
   * likely to work with all webservers.
   *
   * @see    https://bz.apache.org/bugzilla/show_bug.cgi?id=53332
   * @see    https://stackoverflow.com/q/66899385
   * @return self
   */
  public function buffered() {
    $this->transfer= new Buffered($this);
    return $this;
  }

  /**
   * Signal support compression algorithms in the "Accept-Encoding" header.
   * Passing NULL will remove this header, indicating no specific preference.
   *
   * @param  ?string|io.streams.compress.Algorithm|string[]|io.streams.compress.Algorithm[]|iterable $arg
   * @return self
   */
  public function compressing($arg) {
    if (null === $arg) {
      $it= [];
    } else if ($arg instanceof Traversable || is_array($arg)) {
      $it= $arg;
    } else {
      $it= [$arg];
    }

    $supported= [];
    foreach ($it as $algorithm) {
      if ($algorithm instanceof Algorithm) {
        $supported[]= $algorithm->token();
      } else {
        $supported[]= (string)$algorithm;
      }
    }

    if (empty($supported)) {
      unset($this->headers['Accept-Encoding']);
    } else {
      $this->headers['Accept-Encoding']= implode(', ', $supported);
    }
    return $this;
  }

  /**
   * Specify a connection function, which gets passed a URI and returns a
   * `HttpConnection` instance.
   *
   * @param  function(var): peer.http.HttpConnection $connections
   * @return self
   */
  public function connecting($connections) {
    $this->connections= cast($connections, 'function(var): peer.http.HttpConnection');
    return $this;
  }

  /** @return util.URI */
  public function base() { return $this->base; }

  /** @return [:string] */
  public function headers() { return $this->headers; }

  /**
   * Adds headers to be sent with every request
   *
   * @param  string|[:string] $arg
   * @param  ?string $value
   * @return self
   */
  public function with($arg, $value= null) {
    if (is_array($arg)) {
      $this->headers= array_merge($this->headers, $arg);
    } else {
      $this->headers[$arg]= $value;
    }
    return $this;
  }

  /**
   * Returns a REST resource
   *
   * @param  string $path
   * @param  [:string] $segments
   * @return webservices.rest.RestResource
   */
  public function resource($path, $segments= []) {
    return new RestResource($this, $path, $segments);
  }

  /**
   * Sets a log category for debugging
   *
   * @param  ?util.log.LogCategory $cat
   * @return void
   */
  public function setTrace($cat) {
    if (null === $cat) {
      $this->transfer= $this->transfer->untraced();
    } else if ($this->transfer instanceof Traced) {
      $this->transfer->use($cat);
    } else {
      $this->transfer= new Traced($this->transfer, $cat);
    }
  }

  /**
   * Opens a request and returns a transmission instance
   * 
   * @param  webservices.rest.RestRequest $request
   * @return webservices.rest.io.Transmission
   */
  public function open(RestRequest $request) {
    $target= $this->base->resolve($request->path());
    $conn= $this->connections->__invoke($target);

    // Use request timeouts if supplied, otherwise use those of the connection
    $timeouts= $request->timeouts();
    isset($timeouts[0]) && $conn->setTimeout($timeouts[0]);
    isset($timeouts[1]) && $conn->setConnectTimeout($timeouts[1]);

    // RFC 6265: When the user agent generates an HTTP request, the user agent
    // MUST NOT attach more than one Cookie header field.
    $headers= array_merge($this->headers, $request->headers());
    $cookies= (array)$request->header('Cookie');
    foreach ($request->cookies()->validFor($target) as $cookie) {
      $cookies[]= $cookie->name().'='.urlencode($cookie->value());
    }
    $cookies && $headers['Cookie']= implode('; ', $cookies);

    $s= $conn->create(new HttpRequest());
    $s->setMethod($request->method());
    $s->setTarget($target->path());
    $s->addHeaders($headers);
    $s->setParameters($request->parameters());
    return $this->transfer->transmission($conn, $s, $target);
  }

  /**
   * Finished a given transmission and returns the response
   * 
   * @param  webservices.rest.io.Transmission $transmission
   * @return webservices.rest.RestResponse
   * @throws webservices.rest.RestException
   */
  public function finish(Transmission $transmission) {
    try {
      $r= $transmission->finish();
      $output= $this->formats->named($r->header('Content-Type')[0] ?? null);
      $reader= $this->transfer->reader($r, $output, $this->marshalling);
      return new RestResponse($r->statusCode(), $r->message(), $r->headers(), $reader, $transmission->target);
    } catch (Throwable $e) {
      throw new RestException('Cannot send request', $e);
    }
  }

  /**
   * Sends a request and returns the response
   *
   * @param  webservices.rest.RestRequest $request
   * @return webservices.rest.RestResponse
   * @throws webservices.rest.RestException
   */
  public function execute(RestRequest $request) {
    $input= $this->formats->named($request->header('Content-Type'));
    return $this->transfer->writer($request, $input, $this->marshalling);
  }
}