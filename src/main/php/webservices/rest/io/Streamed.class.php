<?php namespace webservices\rest\io;

class Streamed extends Transfer {
  const HEADERS = [
    'Content-Length'    => [],
    'Transfer-Encoding' => ['chunked'],
  ];

  public function headers($length) { return self::HEADERS; }

  public function stream($request, $format, $value) {
    $stream= $this->endpoint->open($request->with(self::HEADERS));
    $format->serialize($value, $stream);
    return $stream;
  }
}