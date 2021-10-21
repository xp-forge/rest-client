<?php namespace webservices\rest\io;

use io\streams\MemoryOutputStream;

class Buffered extends Transfer {

  public function headers($length) { return ['Content-Length' => $length]; }

  public function stream($request, $format, $value) {
    $bytes= $format->serialize($value, new MemoryOutputStream())->bytes();
    $stream= $this->endpoint->open($request->with(['Content-Length' => strlen($bytes)]));
    $stream->write($bytes);
    return $stream;
  }
}