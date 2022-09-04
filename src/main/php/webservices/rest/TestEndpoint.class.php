<?php namespace webservices\rest;

use webservices\rest\io\Transmission;

/**
 * Endpoint subclass used for testing REST clients. Accepts a map of routes
 * using absolute paths, optionally prefixed with an HTTP method, as such:
 *
 * ```php
 * new TestEndpoint([
 *   '/users/6100' => function($call) {
 *     return $call->respond(200, 'OK', ['Content-Type' => 'application/json'], '{
 *       "id": 6100,
 *       "username": "binford"
 *     }');
 *   },
 *   'POST /users' => function($call) {
 *     return $call->respond(201, 'Created', ['Location' => '/users/6100']);
 *   },
 * ]);
 * ```
 *
 * @see   webservices.rest.TestCall
 * @test  webservices.rest.unittest.TestEndpointTest
 */
class TestEndpoint extends Endpoint {
  private $routes= [];

  /**
   * Creates a new testing endpoint
   *
   * @param  [:function(webservices.rest.TestCall): webservices.rest.RestResponse] $routes
   * @param  string $base
   */
  public function __construct(array $routes, $base= '/') {
    parent::__construct('http://test.local/'.ltrim($base, '/'), Formats::defaults());
    foreach ($routes as $match => $handler) {
      $p= strpos($match, ' ');
      $s= preg_replace(['/\{([^:}]+):([^}]+)\}/', '/\{([^}]+)\}/'], ['(?<$1>$2)', '(?<$1>[^/]+)'], $match);
      $this->routes[false === $p ? '#.+ '.$s.'#' : '#'.$s.'#']= $handler;
    }
  }

  /**
   * Handle a call
   *
   * @param  webservices.rest.TestCall $call
   * @return webservices.rest.RestResponse
   */
  private function handle($call) {
    $request= $call->request();
    $resolved= $this->base->resolve($request->path())->path();

    $match= $request->method().' '.$resolved;
    foreach ($this->routes as $pattern => $handler) {
      if (preg_match($pattern, $match, $capture)) return $handler($call, $capture);
    }

    return new RestResponse(404, 'No route '.$match, []);
  }

  /**
   * Opens a request and returns a transmission instance
   * 
   * @param  webservices.rest.RestRequest $request
   * @return webservices.rest.io.Transmission
   */
  public function open(RestRequest $req) {
    return new TestCall($req, $this->formats, $this->marshalling);
  }

  /**
   * Finish a given transmission and returns the response
   *
   * @param  webservices.rest.io.Transmission $transmission
   * @return webservices.rest.RestResponse
   * @throws webservices.rest.RestException
   */
  public function finish(Transmission $transmission) {
    return $this->handle($transmission);
  }

  /**
   * Sends a request and returns the response
   *
   * @param  webservices.rest.RestRequest $request
   * @return webservices.rest.RestResponse
   * @throws webservices.rest.RestException
   */
  public function execute(RestRequest $request) {
    return $this->handle(new TestCall($request, $this->formats, $this->marshalling));
  }
}