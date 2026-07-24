<?php namespace webservices\rest;

use lang\Throwable;
use peer\http\HttpRequest;

/**
 * Indicates a REST request call failed
 *
 * @test  webservices.rest.unittest.ExecuteTest
 */
class RequestFailed extends RestException {
  public $request;
  
  /** Creates a new instance from a given request and cause */
  public function __construct(HttpRequest $request, Throwable $cause) {
    parent::__construct("Failed executing {$request->method} {$request->target()}", $cause);
    $this->request= $request;
  }
}