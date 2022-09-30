<?php namespace webservices\rest;

use lang\ElementNotFoundException;
use util\URI;

/**
 * A REST resource, consisting of path at a given endpoint; which can
 * be accessed via HTTP methods, headers, parameters and payloads.
 *
 * @test  xp://web.rest.unittest.RestResourceTest
 * @see    https://github.com/xp-forge/rest-client/pull/17
 */
class RestResource {
  private $endpoint, $target;
  private $headers= [], $cookies= [];

  /**
   * Creates a new REST resource
   *
   * @param  web.rest.Endpoint $endpoint
   * @param  string $path
   * @param  [:string] $segments
   */
  public function __construct(Endpoint $endpoint, $path, $segments= []) {
    $this->endpoint= $endpoint;
    $this->target= $this->resolve($path, $segments, true);
  }

  /**
   * Resolves segments in resource
   *
   * @param  string $resource
   * @param  [:string] $segments
   * @param  bool $encode
   * @return string
   */
  private function resolve($resource, $segments, $encode) {
    $l= strlen($resource);
    $target= '';
    $offset= 0;
    do {
      $b= strcspn($resource, '{', $offset);
      $target.= substr($resource, $offset, $b);
      $offset+= $b;
      if ($offset >= $l) break;

      $e= strcspn($resource, '}', $offset);
      $name= substr($resource, $offset + 1, $e - 1);
      if (!isset($segments[$name])) {
        throw new ElementNotFoundException('No such segment "'.$name.'"');
      }

      $segment= $segments[$name];
      $target.= $encode ? rawurlencode($segment) : $segment;
      $offset+= $e + 1;
    } while ($offset < $l);

    return $target;
  }

  /** Returns target URI */
  public function uri(): URI {
    return $this->endpoint->base()->resolve($this->target);
  }

  /**
   * Adds headers
   *
   * @param  [:string] $headers
   * @return self
   */
  public function with($headers) {
    foreach ($headers as $name => $value) {
      $this->headers[$name]= $value;
    }
    return $this;
  }

  /**
   * Sets the media type to use as the "Content-Type" header
   *
   * @param  string $type
   * @return self
   */
  public function sending($type) {
    $this->headers['Content-Type']= $type;
    return $this;
  }

  /**
   * Adds a media type to the "Accept" header
   *
   * @param  string $type
   * @param  ?float $q Optional q-value
   * @return self
   */
  public function accepting($type, $q= null) {
    $this->headers['Accept'][]= $type.(null === $q ? '' : ';q='.$q);
    return $this;
  }

  /**
   * Includes given cookies. Encodes value using URL encoding.
   *
   * @param  [:?string]|webservices.rest.Cookie[]|webservices.rest.Cookies $cookies
   * @return self
   */
  public function including($cookies) {
    $this->cookies= $cookies;
    return $this;
  }

  /**
   * Returns a request to this resource without sending it
   *
   * @param  string $method
   * @return webservices.rest.RestRequest
   */
  public function request($method) {
    return new RestRequest($method, $this->target, $this->headers, $this->cookies);
  }

  /**
   * Starts an upload
   *
   * @param  string $method
   * @return webservices.rest.RestUpload
   */
  public function upload($method= 'POST') {
    return new RestUpload($this->endpoint, $this->request($method));
  }

  /**
   * Executes a `GET` request
   *
   * @param  [:var] $parameters
   * @return webservices.rest.RestResponse
   */
  public function get($parameters= []) {
    return $this->endpoint->execute($this->request('GET')->passing($parameters));
  }

  /**
   * Executes a `HEAD` request
   *
   * @param  [:var] $parameters
   * @return webservices.rest.RestResponse
   */
  public function head($parameters= []) {
    return $this->endpoint->execute($this->request('HEAD')->passing($parameters));
  }

  /**
   * Executes a `DELETE` request
   *
   * @param  [:var] $parameters
   * @return webservices.rest.RestResponse
   */
  public function delete($parameters= []) {
    return $this->endpoint->execute($this->request('DELETE')->passing($parameters));
  }

  /**
   * Executes a `POST` request with a given payload
   *
   * @param  var $paylod
   * @param  string $type
   * @return webservices.rest.RestResponse
   */
  public function post($payload, $type= 'application/x-www-form-urlencoded') {
    return $this->endpoint->execute($this->request('POST')->with(['Content-Type' => $type])->transfer($payload));
  }

  /**
   * Executes a `PUT` request with a given payload
   *
   * @param  var $paylod
   * @param  string $type
   * @return webservices.rest.RestResponse
   */
  public function put($payload, $type= 'application/x-www-form-urlencoded') {
    return $this->endpoint->execute($this->request('PUT')->with(['Content-Type' => $type])->transfer($payload));
  }

  /**
   * Executes a `PATCH` request with a given payload
   *
   * @param  var $paylod
   * @param  string $type
   * @return webservices.rest.RestResponse
   */
  public function patch($payload, $type= 'application/x-www-form-urlencoded') {
    return $this->endpoint->execute($this->request('PATCH')->with(['Content-Type' => $type])->transfer($payload));
  }
}