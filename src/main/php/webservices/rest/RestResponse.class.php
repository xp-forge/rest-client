<?php namespace webservices\rest;

use lang\Value;
use util\URI;
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
   * @deprecated Use `self::result()->location()` or `self::header('Location')`
   * @return ?util.URI
   */
  public function location() {
    if ($location= $this->header('Location')) {
      return $this->uri ? $this->uri->resolve($location) : new URI($location);
    }
    return null;
  }

  /**
   * Returns links sent by server.
   *
   * @deprecated Use `self::result()->links()` or `self::header('Link')`
   * @return webservices.rest.Links
   */
  public function links() {
    return Links::in($this->header('Link'));
  }

  /**
   * Returns a value from the response, using the given type for deserialization
   *
   * @deprecated Use `self::result()->value()`
   * @param  string $type
   * @return var
   */
  public function value($type= 'var') {
    return $this->reader->read($type);
  }

  /**
   * Returns a result instance representing the data in this response.
   *
   * @return webservices.rest.Result
   */
  public function result() { return new Result($this); }

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