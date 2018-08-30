<?php namespace webservices\rest\unittest;

use unittest\TestCase;
use webservices\rest\RestRequest;

class RestRequestTest extends TestCase {

  #[@test]
  public function can_create() {
    new RestRequest('GET', '/users');
  }

  #[@test]
  public function can_create_with_headers() {
    new RestRequest('GET', '/users', ['X-User-Id' => 6100]);
  }

  #[@test]
  public function method() {
    $this->assertEquals('GET', (new RestRequest('GET', '/users'))->method());
  }

  #[@test]
  public function path() {
    $this->assertEquals('/users', (new RestRequest('GET', '/users'))->path());
  }

  #[@test]
  public function headers() {
    $headers= ['X-User-Id' => 6100];
    $this->assertEquals($headers, (new RestRequest('GET', '/users', $headers))->headers());
  }

  #[@test]
  public function payload_null_by_default() {
    $this->assertNull((new RestRequest('GET', '/users'))->payload());
  }

  #[@test, @values([null, 'Test', ['key' => 'value']])]
  public function payload($value) {
    $this->assertEquals($value, (new RestRequest('GET', '/users'))->transfer($value)->payload()->value());
  }
}