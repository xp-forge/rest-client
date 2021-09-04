<?php namespace webservices\rest\io;

use io\streams\OutputStream;

class Parts implements OutputStream {
  private $transmission, $boundary;
  private $closed= true;

  /**
   * Creates a new parts instance
   *
   * @param  string $boundary
   * @param  webservices.rest.Transmission $transmission
   */
  public function __construct($boundary, $transmission) {
    $this->boundary= $boundary;
    $this->transmission= $transmission;
  }

  /**
   * Writes a given chunk of bytes
   *
   * @param  string $bytes
   * @return int
   */
  public function write($bytes) {
    return $this->transmission->write($bytes);
  }

  /** @return void */
  public function flush() {
    // NOOP
  }

  /** @return void */
  public function close() {
    if ($this->closed) return;
    $this->transmission->write("\r\n");
    $this->closed= true;
  }

  /**
   * Begins a new part with given headers
   * 
   * @param  string[] $headers
   * @return void
   */
  public function begin($headers) {
    $this->closed || $this->transmission->write("\r\n");
    $this->closed= false;
    $this->transmission->write("--{$this->boundary}\r\n".implode("\r\n", $headers)."\r\n\r\n");
  }

  /**
   * Finalize all parts. The stream must not be written to after calling this!
   * 
   * @return webservices.rest.Transmission
   */
  public function finalize() {
    $this->transmission->write($this->closed ? "--{$this->boundary}--\r\n" : "\r\n--{$this->boundary}--\r\n");
    $this->closed= true;
    return $this->transmission;
  }
}