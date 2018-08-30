<?php namespace webservices\rest\io;

use io\streams\InputStream;
use util\data\Marshalling;
use webservices\rest\format\Format;

class Reader {
  private $stream, $format, $marshalling;

  public function __construct(InputStream $stream, Format $format, Marshalling $marshalling) {
    $this->stream= $stream;
    $this->format= $format;
    $this->marshalling= $marshalling;
  }

  /** @return io.streams.InputStream */
  public function stream() { return $this->stream; }

  public function read($type= 'var') {
    return $this->marshalling->unmarshal($this->format->deserialize($this->stream), $type);
  }
}