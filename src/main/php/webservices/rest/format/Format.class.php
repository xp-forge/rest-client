<?php namespace webservices\rest\format;

abstract class Format {

  /**
   * Serialize a value and write it to the given stream
   *
   * @param  var $value
   * @param  io.streams.OutputStream $stream
   * @return io.streams.OutputStream
   */
  public abstract function serialize($value, $stream);

  /**
   * Deserialize a value from a given stream
   *
   * @param  io.streams.InputStream
   * @return var
   */
  public abstract function deserialize($stream);

}