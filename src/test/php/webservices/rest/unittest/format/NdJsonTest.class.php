<?php namespace webservices\rest\unittest\format;

use io\streams\{MemoryInputStream, MemoryOutputStream};
use lang\XPException;
use test\Assert;
use test\{Test, TestCase, Values};
use webservices\rest\format\NdJson;

class NdJsonTest {

  #[Test]
  public function can_create() {
    new NdJson();
  }

  #[Test, Values([[['some', 'value'], "\"some\"\n\"value\"\n"], [[['key' => 'value']], "{\"key\":\"value\"}\n"], [[['key' => 'value'], ['other'=>'value']], "{\"key\":\"value\"}\n{\"other\":\"value\"}\n"],])]
  public function serialize($value, $expected) {
    Assert::equals($expected, (new NdJson())->serialize(new \ArrayIterator($value), new MemoryOutputStream())->bytes());
  }

  #[Test, Values([[[], '{}'], [['key' => 'value'], '{"key":"value"}'],])]
  public function serialize_object($map, $expected) {
    Assert::equals($expected, (new NdJson())->serialize((object)$map, new MemoryOutputStream())->bytes());
  }

  #[Test, Values([[[], '[]'], [['key' => 'value'], '{"key":"value"}'],])]
  public function serialize_array($map, $expected) {
    Assert::equals($expected, (new NdJson())->serialize($map, new MemoryOutputStream())->bytes());
  }

  #[Test, Values([["\"some\"\n\"value\"\n", ['some', 'value']], ['{"key":"value"}', [['key' => 'value']]], ["{\"key\":\"value\"}\n{\"other\":\"value\"}\n", [['key' => 'value'], ['other'=>'value']]],])]
  public function deserialize($value, $expected) {
    Assert::equals($expected, iterator_to_array((new NdJson())->deserialize(new MemoryInputStream($value))));
  }
}