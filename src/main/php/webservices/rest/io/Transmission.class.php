<?php namespace webservices\rest\io;

use io\streams\{Compression, OutputStream, MemoryInputStream, MemoryOutputStream, Streams};

class Transmission implements OutputStream {
  private $conn, $request, $output, $transfer, $cat;
  public $target, $transferred;

  /**
   * Creates a new instance
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @param  ?util.URI $target
   * @param  webservices.rest.io.Transfer $transfer
   * @param  ?util.log.LogCategory $cat
   */
  public function __construct($conn, $request, $target, $transfer, $cat= null) {
    $this->conn= $conn;
    $this->request= $request;
    $this->target= $target;
    $this->transfer= $transfer;
    $this->cat= $cat;
  }

  /** @return peer.http.HttpConnection */
  public function connection() { return $this->conn; }

  /**
   * Transmit payload in a given format
   *
   * @param  var $payload
   * @param  webservices.rest.format.Format $format
   */
  public function transmit($payload, $format) {
    $this->start();

    // Include complete payload in debug trace (before sending it).
    if ($this->cat) {
      $bytes= $format->serialize($payload, new MemoryOutputStream())->bytes();
      $this->cat->debug($bytes);
      $this->write($bytes);
    } else {
      $format->serialize($payload, $this);
    }
  }

  /** @return void */
  private function start() {
    $this->output= $this->transfer->start($this->conn, $this->request);
    $this->cat && $this->cat->info('>>>', substr($this->request->getHeaderString(), 0, -2));
    $this->transferred= 0;
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
    $this->output->write($bytes);

    $l= strlen($bytes);
    $this->transferred+= $l;
    return $l;
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
    if (null === $this->output) {
      $this->cat && $this->cat->info('>>>', substr($this->request->getHeaderString(), 0, -2));
    } else {
      $this->cat && $this->cat->debug("({$this->transferred} bytes transferred)");
    }

    $response= $this->transfer->finish($this->conn, $this->request, $this->output);
    $this->cat && $this->cat->info('<<<', substr($response->getHeaderString(), 0, -2));
    return $response;
  }

  /**
   * Returns a stream
   *
   * @param  peer.http.HttpResponse $response
   * @return io.streams.InputStream
   */
  public function stream($response) { 
    if ($encoding= $response->header('Content-Encoding')) {
      $in= Compression::named($encoding[0])->open($response->in());
    } else {
      $in= $response->in();
    }
    if (null === $this->cat) return $in;

    // Include complete payload in debug trace (before returning it).
    $content= Streams::readAll($in);
    $this->cat->debug($content);
    return new MemoryInputStream($content);
  }
}