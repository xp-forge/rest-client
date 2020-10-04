<?php namespace webservices\rest\unittest\format;

use io\streams\{MemoryInputStream, MemoryOutputStream};
use unittest\{Test, TestCase};
use webservices\rest\format\FormUrlencoded;

class FormUrlencodedTest extends TestCase {

  #[Test]
  public function can_create() {
    new FormUrlencoded();
  }

  #[Test]
  public function serialize() {
    $format= new FormUrlencoded();
    $this->assertEquals('key=value', $format->serialize(['key' => 'value'], new MemoryOutputStream())->getBytes());
  }

  #[Test]
  public function deserialize() {
    $format= new FormUrlencoded();
    $this->assertEquals(['key' => 'value'], $format->deserialize(new MemoryInputStream('key=value')));
  }
}