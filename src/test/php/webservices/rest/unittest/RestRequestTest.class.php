<?php namespace webservices\rest\unittest;

use test\{Assert, Test, Values};
use webservices\rest\RestRequest;

class RestRequestTest {

  #[Test]
  public function can_create() {
    new RestRequest('GET', '/users');
  }

  #[Test]
  public function can_create_with_headers() {
    new RestRequest('GET', '/users', ['X-User-Id' => 6100]);
  }

  #[Test]
  public function method() {
    Assert::equals('GET', (new RestRequest('GET', '/users'))->method());
  }

  #[Test]
  public function path() {
    Assert::equals('/users', (new RestRequest('GET', '/users'))->path());
  }

  #[Test]
  public function headers() {
    $headers= ['X-User-Id' => 6100];
    Assert::equals($headers, (new RestRequest('GET', '/users', $headers))->headers());
  }

  #[Test]
  public function payload_null_by_default() {
    Assert::null((new RestRequest('GET', '/users'))->payload());
  }

  #[Test, Values([[null], ['Test'], [['key' => 'value']]])]
  public function payload($value) {
    Assert::equals($value, (new RestRequest('GET', '/users'))->transfer($value)->payload()->value());
  }
}