<?php namespace webservices\rest\unittest;

use unittest\{Test, TestCase, Values};
use webservices\rest\RestRequest;

class RestRequestTest extends TestCase {

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
    $this->assertEquals('GET', (new RestRequest('GET', '/users'))->method());
  }

  #[Test]
  public function path() {
    $this->assertEquals('/users', (new RestRequest('GET', '/users'))->path());
  }

  #[Test]
  public function headers() {
    $headers= ['X-User-Id' => 6100];
    $this->assertEquals($headers, (new RestRequest('GET', '/users', $headers))->headers());
  }

  #[Test]
  public function payload_null_by_default() {
    $this->assertNull((new RestRequest('GET', '/users'))->payload());
  }

  #[Test, Values([null, 'Test', ['key' => 'value']])]
  public function payload($value) {
    $this->assertEquals($value, (new RestRequest('GET', '/users'))->transfer($value)->payload()->value());
  }
}