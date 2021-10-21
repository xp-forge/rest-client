<?php namespace webservices\rest\unittest\format;

use io\streams\{MemoryInputStream, MemoryOutputStream};
use unittest\Assert;
use unittest\{Test, TestCase};
use webservices\rest\format\FormUrlencoded;

class FormUrlencodedTest {

  #[Test]
  public function can_create() {
    new FormUrlencoded();
  }

  #[Test]
  public function serialize() {
    $format= new FormUrlencoded();
    Assert::equals('key=value', $format->serialize(['key' => 'value'], new MemoryOutputStream())->bytes());
  }

  #[Test]
  public function deserialize() {
    $format= new FormUrlencoded();
    Assert::equals(['key' => 'value'], $format->deserialize(new MemoryInputStream('key=value')));
  }
}