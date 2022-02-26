<?php namespace webservices\rest\io;

use io\streams\Compression;
use lang\MethodNotImplementedException;

abstract class Transfer {
  protected $endpoint;

  /** @param webservices.rest.Endpoint */
  public function __construct($endpoint) { $this->endpoint= $endpoint; }

  /** @return self */
  public function untraced() { return $this; }

  public function transmission($conn, $s, $target) {
    return new Transmission($conn, $s, $target);
  }

  public function headers($length) {
    throw new MethodNotImplementedException(__METHOD__);
  }

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
    if ($encoding= $response->header('Content-Encoding')) {
      $in= Compression::named($encoding[0])->open($response->in());
    } else {
      $in= $response->in();
    }

    return new Reader($in, $format, $marshalling);
  }
}