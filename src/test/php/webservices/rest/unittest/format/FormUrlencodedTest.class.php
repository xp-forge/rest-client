<?php namespace webservices\rest\unittest\format;

use io\streams\MemoryInputStream;
use io\streams\MemoryOutputStream;
use unittest\TestCase;
use webservices\rest\format\FormUrlencoded;

class FormUrlencodedTest extends TestCase {

  #[@test]
  public function can_create() {
    new FormUrlencoded();
  }

  #[@test]
  public function serialize() {
    $format= new FormUrlencoded();
    $this->assertEquals('key=value', $format->serialize(['key' => 'value'], new MemoryOutputStream())->getBytes());
  }

  #[@test]
  public function deserialize() {
    $format= new FormUrlencoded();
    $this->assertEquals(['key' => 'value'], $format->deserialize(new MemoryInputStream('key=value')));
  }
}