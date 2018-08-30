<?php namespace webservices\rest\unittest\format;

use io\streams\MemoryInputStream;
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

  #[@test]
  public function deserialize() {
    $format= new Json();
    $this->assertEquals(['key' => 'value'], $format->deserialize(new MemoryInputStream('{"key":"value"}')));
  }
}