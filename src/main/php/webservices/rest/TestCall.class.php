<?php namespace webservices\rest;

use webservices\rest\io\{Reader, Transmission};
use io\streams\{InputStream, MemoryInputStream};
use util\data\Marshalling;

class TestCall extends Transmission {
  private $formats, $marshalling;
  public $transfer= null;

  /** Creates a new call */
  public function __construct(RestRequest $request, Formats $formats, Marshalling $marshalling= null) {
    $this->request= $request;
    $this->formats= $formats;
    $this->marshalling= $marshalling ?? new Marshalling();
  }

  /** Returns the request associated with this call */
  public function request(): RestRequest { return $this->request; }

  /**
   * Writes given bytes
   *
   * @param  string $bytes
   * @return int
   */
  public function write($bytes) {
    $this->transfer.= $bytes;
    return strlen($bytes);
  }

  /** @return void */
  public function flush() {
    // NOOP
  }

  /** @return void */
  public function close() {
    // NOOP
  }

  /**
   * Responds to this call with a given status call, message, headers and payload.
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   * @param  string|io.streams.InputStream $payload
   * @return webservices.rest.RestResponse
   */
  public function respond($status, $message, $headers= [], $payload= null): RestResponse {
    return new RestResponse($status, $message, $headers, new Reader(
      $payload instanceof InputStream ? $payload : new MemoryInputStream($payload),
      $this->formats->named($headers['Content-Type'] ?? null),
      $this->marshalling
    ));
  }
}