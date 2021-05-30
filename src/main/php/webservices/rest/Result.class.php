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
   * @throws webservices.rest.UnexpectedStatus
   */
  public function value($type= null) {
    $s= $this->response->status();
    if ($s >= 200 && $s < 300) return $this->response->value($type ?? 'var');

    throw new UnexpectedStatus($this->response);
  }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Returns NULL for a given list of status codes indicating absence, defaulting
   * to 404s. Throws an exception if the HTTP statuscode is 400 and above.
   *
   * @param  ?string $type
   * @param  int[] $absent Status code indicating absence
   * @return var
   * @throws webservices.rest.UnexpectedStatus
   */
  public function optional($type= null, $absent= [404]) {
    $s= $this->response->status();
    if ($s >= 200 && $s < 300) return $this->response->value($type ?? 'var');
    if (in_array($s, $absent)) return null;

    throw new UnexpectedStatus($this->response);
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