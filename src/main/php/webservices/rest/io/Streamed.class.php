<?php namespace webservices\rest\io;

class Streamed extends Transfer {

  public function stream($request, $format, $value) {
    $stream= $this->endpoint->open($request->with(['Transfer-Encoding' => 'chunked']));
    $format->serialize($value, $stream);
    return $stream;
  }
}