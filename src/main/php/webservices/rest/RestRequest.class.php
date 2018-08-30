<?php namespace webservices\rest;

class RestRequest {
  use Headers;

  private $method, $path;
  private $parameters= [];
  private $payload= null;

  public function __construct($method, $path, $headers= []) {
    $this->method= $method;
    $this->path= $path;
    $this->add($headers);
  }

  /** @return string */
  public function method() { return $this->method; }

  /** @return string */
  public function path() { return $this->path; }

  /** @return [:string] */
  public function parameters() { return $this->parameters; }

  /** @return var */
  public function payload() { return $this->payload; }

  public function using($method) {
    $this->method= $method;
    return $this;
  }

  public function with($headers) {
    $this->add($headers);
    return $this;
  }

  public function passing($parameters) {
    $this->parameters= $parameters;
    return $this;
  }

  public function transfer($payload) {
    $this->payload= $payload;
    return $this;
  }
}