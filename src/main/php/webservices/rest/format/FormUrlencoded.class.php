<?php namespace webservices\rest\format;

use io\streams\Streams;

/**
 * Represents `application/x-www-form-urlencoded`
 *
 * @test  xp://webservices.rest.unittest.format.FormUrlencodedTest
 */
class FormUrlencoded extends Format {

  /**
   * Serialize a value and write it to the given stream
   *
   * @param  var $value
   * @param  io.streams.OutputStream $stream
   * @return io.streams.OutputStream
   */
  public function serialize($value, $stream) {
    $stream->write(http_build_query($value));
    return $stream;
  }

  /**
   * Deserialize a value from a given stream
   *
   * @param  io.streams.InputStream
   * @return var
   */
  public function deserialize($stream) {
    parse_str(Streams::readAll($stream), $values);
    return $values;
  }
}