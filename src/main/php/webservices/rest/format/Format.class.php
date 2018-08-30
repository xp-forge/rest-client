<?php namespace webservices\rest\format;

abstract class Format {

  public abstract function serialize($value, $stream);

  public abstract function deserialize($stream);

}