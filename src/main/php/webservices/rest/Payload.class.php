<?php namespace webservices\rest;

class Payload {
  private $value;

  /** @param var $value */
  public function __construct($value) {
    $this->value= $value;
  }

  /** @return var */
  public function value() { return $this->value; }
}