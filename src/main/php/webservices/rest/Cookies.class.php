<?php namespace webservices\rest;

use lang\ElementNotFoundException;
use lang\Value;
use util\Objects;

/**
 * Cookie JAR
 *
 * @test  xp://webservices.rest.unittest.CookiesTest
 */
class Cookies implements Value, \IteratorAggregate {
  public static $EMPTY;
  private $named= [];

  static function __static() {
    self::$EMPTY= new self([]);  // @codeCoverageIgnore
  }

  /** @param webservices.rest.Cookie[]|[:?string] $cookies */
  public function __construct($cookies) {
    $this->merge($cookies);
  }

  /** @return bool */
  public function present() { return sizeof($this->named) > 0; }

  /**
   * Checks whether a given cookie is contained in this list
   *
   * @param  string $name
   * @return bool
   */
  public function provides($name) { return isset($this->named[$name]); }

  /**
   * Returns a given named cookie
   *
   * @param  string $name
   * @return webservices.rest.Cookie
   * @throws lang.ElementNotFoundException
   */
  public function named($name) {
    if (isset($this->named[$name])) return $this->named[$name];

    throw new ElementNotFoundException('No cookie named "'.$name.'"');
  }

  /** @return self */
  public function clear() { $this->named= []; return $this; }

  /**
   * Merges these cookies with a given list of cookies
   *
   * @param  webservices.rest.Cookie[]|[:?string] $cookies
   * @return self
   */
  public function merge($cookies) {
    foreach ($cookies as $name => $cookie) {
      if ($cookie instanceof Cookie) {
        if (null === ($value= $cookie->value())) {
          unset($this->named[$cookie->name()]);
        } else {
          $this->named[$cookie->name()]= $cookie;
        }
      } else {
        if (null === $cookie) {
          unset($this->named[$name]);
        } else {
          $this->named[$name]= new Cookie($name, $cookie);
        }
      }
    }
    return $this;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->named as $name => $cookie) {
      yield $name => $cookie;
    }
  }

  /** @return string */
  public function toString() {
    return nameof($this).'@'.Objects::stringOf($this->named);
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf($this->named);
  }

  /**
   * Compares this cookie to another given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->named, $value->named) : 1;
  }
}
