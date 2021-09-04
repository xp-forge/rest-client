<?php namespace webservices\rest\io;

use io\streams\{MemoryInputStream, MemoryOutputStream, Streams};

class Traced extends Transfer {
  private $untraced, $cat;

  /**
   * Created a new traced transfer
   *
   * @param  parent $untraced
   * @param  util.log.LogCategory $cat
   */
  public function __construct($untraced, $cat) {
    $this->untraced= $untraced;
    $this->cat= $cat;
  }

  /** @return parent */
  public function untraced() { return $this->untraced; }

  public function transmission($conn, $s, $target) {
    return new class($conn, $s, $target, $this->cat) extends Transmission {
      private $cat;

      public function __construct($conn, $request, $target, $cat) {
        parent::__construct($conn, $request, $target);
        $this->cat= $cat;
      }

      public function write($bytes) {
        if (null === $this->output) {
          $this->cat->info('>>>', substr($this->request->getHeaderString(), 0, -2));
          $this->output= $this->conn->open($this->request);
        }

        $this->cat->debug($bytes);
        return $this->output->write($bytes);
      }

      public function finish() {
        if (null === $this->output) {
          $this->cat->info('>>>', substr($this->request->getHeaderString(), 0, -2));
          return $this->conn->send($this->request);
        } else {
          return $this->conn->finish($this->output);
        }
      }
    };
  }

  public function writer($request, $format, $marshalling) {
    $stream= $this->untraced->endpoint->open($request);

    // Send payload in one big chunk to create compact logging output
    if ($payload= $request->payload()) {
      $bytes= $format->serialize($marshalling->marshal($payload->value()), new MemoryOutputStream())->getBytes();
      $stream->request->setHeader('Content-Length', strlen($bytes));
      $stream->write($bytes);
    }

    return $this->untraced->endpoint->finish($stream);
  }

  public function reader($response, $format, $marshalling) {
    $this->cat->info('<<<', substr($response->getHeaderString(), 0, -2));
    $bytes= Streams::readAll($response->in());
    $this->cat->debug($bytes);

    return new Reader(new MemoryInputStream($bytes), $format, $marshalling);
  }
}