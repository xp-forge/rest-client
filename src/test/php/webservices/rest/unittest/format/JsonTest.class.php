<?php namespace webservices\rest\unittest\format;

use io\streams\MemoryOutputStream;
use unittest\TestCase;
use webservices\rest\format\Json;

class JsonTest extends TestCase {

  #[@test]
  public function can_create() {
    new Json();
  }

  #[@test]
  public function serialize() {
    $format= new Json();
    $this->assertEquals('{"key":"value"}', $format->serialize(['key' => 'value'], new MemoryOutputStream())->getBytes());
  }
}