<?php namespace webservices\rest\unittest;

use lang\ClassLoader;
use peer\http\HttpConnection;
use unittest\TestCase;
use util\log\BufferedAppender;
use util\log\Logging;
use webservices\rest\Endpoint;

class ExecuteTest extends TestCase {

  #[@test]
  public function get() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $response= $fixture->resource('/test')->get();
    $this->assertEquals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $response->content()
    );
  }

  #[@test]
  public function get_with_parameters() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $response= $fixture->resource('/')->get(['username' => 'test']);
    $this->assertEquals("GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n", $response->content());
  }

  #[@test]
  public function get_with_parameters_in_resource() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $resource= $fixture->resource('/?username={0}', ['test']);
    $this->assertEquals(
      "GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[@test]
  public function get_with_cookies() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $resource= $fixture->resource('/')->including(['session' => '0x6100', 'lang' => 'de']);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[@test]
  public function get_with_cookies_merges_cookie_header() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $resource= $fixture->resource('/')->with(['Cookie' => 'session=0x6100'])->including(['lang' => 'de']);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[@test]
  public function logging() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $log= new BufferedAppender();
    $fixture->setTrace(Logging::all()->to($log));

    $fixture->resource('/users/0')->get();

    $buf= $log->getBuffer();
    $this->assertEquals(
      ['req' => 1, 'res' => 1],
      ['req' => preg_match('~GET /users/0 HTTP/1\.1~', $buf), 'res' => preg_match('~HTTP/1\.1 200 OK~', $buf)],
      $buf
    );
  }
}