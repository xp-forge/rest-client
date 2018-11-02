<?php namespace webservices\rest;

class Cookie {
  private $name, $value;
  private $attributes= [];

  public function __construct($name, $value) {
    $this->name= $name;
    $this->value= $value;
  }

  public function with($name, $value) {
    $this->attributes[$name]= $value;
    return $this;
  }
}