<?php namespace webservices\rest\io;

class Streamed extends Transfer {

  protected function payload($request, $payload, $format, $marshalling) {
    $request->setHeader('Transfer-Encoding', 'chunked');
    return function($conn) use($request, $payload, $format, $marshalling) {
      $stream= $conn->open($request);
      $format->serialize($marshalling->marshal($payload), $stream);
      return $conn->finish($stream);
    };
  }
}