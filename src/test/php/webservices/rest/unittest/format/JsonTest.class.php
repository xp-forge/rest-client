<?php namespace webservices\rest\unittest\format;

use io\streams\{MemoryInputStream, MemoryOutputStream};
use unittest\{Test, TestCase, Values};
use webservices\rest\format\Json;

class JsonTest extends TestCase {

  #[Test]
  public function can_create() {
    new Json();
  }

  #[Test]
  public function serialize() {
    $this->assertEquals('{"key":"value"}', (new Json())->serialize(['key' => 'value'], new MemoryOutputStream())->getBytes());
  }

  #[Test, Values([[[], '{}'], [['key' => 'value'], '{"key":"value"}'],])]
  public function serialize_object($map, $expected) {
    $this->assertEquals($expected, (new Json())->serialize((object)$map, new MemoryOutputStream())->getBytes());
  }

  #[Test]
  public function deserialize() {
    $format= new Json();
    $this->assertEquals(['key' => 'value'], $format->deserialize(new MemoryInputStream('{"key":"value"}')));
  }
}