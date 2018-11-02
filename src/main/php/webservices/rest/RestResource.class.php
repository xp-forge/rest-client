<?php namespace webservices\rest;

use lang\ElementNotFoundException;

/**
 * A REST resource, consisting of path at a given endpoint; which can
 * be accessed via HTTP methods, headers, parameters and payloads.
 *
 * @test  xp://web.rest.unittest.RestResourceTest
 */
class RestResource {
  private $endpoint, $request;
  private $headers= [];

  /**
   * Creates a new REST resource
   *
   * @param  web.rest.Endpoint $endpoint
   * @param  string $path
   * @param  [:string] $segments
   */
  public function __construct(Endpoint $endpoint, $path, $segments= []) {
    $this->endpoint= $endpoint;
    $this->request= new RestRequest('GET', $this->resolve($path, $segments, true));
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
   * Passes given cookies. Encodes value using URL encoding.
   *
   * @param  [:?string]|webservices.rest.Cookie[]|webservices.rest.Cookies $cookies
   * @return self
   */
  public function passing($cookies) {
    $header= '';
    foreach ($cookies as $name => $cookie) {
      if ($cookie instanceof Cookie) {
        $name= $cookie->name();
        $value= $cookie->value();
      } else {
        $value= $cookie;
      }

      null === $value || $header.= ', '.$name.'='.urlencode($value);
    }
    $this->headers['Cookie'][]= substr($header, 2);
    return $this;
  }

  public function get($parameters= []) {
    $request= clone $this->request;
    return $this->endpoint->execute($request->using('GET')
      ->with($this->headers)
      ->passing($parameters)
    );
  }

  public function head($parameters= []) {
    $request= clone $this->request;
    return $this->endpoint->execute($request->using('HEAD')
      ->with($this->headers)
      ->passing($parameters)
    );
  }

  public function delete($parameters= []) {
    $request= clone $this->request;
    return $this->endpoint->execute($request->using('DELETE')
      ->with($this->headers)
      ->passing($parameters)
    );
  }

  public function post($payload, $type= 'application/x-www-form-urlencoded') {
    $request= clone $this->request;
    return $this->endpoint->execute($request->using('POST')
      ->with($this->headers + ['Content-Type' => $type])
      ->transfer($payload)
    );
  }

  public function put($payload, $type= 'application/x-www-form-urlencoded') {
    $request= clone $this->request;
    return $this->endpoint->execute($request->using('PUT')
      ->with($this->headers + ['Content-Type' => $type])
      ->transfer($payload)
    );
  }

  public function patch($payload, $type= 'application/x-www-form-urlencoded') {
    $request= clone $this->request;
    return $this->endpoint->execute($request->using('PATCH')
      ->with($this->headers + ['Content-Type' => $type])
      ->transfer($payload)
    );
  }
}