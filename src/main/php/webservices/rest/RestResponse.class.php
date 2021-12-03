<?php namespace webservices\rest;

use lang\Value;
use util\URI;
use webservices\rest\format\Unsupported;
use webservices\rest\io\Reader;

/**
 * REST response
 *
 * @test  xp://web.rest.unittest.RestResponseTest
 */
class RestResponse implements Value {
  use Headers;

  private $status, $message, $reader, $uri;

  /**
   * Creates a new REST response
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string|string[]] $headers
   * @param  webservices.rest.io.Reader $reader
   * @param  string|?util.URI $uri The request URI, if available
   */
  public function __construct($status, $message, $headers= [], Reader $reader= null, $uri= null) {
    $this->status= $status;
    $this->message= $message;
    $this->reader= $reader;

    if (null === $uri) {
      $this->uri= null;
    } else if ($uri instanceof URI) {
      $this->uri= $uri;
    } else {
      $this->uri= new URI($uri);
    }

    $this->add($headers);
  }

  /** @return int */
  public function status() { return $this->status; }

  /** @return string */
  public function message() { return $this->message; }

  /** @return ?util.URI */
  public function uri() { return $this->uri; }

  /** @return webservices.rest.io.Reader */
  public function reader() { return $this->reader; }

  /** @return io.stream.InputStream */
  public function stream() { return $this->reader->stream(); }

  /** @return string */
  public function content() { return $this->reader->content(); }

  /**
   * Returns cookies sent by server.
   *
   * @return webservices.rest.Cookies
   */
  public function cookies() {
    return isset($this->lookup['set-cookie'])
      ? Cookies::parse($this->headers[$this->lookup['set-cookie']], $this->uri)
      : Cookies::$EMPTY
    ;
  }

  /**
   * Resolves a URI
   *
   * @param  ?string|util.URI $uri
   * @return ?util.URI
   */
  public function resolve($uri) {
    if ($this->uri) {
      return null === $uri ? $this->uri : $this->uri->resolve($uri);
    } else {
      return null === $uri ? null : new URI((string)$uri);
    }
  }

  /**
   * Returns the value of the "Location" header, or NULL if it not present.
   * The URI is resolved against the request URI.
   *
   * @return ?util.URI
   */
  public function location() {
    if ($location= $this->header('Location')) {
      return $this->uri ? $this->uri->resolve($location) : new URI($location);
    }
    return null;
  }

  /**
   * Returns links sent by server. Returns an empty array if no `Link`
   * header is present. Throws an exception if the HTTP statuscode not
   * in the 200-299 range.
   *
   * @return [:util.URI]
   * @throws webservices.rest.UnexpectedStatus
   */
  public function links() {
    if ($this->status < 200 || $this->status >= 300) throw new UnexpectedStatus($this);

    $r= [];
    foreach (Links::in($this->header('Link'))->all() as $link) {
      $r[$link->param('rel')]= $this->resolve($link->uri());
    }
    return $r;
  }

  /**
   * Returns link URI with a given `rel` attribute or NULL if the given
   * link is not present (or no `Link` header is present at all). Throws
   * an exception if the HTTP statuscode not in the 200-299 range.
   *
   * @param  string $rel
   * @return ?util.URI
   * @throws webservices.rest.UnexpectedStatus
   */
  public function link($rel) {
    if ($this->status < 200 || $this->status >= 300) throw new UnexpectedStatus($this);

    if ($uri= Links::in($this->header('Link'))->uri(['rel' => $rel])) {
      return $this->resolve($uri);
    }
    return null;
  }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Throws an exception if the HTTP statuscode not in the 200-299 range.
   *
   * @param  ?string $type
   * @return var
   * @throws webservices.rest.UnexpectedStatus
   */
  public function value($type= null) {
    if ($this->status >= 200 && $this->status < 300) return $this->reader->read($type);

    throw new UnexpectedStatus($this);
  }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Returns NULL for a given list of status codes indicating absence, defaulting
   * to 404s. Throws an exception if the HTTP statuscode not in the 200-299 range.
   *
   * @param  ?string $type
   * @param  int[] $absent Status code indicating absence
   * @return var
   * @throws webservices.rest.UnexpectedStatus
   */
  public function optional($type= null, $absent= [404]) {
    if ($this->status >= 200 && $this->status < 300) return $this->reader->read($type);
    if (in_array($this->status, $absent)) return null;

    throw new UnexpectedStatus($this);
  }

  /**
   * Returns the error from the response, using the given type for deserialization.
   * Falls back to using the complete body as a string if the response format is
   * unsupported.
   *
   * @param  ?string $type
   * @return var
   */
  public function error($type= null) {
    if ($this->status < 400) return null;

    return $this->reader->format() instanceof Unsupported ? $this->reader->content() : $this->reader->read($type);
  }

  /**
   * Matches response status codes and returns values based on the given cases.
   * A case is an integer status code mapped to either a given value or a
   * function which receives this result instance and returns a value. Throws
   * an exception if the HTTP statuscode is unhandled.
   *
   * @param  [:var] $cases
   * @return var
   * @throws webservices.rest.UnexpectedStatus
   */
  public function match(array $cases) {
    if (array_key_exists($this->status, $cases)) return $cases[$this->status] instanceof \Closure
      ? $cases[$this->status]($this)
      : $cases[$this->status]
    ;

    throw new UnexpectedStatus($this);
  }

  /**
   * Returns an instance representing the data in this response.
   *
   * @deprecated Directly use this class!
   * @return webservices.rest.RestResponse
   */
  public function result() {
    trigger_error('Use response directly!', E_USER_DEPRECATED);
    return $this;
  }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /** @return string */
  public function toString() {
    $s= nameof($this).'('.$this->status.' '.$this->message.")@{\n";
    foreach ($this->headers as $name => $value) {
      $s.= '  '.$name.': '.implode(', ', $value)."\n";
    }
    return $s.'}';
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value === $this ? 0 : 1;
  }
}