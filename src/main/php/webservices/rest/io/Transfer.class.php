<?php namespace webservices\rest\io;

use io\streams\Compression;
use lang\MethodNotImplementedException;

abstract class Transfer {
  protected $endpoint;

  /**
   * Returns stream, handling compression
   *
   * @param  webservices.rest.RestResponse
   * @return io.streams.InputStream
   */
  protected function in($response) {
    if ($encoding= $response->header('Content-Encoding')) {
      return Compression::named($encoding[0])->open($response->in());
    } else {
      return $response->in();
    }
  }

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
    return new Reader($this->in($response), $format, $marshalling);
  }
}