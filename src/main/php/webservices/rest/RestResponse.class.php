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
   * Returns cookies sent by server; rejecting cookies from invalid domains.
   * However, it does *not* take https://publicsuffix.org/list/ into account!
   *
   * @see    https://tools.ietf.org/html/rfc6265
   * @see    https://tools.ietf.org/html/draft-ietf-httpbis-rfc6265bis-02
   * @return webservices.rest.Cookies
   */
  public function cookies() {
    if (!isset($this->lookup['set-cookie'])) return Cookies::$EMPTY;

    $list= [];
    foreach ($this->headers[$this->lookup['set-cookie']] as $cookie) {
      $attr= [];
      preg_match('/([^=]+)=("([^"]+)"|([^;]+))?(;(.+))*/', $cookie, $matches);
      if (isset($matches[6])) {
        foreach (explode(';', $matches[6]) as $attribute) {
          $r= sscanf(trim($attribute), "%[^=]=%[^\r]", $name, $value);
          $attr[$name]= 2 === $r ? urldecode($value) : true;
        }
      }

      if (0 === strncmp($matches[1], '__Host-', 7)) {
        if (isset($attr['Domain']) || !isset($attr['Path']) || '/' !== $attr['Path'] || !isset($attr['Secure'])) continue;
        $name= substr($matches[1], 7);
      } else if (0 === strncmp($matches[1], '__Secure-', 9)) {
        if (!isset($attr['Secure'])) continue;
        $name= substr($matches[1], 9);
      } else {
        $name= $matches[1];
      }

      // Reject cookies if:
      // * They belong to a domain that does not include the origin server
      // * An insecure site tries to set a cookie with a "Secure" directive
      if ($this->uri && (
        (isset($attr['Domain']) && !preg_match('/^.+'.preg_quote($attr['Domain']).'$/', $this->uri->host())) ||
        (isset($attr['Secure']) && 'https' !== $this->uri->scheme())
      )) continue;

      // Normalize domain: If a domain is specified, subdomains are always included.
      // Otherwise, defaults to current host; not including subdomains.
      if (isset($attr['Domain'])) {
        $attr['Domain']= '.'.ltrim($attr['Domain'], '.');
      } else if ($this->uri) {
        $attr['Domain']= $this->uri->host();
      }

      $list[]= new Cookie($name, isset($matches[2]) ? urldecode($matches[2]) : null, $attr);
    }
    return new Cookies($list);
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