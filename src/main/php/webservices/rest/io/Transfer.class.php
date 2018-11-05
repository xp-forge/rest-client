<?php namespace webservices\rest\io;

use lang\MethodNotImplementedException;

abstract class Transfer {

  /** @return self */
  public function untraced() { return $this; }

  protected function payload($request, $value, $format, $marshalling) {
    throw new MethodNotImplementedException('payload()');
  }

  public function writer($request, $payload, $format, $marshalling) {
    if ($payload) {
      return $this->payload($request, $payload->value(), $format, $marshalling);
    } else {
      return function($conn) use($request) { return $conn->send($request); };
    }
  }

  public function reader($response, $format, $marshalling) {
    return new Reader($response->in(), $format, $marshalling);
  }
}