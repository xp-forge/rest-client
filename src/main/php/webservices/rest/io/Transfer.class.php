<?php namespace webservices\rest\io;

use lang\MethodNotImplementedException;

abstract class Transfer {
  protected $endpoint;

  public function __construct($endpoint) { $this->endpoint= $endpoint; }

  /** @return self */
  public function untraced() { return $this; }

  public function stream($request, $format, $payload) {
    throw new MethodNotImplementedException(__METHOD__);
  }

  public function writer($request, $format, $marshalling) {
    if ($payload= $request->payload()) {
      $stream= $this->stream($request, $format, $marshalling->marshal($payload->value()));
    } else {
      $stream= $this->endpoint->open($request);
    }

    return $this->endpoint->finish($stream);
  }

  public function reader($response, $format, $marshalling) {
    return new Reader($response->in(), $format, $marshalling);
  }
}