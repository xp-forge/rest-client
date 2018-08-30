<?php namespace webservices\rest\io;

use io\streams\MemoryOutputStream;

class Buffered implements Transfer {

  public function writer($request, $payload, $format, $marshalling) {
    $bytes= $format->serialize($marshalling->marshal($payload), new MemoryOutputStream())->getBytes();
    $request->setHeader('Content-Length', strlen($bytes));
    return function($stream) use($bytes) {
      $stream->write($bytes);
      return $stream;
    };
  }
}