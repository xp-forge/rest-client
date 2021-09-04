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

  public function writer($request, $format, $marshalling) {
    $stream= $this->untraced->endpoint->open($request);

    if ($payload= $request->payload()) {
      $bytes= $format->serialize($marshalling->marshal($payload->value()), new MemoryOutputStream())->getBytes();
      $stream->request->setHeader('Content-Length', strlen($bytes));
      $this->cat->info('>>>', rtrim($stream->request->getHeaderString(), "\r\n"));
      $this->cat->debug($bytes);
    } else {
      $this->cat->info('>>>', rtrim($stream->request->getHeaderString(), "\r\n"));
    }

    return $this->untraced->endpoint->finish($stream);
  }

  public function reader($response, $format, $marshalling) {
    $this->cat->info('<<<', rtrim($response->getHeaderString(), "\r\n"));
    $bytes= Streams::readAll($response->in());
    $this->cat->debug($bytes);

    return new Reader(new MemoryInputStream($bytes), $format, $marshalling);
  }
}