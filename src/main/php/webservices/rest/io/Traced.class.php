<?php namespace webservices\rest\io;

use io\streams\MemoryInputStream;
use io\streams\MemoryOutputStream;
use io\streams\Streams;

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

  public function header($request) {
    $this->cat->info('>>>', substr($request->getHeaderString(), 0, -2));
  }

  public function writer($request, $payload, $format, $marshalling) {
    $this->cat->info('>>>', substr($request->getHeaderString(), 0, -2));
    $bytes= $format->serialize($marshalling->marshal($payload), new MemoryOutputStream())->getBytes();
    $this->cat->debug($bytes);

    // We've created it anyway, now simply transfer the bytes in a buffered manner
    $request->setHeader('Content-Length', strlen($bytes));
    return function($stream) use($bytes) {
      $stream->write($bytes);
      return $stream;
    };
  }

  public function reader($response, $format, $marshalling) {
    $this->cat->info('<<<', substr($response->getHeaderString(), 0, -2));
    $bytes= Streams::readAll($response->in());
    $this->cat->debug($bytes);

    return new Reader(new MemoryInputStream($bytes), $format, $marshalling);
  }
}