<?php namespace webservices\rest;

use lang\IllegalStateException;

class UnexpectedError extends IllegalStateException {
  private $response;

  /** @param webservices.rest.RestResponse */
  public function __construct($response) {
    parent::__construct('Unexpected '.$response->status().' ('.$response->message().')');
    $this->response= $response;
  }

  /** @return int */
  public function status() { return $this->response->status(); }

  /** @return webservices.rest.RestResponse */
  public function response() { return $this->response; }

}