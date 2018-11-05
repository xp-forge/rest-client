<?php namespace webservices\rest\io;

use io\streams\MemoryOutputStream;

class Buffered extends Transfer {

  protected function payload($request, $payload, $format, $marshalling) {
    $bytes= $format->serialize($marshalling->marshal($payload), new MemoryOutputStream())->getBytes();
    $request->setHeader('Content-Length', strlen($bytes));
    return function($conn) use($request, $bytes) {
      $stream= $conn->open($request);
      $stream->write($bytes);
      return $conn->finish($stream);
    };
  }
}