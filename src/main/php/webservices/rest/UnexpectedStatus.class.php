<?php namespace webservices\rest;

use lang\IllegalStateException;
use webservices\rest\format\Unsupported;

class UnexpectedStatus extends IllegalStateException {
  private $response;

  /** @param webservices.rest.RestResponse */
  public function __construct($response) {
    parent::__construct('Unexpected '.$response->status().' ('.$response->message().')');
    $this->response= $response;
  }

  /** @return int */
  public function status() { return $this->response->status(); }

  /**
   * Returns body from this response, deserializing if possible.
   *
   * @see    webservices.rest.Result::error()
   * @param  ?string $type
   * @return var
   */
  public function cause($type= null) {
    return $this->response->format() instanceof Unsupported
      ? $this->response->content()
      : $this->response->value($type ?? 'var')
    ;
  }
}