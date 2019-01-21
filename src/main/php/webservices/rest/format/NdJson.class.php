<?php namespace webservices\rest\format;

use io\streams\LinesIn;
use text\json\Format as WireFormat;
use text\json\StringInput;
use text\json\StreamOutput;
/**
 * Implements ndjson.
 *
 * @see http://ndjson.org
 */
class NdJson extends Format {
  const MIMETYPE= 'application/x-ndjson';
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
    $out= new StreamOutput($stream, $this->format);
    if (is_array($value) && is_int(key($value))) {
      foreach ($value as $val) {
        $out->write($val); // ensure our value is written as JSON to the stream
        $stream->write("\n"); // write newline to stream directly so it's not encoded as JSON string
      }
    } else {
      $out->write($value);
    }

    return $stream;
  }

  /**
   * Deserialize a value from a given stream
   *
   * @param  io.streams.InputStream
   * @return var
   */
  public function deserialize($stream) {
    $vals= [];
    foreach ((new LinesIn($stream)) as $line) {
      $vals[]= (new StringInput($line))->read();
    }

    return $vals;
  }
}