<?php namespace web\rest\format;

class Unsupported extends Format {
  private $mime;

  public function __construct($mime) {
    $this->mime= $mime;
  }

  public function serialize($value, $stream) {
    throw new FormatUnsupported('Cannot serialize '.($this->mime ? 'to '.$this->mime : 'without mime type'));
  }

  public function deserialize($stream) {
    throw new FormatUnsupported('Cannot deserialize '.($this->mime ? 'from '.$this->mime : 'without mime type'));
  }
}