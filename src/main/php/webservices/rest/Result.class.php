<?php namespace webservices\rest;

class Result {
  private $response;

  /** @param webservices.rest.RestResponse */
  public function __construct($response) { $this->response= $response; }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Throws an exception if the HTTP statuscode is 400 and above.
   *
   * @param  string $type
   * @return var
   * @throws webservices.rest.UnexpectedError
   */
  public function value($type= 'var') {
    if ($this->response->status() < 400) return $this->response->value($type);

    throw new UnexpectedError($this->response);
  }
}