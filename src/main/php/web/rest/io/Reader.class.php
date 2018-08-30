<?php namespace web\rest\io;

use io\streams\InputStream;

class Reader {

  public function __construct(InputStream $stream, $type) {
    $this->stream= $stream;
    $this->type= $type;
  }

  /** @return io.streams.InputStream */
  public function stream() { return $this->stream; }

  public function read($type= 'var') {
    return $this->type->deserialize($this->stream, $type);
  }
}