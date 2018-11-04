<?php namespace webservices\rest;

use peer\URL;
use peer\http\HttpConnection;
use peer\http\HttpRequest;
use util\URI;
use util\data\Marshalling;
use webservices\rest\io\Buffered;
use webservices\rest\io\Reader;
use webservices\rest\io\Streamed;

/**
 * Entry point class
 *
 * @test  xp://web.rest.unittest.EndpointTest
 */
class Endpoint {
  private $base, $formats, $transfer, $marshalling;

  /**
   * Creates a new REST endpoint with a given base URI. The base URI may contain
   * basic auth credentials, and a path, which is treated as a directory.
   * 
   * @param  string|util.URI|peer.URL $base
   * @param  ?web.rest.Formats $formats
   */
  public function __construct($base, Formats $formats= null) {
    if ($base instanceof URI) {
      $uri= $base;
    } else if ($base instanceof URL) {
      $uri= new URI($base->getURL());
    } else {
      $uri= new URI($base);
    }

    $this->base= $uri->using()->path(rtrim($uri->path(), '/').'/')->create();
    $this->formats= $formats ?: Formats::defaults();
    $this->transfer= new Streamed();
    $this->marshalling= new Marshalling();
    $this->connections= function($uri) { return new HttpConnection($uri); };
  }

  /**
   * Use buffering for sending requests; ensuring they have a "Content-Length"
   * header. This will be slower, especially for big requests, but is more
   * likely to work with all webservers.
   *
   * @see    https://bz.apache.org/bugzilla/show_bug.cgi?id=53332
   * @return self
   */
  public function buffered() {
    $this->transfer= new Buffered();
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

  /**
   * Returns a REST resource
   *
   * @param  string $path
   * @param  [:string] $segments
   * @return web.rest.RestResource
   */
  public function resource($path, $segments= []) {
    return new RestResource($this, $path, $segments);
  }

  /**
   * Sends a request and returns the response
   *
   * @param  web.rest.RestRequest $request
   * @return web.rest.RestResponse
   * @throws web.rest.RestException
   */
  public function execute(RestRequest $request) {
    $uri= $this->base->resolve($request->path());
    $conn= $this->connections->__invoke($uri);
    $headers= $request->headers();

    // RFC 6265: When the user agent generates an HTTP request, the user agent
    // MUST NOT attach more than one Cookie header field.
    $cookies= (array)$request->header('Cookie');
    foreach ($request->cookies()->validFor($uri) as $cookie) {
      $cookies[]= $cookie->name().'='.urlencode($cookie->value());
    }
    $cookies && $headers['Cookie']= implode('; ', $cookies);

    $s= $conn->create(new HttpRequest());
    $s->setMethod($request->method());
    $s->setTarget($uri->path());
    $s->addHeaders($headers);
    $s->setParameters($request->parameters());

    try {
      if ($payload= $request->payload()) {
        $input= $this->formats->named($request->header('Content-Type'));
        $writer= $this->transfer->writer($s, $payload->value(), $input, $this->marshalling);
        $stream= $writer($conn->open($s));
      } else {
        $stream= $conn->open($s);
      }

      $r= $conn->finish($stream);
      $output= $this->formats->named(current($r->header('Content-Type')));
      $reader= new Reader($r->in(), $output, $this->marshalling);
      return new RestResponse($r->statusCode(), $r->message(), $r->headers(), $reader, $uri);
    } catch (Throwable $e) {
      throw new RestException('Cannot send request', $e);
    }
  }
}