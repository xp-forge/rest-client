<?php namespace web\rest\format;

use text\json\Format as WireFormat;
use text\json\StreamInput;
use text\json\StreamOutput;

class Json extends Format {
  private $format;

  /**
   * Constructor
   *
   * @param  text.json.Format $format Optional wire format, defaults to *dense*
   */
  public function __construct(WireFormat $format= null) {
    $this->format= $format ?: WireFormat::dense();
  }

  public function serialize($value, $stream) {
    (new StreamOutput($stream, $this->format))->write($value);
    return $stream;
  }

  public function deserialize($stream, $type) {
    return (new StreamInput($stream))->read();
  }
}