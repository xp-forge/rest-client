<?php namespace webservices\rest;

/**
 * Endpoint subclass used for testing REST clients.
 *
 * ```php
 * $endpoint= new TestEndpoint([
 *  '/users/me' => function($call) {
 *    return $call->respond(307, 'Temporary Redirect', ['Location' => '/users/6100']);
 *   }
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
   * @param  [:function(webservices.rest.TestCall): webservices.rest.RestResponse]
   */
  public function __construct(array $routes) {
    parent::__construct('http://test.local', Formats::defaults());
    $this->routes= $routes;
  }

  /**
   * Sends a request and returns the response
   *
   * @param  webservices.rest.RestRequest $request
   * @return webservices.rest.RestResponse
   * @throws webservices.rest.RestException
   */
  public function execute(RestRequest $req) {
    $handler= $this->routes[$req->method().' '.$req->path()]
      ?? $this->routes[$req->path()]
      ?? function() { return new RestResponse(404, 'No route', []); }
    ;

    return $handler(new TestCall($req, $this->formats, $this->marshalling));
  }
}