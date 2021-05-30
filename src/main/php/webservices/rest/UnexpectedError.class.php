<?php namespace webservices\rest;

use lang\IllegalStateException;
use webservices\rest\format\Unsupported;

class UnexpectedError extends IllegalStateException {
  private $response;

  /** @param webservices.rest.RestResponse */
  public function __construct($response) {
    parent::__construct('Unexpected '.$response->status().' ('.$response->message().')');
    $this->response= $response;
  }

  /** @return int */
  public function status() { return $this->response->status(); }

  /**
   * Returns error from this response, deserializing content if possible.
   *
   * @see    webservices.rest.Result::error()
   * @param  ?string $type
   * @return var
   */
  public function error($type= null) {
    return $this->response->format() instanceof Unsupported
      ? $this->response->content()
      : $this->response->value($type ?? 'var')
    ;
  }
}