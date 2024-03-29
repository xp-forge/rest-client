<?php namespace webservices\rest\io;

use io\streams\OutputStream;

class Transmission implements OutputStream {
  protected $conn, $request, $output;
  public $target;

  /**
   * Creates a new instance
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @param  ?util.URI $target
   */
  public function __construct($conn, $request, $target= null) {
    $this->conn= $conn;
    $this->request= $request;
    $this->target= $target;
  }

  /** @return peer.http.HttpConnection */
  public function connection() { return $this->conn; }

  /** @return void */
  public function start() {
    $this->output= $this->conn->open($this->request);
  }

  /**
   * Writes bytes. Opens connection if not previously connected.
   *
   * @param  string $bytes
   * @return int
   * @throws io.IOException
   */
  public function write($bytes) {
    $this->output ?? $this->start();
    return $this->output->write($bytes);
  }

  /** @return void */
  public function flush() {
    // NOOP
  }

  /** @return void */
  public function close() {
    // NOOP
  }

  /** @return peer.http.HttpResponse */
  public function finish() {
    return $this->output ? $this->conn->finish($this->output) : $this->conn->send($this->request);
  }
}