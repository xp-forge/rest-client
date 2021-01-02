<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use lang\ClassLoader;
use peer\ConnectException;
use peer\http\{HttpConnection, HttpRequest, HttpResponse, Authorization};
use unittest\{Expect, Test, TestCase};
use util\log\{BufferedAppender, Logging};
use webservices\rest\{Endpoint, RestException};

class ExecuteTest extends TestCase {

  #[Test]
  public function get() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $response= $fixture->resource('/test')->get();
    $this->assertEquals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $response->content()
    );
  }

  #[Test]
  public function get_with_parameters() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $response= $fixture->resource('/')->get(['username' => 'test']);
    $this->assertEquals("GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n", $response->content());
  }

  #[Test]
  public function get_with_parameters_in_resource() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $resource= $fixture->resource('/?username={0}', ['test']);
    $this->assertEquals(
      "GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_cookies() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $resource= $fixture->resource('/')->including(['session' => '0x6100', 'lang' => 'de']);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_cookies_merges_cookie_header() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);

    $resource= $fixture->resource('/')->with(['Cookie' => 'session=0x6100'])->including(['lang' => 'de']);
    $this->assertEquals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_header() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new'])->with('User-Agent', 'XP');

    $response= $fixture->resource('/test')->get();
    $this->assertEquals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nUser-Agent: XP\r\n\r\n",
      $response->content()
    );
  }

  #[Test]
  public function get_with_headers() {
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new'])->with(['User-Agent' => 'XP']);

    $response= $fixture->resource('/test')->get();
    $this->assertEquals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nUser-Agent: XP\r\n\r\n",
      $response->content()
    );
  }

  #[Test]
  public function get_with_authorization() {
    $authorization= new class() extends Authorization {
      public function sign($request) {
        $request->setHeader('Authorization', 'OAuth Bearer TOKEN');
      }
    };
    $fixture= (new Endpoint('http://test'))->connecting([TestConnection::class, 'new'])->with('Authorization', $authorization);

    $response= $fixture->resource('/test')->get();
    $this->assertEquals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nAuthorization: OAuth Bearer TOKEN\r\n\r\n",
      $response->content()
    );
  }

  #[Test]
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

  #[Test, Expect(RestException::class)]
  public function exceptions_from_sending_requests_are_wrapped() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) {
      return newinstance(HttpConnection::class, [$uri], [
        'send' => function(HttpRequest $req) { throw new ConnectException('Test'); }
      ]);
    });

    $fixture->resource('/fails')->get();
  }

  #[Test]
  public function handles_responses_without_content_type() {
    $fixture= (new Endpoint('http://test'))->connecting(function($uri) {
      return newinstance(HttpConnection::class, [$uri], [
        'send' => function(HttpRequest $req) {
          return new HttpResponse(new MemoryInputStream("HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n"));
        }
      ]);
    });

    $this->assertEquals(403, $fixture->resource('/')->get()->status());
  }
}