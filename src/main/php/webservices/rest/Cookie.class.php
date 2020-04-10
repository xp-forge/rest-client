<?php namespace webservices\rest;

use lang\Value;
use util\{Date, Objects};

class Cookie implements Value {
  private $name, $value, $attributes;

  /**
   * Creates a cookie
   *
   * @param  string $name
   * @param  string $value
   * @param  [:var] $attributes
   */
  public function __construct($name, $value, $attributes= []) {
    $this->name= $name;
    $this->value= $value;
    $this->attributes= $attributes;

    // If both (Expires and Max-Age) are set, Max-Age will have precedence.
    if (isset($this->attributes['Max-Age'])) {
      $this->attributes['Expires']= gmdate('D, d M Y H:i:s \G\M\T', time() + $this->attributes['Max-Age']);
    }
  }

  /** @return string */
  public function name() { return $this->name; }

  /** @return ?string */
  public function value() { return $this->value; }

  /** @return ?util.Date */
  public function expires() { return isset($this->attributes['Expires']) ? new Date($this->attributes['Expires']) : null; }

  /** @return ?int */
  public function maxAge() { return isset($this->attributes['Max-Age']) ? (int)$this->attributes['Max-Age'] : null; }

  /** @return ?string */
  public function domain() { return isset($this->attributes['Domain']) ? $this->attributes['Domain'] : null; }

  /** @return ?string */
  public function path() { return isset($this->attributes['Path']) ? $this->attributes['Path'] : null; }

  /** @return bool */
  public function httpOnly() { return isset($this->attributes['HttpOnly']); }

  /** @return bool */
  public function secure() { return isset($this->attributes['Secure']); }

  /** @return ?string */
  public function sameSite() { return isset($this->attributes['SameSite']) ? $this->attributes['SameSite'] : null; }

  /** @return string */
  public function toString() {
    return nameof($this).'('.$this->name.'='.$this->value.')@'.Objects::stringOf($this->attributes);
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf((array)$this);
  }

  /**
   * Compares this cookie to another given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare((array)$this, (array)$value) : 1;
  }
}