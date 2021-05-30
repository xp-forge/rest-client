<?php namespace webservices\rest;

use webservices\rest\format\Unsupported;

class Result {
  private $response;

  /** @param webservices.rest.RestResponse */
  public function __construct($response) { $this->response= $response; }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Throws an exception if the HTTP statuscode is 400 and above.
   *
   * @param  ?string $type
   * @return var
   * @throws webservices.rest.UnexpectedError
   */
  public function value($type= null) {
    if ($this->response->status() < 400) return $this->response->value($type ?? 'var');

    throw new UnexpectedError($this->response);
  }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Returns NULL for a given list of status codes indicating absence, defaulting
   * to 404s. Throws an exception if the HTTP statuscode is 400 and above.
   *
   * @param  ?string $type
   * @param  int[] $absent Status code indicating absence
   * @return var
   * @throws webservices.rest.UnexpectedError
   */
  public function optional($type= null, $absent= [404]) {
    if ($this->response->status() < 400) return $this->response->value($type ?? 'var');
    if (in_array($this->response->status(), $absent)) return null;

    throw new UnexpectedError($this->response);
  }

  /**
   * Returns the error from the response, using the given type for deserialization.
   * Falls back to using the complete body as a string if the response format is
   * unsupported.
   *
   * @param  ?string $type
   * @return var
   */
  public function error($type= null) {
    if ($this->response->status() < 400) return null;

    return $this->response->format() instanceof Unsupported
      ? $this->response->content()
      : $this->response->value($type ?? 'var')
    ;
  }
}