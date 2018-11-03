<?php namespace webservices\rest;

use lang\ElementNotFoundException;
use lang\Value;
use util\Date;
use util\Objects;
use util\URI;

/**
 * Cookie JAR
 *
 * @test  xp://webservices.rest.unittest.CookiesTest
 */
class Cookies implements Value, \IteratorAggregate {
  public static $EMPTY;
  private $list= [];

  static function __static() {
    self::$EMPTY= new self([]);  // @codeCoverageIgnore
  }

  /** @param webservices.rest.Cookie[]|[:?string] $cookies */
  public function __construct($cookies) {
    $this->merge($cookies);
  }

  /** @return bool */
  public function present() { return sizeof($this->list) > 0; }

  /** @return self */
  public function clear() { $this->list= []; return $this; }

  /**
   * Merges these cookies with a given list of cookies
   *
   * @param  webservices.rest.Cookie[]|[:?string] $cookies
   * @return self
   */
  public function merge($cookies) {
    foreach ($cookies as $name => $cookie) {
      if ($cookie instanceof Cookie) {
        $domain= $cookie->domain();
        $key= sprintf(
          '#^%s://%s%s/%s#',
          $cookie->secure() ? 'https': 'https?',
          0 === strncmp($domain, '.', 1) ? '.+' : '',
          preg_quote($domain),
          ltrim($cookie->path(), '/')
        );
        if (null === ($value= $cookie->value())) {
          unset($this->list[$key][$cookie->name()]);
        } else {
          $this->list[$key][$cookie->name()]= $cookie;
        }
      } else {
        $key= '#^https?://[^/]+/#';
        if (null === $cookie) {
          unset($this->list[$key][$name]);
        } else {
          $this->list[$key][$name]= new Cookie($name, $cookie);
        }
      }
    }

    // RFC 6265: The user agent SHOULD sort the cookie-list in the following order:
    // Cookies with longer paths are listed before cookies with shorter paths.
    uksort($this->list, function($a, $b) { return strlen($b) - strlen($a); });
    return $this;
  }

  /**
   * Returns all cookies
   * 
   * @return iterable
   */
  public function getIterator() {
    foreach ($this->list as $lookup) {
      foreach ($lookup as $cookie) {
        yield $cookie;
      }
    }
  }

  /**
   * Retrieves non-expired cookies for a given URI.
   *
   * @param  string|util.URI $arg
   * @param  ?util.Date $rel
   * @return iterable
   */
  public function validFor($arg, $rel= null) {
    $uri= $arg instanceof URI ? $arg : new URI($arg);
    $normalized= (string)$uri->canonicalize();
    $rel || $rel= Date::now();

    $yielded= [];
    foreach ($this->list as $key => $lookup) {
      if (preg_match($key, $normalized)) foreach ($lookup as $name => $cookie) {
        if (isset($yielded[$name])) continue;

        $expires= $cookie->expires();
        if (null === $expires || $expires->isAfter($rel)) {
          yield $cookie;
          $yielded[$name]= true;
        }
      }
    }
  }

  /** @return string */
  public function toString() {
    $s= nameof($this)."@{\n";
    foreach ($this->list as $lookup) {
      foreach ($lookup as $cookie) {
        $s.= '  '.str_replace("\n", "\n  ", $cookie->toString())."\n";
      }
    }
    return $s.'}';
  }

  /** @return string */
  public function hashCode() { return Objects::hashOf($this->list); }

  /**
   * Compares this cookie to another given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->list, $value->list) : 1;
  }
}
