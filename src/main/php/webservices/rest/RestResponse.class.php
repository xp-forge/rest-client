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
   * @param  web.rest.Reader $reader
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
   * Returns links sent by server.
   *
   * @return webservices.rest.Links
   */
  public function links() {
    return Links::in($this->header('Link'));
  }

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
   * Returns a value from the response, using the given type for deserialization
   *
   * @param  string $type
   * @return var
   */
  public function value($type= 'var') {
    return $this->reader->read($type);
  }

  /**
   * Returns a format instance representing the data format in this response.
   *
   * @return webservices.rest.format.Format
   */
  public function format() { return $this->reader->format(); }

  /**
   * Returns the response as a stream
   *
   * @return io.stream.InputStream
   */
  public function stream() { return $this->reader->stream(); }

  /**
   * Returns the response as a string
   *
   * @return string
   */
  public function content() {
    $s= $this->reader->stream();
    try {
      $r= '';
      while ($s->available()) {
        $r.= $s->read();
      }
      return $r;
    } finally {
      $s->close();
    }
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