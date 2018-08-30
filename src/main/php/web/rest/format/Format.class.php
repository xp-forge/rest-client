<?php namespace web\rest\format;

abstract class Format {

  public abstract function serialize($value, $stream);

  public abstract function deserialize($stream, $type);

}