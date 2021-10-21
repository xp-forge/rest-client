<?php namespace webservices\rest\unittest\format;

use io\streams\{MemoryInputStream, MemoryOutputStream};
use unittest\Assert;
use unittest\{Test, TestCase, Values};
use webservices\rest\format\Json;

class JsonTest {

  #[Test]
  public function can_create() {
    new Json();
  }

  #[Test]
  public function serialize() {
    Assert::equals('{"key":"value"}', (new Json())->serialize(['key' => 'value'], new MemoryOutputStream())->bytes());
  }

  #[Test, Values([[[], '{}'], [['key' => 'value'], '{"key":"value"}'],])]
  public function serialize_object($map, $expected) {
    Assert::equals($expected, (new Json())->serialize((object)$map, new MemoryOutputStream())->bytes());
  }

  #[Test]
  public function deserialize() {
    $format= new Json();
    Assert::equals(['key' => 'value'], $format->deserialize(new MemoryInputStream('{"key":"value"}')));
  }
}