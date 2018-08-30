<?php namespace webservices\rest\format;

use text\json\Format as WireFormat;
use text\json\StreamInput;
use text\json\StreamOutput;

/**
 * Represents `application/json`
 *
 * @test  xp://webservices.rest.unittest.format.JsonTest
 */
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

  /**
   * Serialize a value and write it to the given stream
   *
   * @param  var $value
   * @param  io.streams.OutputStream $stream
   * @return io.streams.OutputStream
   */
  public function serialize($value, $stream) {
    (new StreamOutput($stream, $this->format))->write($value);
    return $stream;
  }

  /**
   * Deserialize a value from a given stream
   *
   * @param  io.streams.InputStream
   * @return var
   */
  public function deserialize($stream) {
    return (new StreamInput($stream))->read();
  }
}