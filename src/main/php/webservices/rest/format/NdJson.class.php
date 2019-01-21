<?php namespace webservices\rest\format;

use Traversable;
use io\streams\LinesIn;
use text\json\Format as WireFormat;
use text\json\StringInput;
use text\json\StreamOutput;
use util\XPIterator;
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
    if ($value instanceof Traversable) {
      foreach ($value as $val) {
        $out->write($val); // ensure our value is written as JSON to the stream
        $stream->write("\n"); // write newline to stream directly so it's not encoded as JSON string
      }
    } else if ($value instanceof XPIterator) {
      while ($value->hasNext()) {
        $out->write($value->next());
        $stream->write("\n");
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
   * @return Generator
   */
  public function deserialize($stream) {
    foreach ((new LinesIn($stream)) as $line) {
      yield (new StringInput($line))->read();
    }
  }
}