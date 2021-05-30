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

  /** @return webservices.rest.format.Format */
  public function format() { return $this->format; }

  /** @return io.streams.InputStream */
  public function stream() { return $this->stream; }

  /**
   * Reads the payload as a string
   *
   * @return string
   */
  public function content() {
    try {
      $r= '';
      while ($this->stream->available()) {
        $r.= $this->stream->read();
      }
      return $r;
    } finally {
      $this->stream->close();
    }
  }

  /**
   * Reads the payload and unmarshals it to data
   *
   * @param  string $type
   * @return var
   */
  public function read($type= 'var') {
    return $this->marshalling->unmarshal($this->format->deserialize($this->stream), $type);
  }
}