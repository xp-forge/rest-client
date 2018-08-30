<?php namespace web\rest\format;

class FormUrlencoded extends Format {

  public function serialize($value, $stream) {
    // TBI
    return $stream;
  }

  public function deserialize($stream) {
    // TBI
  }
}