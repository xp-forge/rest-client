<?php namespace webservices\rest;

/**
 * REST request
 *
 * @test  xp://webservices.rest.unittest.RestRequestTest
 */
class RestRequest {
  use Headers;

  private $method, $path, $cookies;
  private $parameters= [];
  private $payload= null;

  /**
   * Creates a new REST request
   *
   * @param  string $method GET HEAD POST ...
   * @param  string $path
   * @param  [:string] $headers
   */
  public function __construct($method, $path, $headers= []) {
    $this->method= $method;
    $this->path= $path;
    $this->add($headers);
    $this->cookies= Cookies::$EMPTY;
  }

  /** @return string */
  public function method() { return $this->method; }

  /** @return string */
  public function path() { return $this->path; }

  /** @return [:string] */
  public function parameters() { return $this->parameters; }

  /** @return webservices.rest.Cookies */
  public function cookies() { return $this->cookies; }

  /** @return webservices.rest.Payload */
  public function payload() { return $this->payload; }

  /**
   * Uses a given HTTP method
   *
   * @param  string $method GET HEAD POST ...
   * @return self
   */
  public function using($method) {
    $this->method= $method;
    return $this;
  }

  /**
   * Adds given headers
   *
   * @param  [:string] $headers
   * @return self
   */
  public function with($headers) {
    $this->add($headers);
    return $this;
  }

  /**
   * Includes given cookies
   *
   * @param  [:?string]|webservices.rest.Cookie[]|webservices.rest.Cookies $cookies
   * @return self
   */
  public function including($cookies) {
    $this->cookies= $cookies instanceof Cookies ? $cookies : new Cookies($cookies);
    return $this;
  }

  /**
   * Passes given parameters
   *
   * @param  [:string] $parameters
   * @return self
   */
  public function passing($parameters) {
    $this->parameters= $parameters;
    return $this;
  }

  /**
   * Transfers a given payload, which is serialized according to the format
   * defined by the `Content-Type` header
   *
   * @param  var $payload
   * @return self
   */
  public function transfer($payload) {
    $this->payload= new Payload($payload);
    return $this;
  }
}