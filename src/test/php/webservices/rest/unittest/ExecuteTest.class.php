<?php namespace webservices\rest\unittest;

use lang\ClassLoader;
use peer\http\HttpConnection;
use unittest\TestCase;
use webservices\rest\Endpoint;

class ExecuteTest extends TestCase {

  #[@test]
  public function get() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) { return new TestConnection($uri); });

    $response= $fixture->resource('/test');
    $this->assertEquals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $response->get()->content()
    );
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

    $resource= $fixture->resource('/?username={0}', ['test']);
    $this->assertEquals(
      "GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[@test]
  public function get_with_cookies() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) { return new TestConnection($uri); });

    $resource= $fixture->resource('/')->including(['session' => '0x6100', 'lang' => 'de']);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[@test]
  public function get_with_cookies_merges_cookie_header() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) { return new TestConnection($uri); });

    $resource= $fixture->resource('/')->with(['Cookie' => 'session=0x6100'])->including(['lang' => 'de']);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }
}