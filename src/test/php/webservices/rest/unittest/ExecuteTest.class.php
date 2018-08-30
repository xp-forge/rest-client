<?php namespace webservices\rest\unittest;

use lang\ClassLoader;
use peer\http\HttpConnection;
use unittest\TestCase;
use webservices\rest\Endpoint;

class ExecuteTest extends TestCase {

  #[@test]
  public function get() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) { return new TestConnection($uri); });

    $response= $fixture->resource('/test')->get();
    $this->assertEquals("GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n", $response->content());
  }

  #[@test]
  public function get_with_parameters() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) { return new TestConnection($uri); });

    $response= $fixture->resource('/')->get(['username' => 'test']);
    $this->assertEquals("GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n", $response->content());
  }

  #[@test]
  public function get_with_parameters_in_resource() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) { return new TestConnection($uri); });

    $response= $fixture->resource('/?username={0}', ['test'])->get();
    $this->assertEquals("GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n", $response->content());
  }
}