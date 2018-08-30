<?php namespace web\rest\io;

use io\streams\MemoryOutputStream;

class Buffered implements Transfer {

  public function writer($request, $payload, $format) {
    $bytes= $format->serialize($payload, new MemoryOutputStream())->getBytes();
    $request->setHeader('Content-Length', strlen($bytes));
    return function($request, $stream) use($bytes) {
      $stream->write($bytes);
      return $stream;
    };
  }
}