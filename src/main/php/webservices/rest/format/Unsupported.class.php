<?php namespace webservices\rest\format;

class Unsupported extends Format {
  private $mime;

  /** @param string $mime */
  public function __construct($mime) {
    $this->mime= $mime;
  }

  /**
   * Serialize a value and write it to the given stream
   *
   * @param  var $value
   * @param  io.streams.OutputStream $stream
   * @return io.streams.OutputStream
   */
  public function serialize($value, $stream) {
    throw new FormatUnsupported('Cannot serialize '.($this->mime ? 'to '.$this->mime : 'without mime type'));
  }

  /**
   * Deserialize a value from a given stream
   *
   * @param  io.streams.InputStream
   * @return var
   */
  public function deserialize($stream) {
    throw new FormatUnsupported('Cannot deserialize '.($this->mime ? 'from '.$this->mime : 'without mime type'));
  }
}