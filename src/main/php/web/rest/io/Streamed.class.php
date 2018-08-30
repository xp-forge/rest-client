<?php namespace web\rest\io;

class Streamed implements Transfer {

  public function writer($request, $payload, $format) {
    $request->setHeader('Transfer-Encoding', 'chunked');
    return function($stream) use($payload, $format) {
      return $format->serialize($payload, $stream);
    };
  }
}