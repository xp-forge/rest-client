<?php namespace webservices\rest;

use lang\IllegalStateException;

class UnexpectedError extends IllegalStateException {
  private $status, $stream;

  /**
   * Creates a new instance
   *
   * @param  int $status
   * @param  string $message
   * @param  io.stream.InputStream $stream
   */
  public function __construct($status, $message, $stream) {
    parent::__construct('Unexpected '.$status.' ('.$message.')');
    $this->status= $status;
    $this->stream= $stream;
  }

  /** @return int */
  public function status() { return $this->status; }

  /** @return io.stream.InputStream */
  public function stream() { return $this->stream; }

}