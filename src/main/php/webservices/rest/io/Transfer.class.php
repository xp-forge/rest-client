<?php namespace webservices\rest\io;

abstract class Transfer {

  /** @return self */
  public function untraced() { return $this; }

  public function header($request) {
    // NOOP
  }

  public abstract function writer($request, $payload, $format, $marshalling);

  public function reader($response, $format, $marshalling) {
    return new Reader($response->in(), $format, $marshalling);
  }
}