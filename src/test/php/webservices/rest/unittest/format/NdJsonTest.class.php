<?php namespace webservices\rest\unittest\format;

use io\streams\MemoryInputStream;
use io\streams\MemoryOutputStream;
use lang\XPException;
use unittest\TestCase;
use webservices\rest\format\NdJson;

class NdJsonTest extends TestCase {

  #[@test]
  public function can_create() {
    new NdJson();
  }

  #[@test, @values([
  #  [['some', 'value'], "\"some\"\n\"value\"\n"],
  #  [[['key' => 'value']], "{\"key\":\"value\"}\n"],
  #  [[['key' => 'value'], ['other'=>'value']], "{\"key\":\"value\"}\n{\"other\":\"value\"}\n"],
  #])]
  public function serialize($value, $expected) {
    $this->assertEquals($expected, (new NdJson())->serialize(new \ArrayIterator($value), new MemoryOutputStream())->getBytes());
  }

  #[@test, @values([
  #  [[], '{}'],
  #  [['key' => 'value'], '{"key":"value"}'],
  #])]
  public function serialize_object($map, $expected) {
    $this->assertEquals($expected, (new NdJson())->serialize((object)$map, new MemoryOutputStream())->getBytes());
  }

  #[@test, @values([
  #  [[], '[]'],
  #  [['key' => 'value'], '{"key":"value"}'],
  #])]
  public function serialize_array($map, $expected) {
    $this->assertEquals($expected, (new NdJson())->serialize($map, new MemoryOutputStream())->getBytes());
  }

  #[@test, @values([
  #  ["\"some\"\n\"value\"\n", ['some', 'value']],
  #  ['{"key":"value"}', [['key' => 'value']]],
  #  ["{\"key\":\"value\"}\n{\"other\":\"value\"}\n", [['key' => 'value'], ['other'=>'value']]],
  #])]
  public function deserialize($value, $expected) {
    $this->assertEquals($expected, iterator_to_array((new NdJson())->deserialize(new MemoryInputStream($value))));
  }
}