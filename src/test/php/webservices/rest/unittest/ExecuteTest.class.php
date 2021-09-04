<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use lang\ClassLoader;
use peer\ConnectException;
use peer\http\{Authorization, HttpConnection, HttpRequest, HttpResponse};
use unittest\{Assert, Expect, Test};
use util\log\layout\PatternLayout;
use util\log\{BufferedAppender, Logging};
use webservices\rest\{Endpoint, RestException, RestUpload};

class ExecuteTest {

  /** Returns a new endpoint using the `TestConnection` class */
  private function newFixture() {
    return (new Endpoint('http://test'))->connecting([TestConnection::class, 'new']);
  }

  #[Test]
  public function get() {
    $resource= $this->newFixture()->resource('/test');
    Assert::equals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_parameters() {
    $resource= $this->newFixture()->resource('/');
    Assert::equals(
      "GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $resource->get(['username' => 'test'])->content()
    );
  }

  #[Test]
  public function get_with_parameters_in_resource() {
    $resource= $this->newFixture()->resource('/?username={0}', ['test']);
    Assert::equals(
      "GET /?username=test HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_cookies() {
    $resource= $this->newFixture()->resource('/')->including(['session' => '0x6100', 'lang' => 'de']);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_cookies_merges_cookie_header() {
    $resource= $this->newFixture()->resource('/')->with(['Cookie' => 'session=0x6100'])->including(['lang' => 'de']);
    Assert::equals(
      "GET / HTTP/1.1\r\nConnection: close\r\nHost: test\r\nCookie: session=0x6100; lang=de\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_header() {
    $resource= $this->newFixture()->with('User-Agent', 'XP')->resource('/test');
    Assert::equals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nUser-Agent: XP\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_headers() {
    $resource= $this->newFixture()->with(['User-Agent' => 'XP'])->resource('/test');
    Assert::equals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nUser-Agent: XP\r\n\r\n",
      $resource->get()->content()
    );
  }

  #[Test]
  public function get_with_authorization() {
    $authorization= new class() extends Authorization {
      public function sign(HttpRequest $request) {
        $request->setHeader('Authorization', 'OAuth Bearer TOKEN');
      }
    };
    $response= $this->newFixture()->with('Authorization', $authorization)->resource('/test')->get();
    Assert::equals(
      "GET /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nAuthorization: OAuth Bearer TOKEN\r\n\r\n",
      $response->content()
    );
  }

  #[Test, Values(['POST', 'PUT'])]
  public function upload($method) {
    $response= $this->newFixture()->resource('/test')->upload($method)
      ->pass('submit', 'true')
      ->transfer('upload', new MemoryInputStream('Test'), 'test.txt', 'text/plain')
      ->finish()
    ;

    $expected= implode("\r\n", [
      '%1$s /test HTTP/1.1',
      'Connection: close',
      'Host: test',
      'Content-Type: multipart/form-data; boundary=%2$s',
      'Transfer-Encoding: chunked',
      '',
      '--%2$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'true',
      '--%2$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%2$s--',
      '',
    ]);
    Assert::equals(sprintf($expected, $method, RestUpload::BOUNDARY), $response->content());
  }

  #[Test, Values(['POST', 'PUT'])]
  public function mimetype_detected_from_filename($method) {
    $response= $this->newFixture()->resource('/test')->upload($method)
      ->transfer('upload', new MemoryInputStream('Test'), 'test.txt')
      ->finish()
    ;

    $expected= implode("\r\n", [
      '%1$s /test HTTP/1.1',
      'Connection: close',
      'Host: test',
      'Content-Type: multipart/form-data; boundary=%2$s',
      'Transfer-Encoding: chunked',
      '',
      '--%2$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%2$s--',
      '',
    ]);
    Assert::equals(sprintf($expected, $method, RestUpload::BOUNDARY), $response->content());
  }

  #[Test, Values(['POST', 'PUT'])]
  public function stream($method) {
    $upload= $this->newFixture()->resource('/test')->upload($method);
    $stream= $upload->stream('upload', 'test.txt', 'text/plain');
    $stream->write('Test');
    $stream->close();
    $response= $upload->finish();

    $expected= implode("\r\n", [
      '%1$s /test HTTP/1.1',
      'Connection: close',
      'Host: test',
      'Content-Type: multipart/form-data; boundary=%2$s',
      'Transfer-Encoding: chunked',
      '',
      '--%2$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%2$s--',
      '',
    ]);
    Assert::equals(sprintf($expected, $method, RestUpload::BOUNDARY), $response->content());
  }

  #[Test, Values(['POST', 'PUT'])]
  public function write_to_stream_without_closing($method) {
    $upload= $this->newFixture()->resource('/test')->upload($method);
    $upload->stream('upload', 'test.txt', 'text/plain')->write('Test');
    $response= $upload->finish();

    $expected= implode("\r\n", [
      '%1$s /test HTTP/1.1',
      'Connection: close',
      'Host: test',
      'Content-Type: multipart/form-data; boundary=%2$s',
      'Transfer-Encoding: chunked',
      '',
      '--%2$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%2$s--',
      '',
    ]);
    Assert::equals(sprintf($expected, $method, RestUpload::BOUNDARY), $response->content());
  }

  #[Test]
  public function logging() {
    $log= new BufferedAppender();

    $fixture= $this->newFixture();
    $fixture->setTrace(Logging::all()->using(new PatternLayout('%L %m%n'))->to($log));
    $fixture->resource('/users/0')->get();

    Assert::equals(
      "INFO >>> GET /users/0 HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\n".
      "INFO <<< HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 56\r\n\n".
      "DEBUG GET /users/0 HTTP/1.1\r\nConnection: close\r\nHost: test\r\n\r\n\n",
      $log->getBuffer()
    );
  }

  #[Test]
  public function logging_with_body() {
    $log= new BufferedAppender();

    $fixture= $this->newFixture();
    $fixture->setTrace(Logging::all()->using(new PatternLayout('%L %m%n'))->to($log));
    $fixture->resource('/test')->post(['test' => true], 'application/json');

    Assert::equals(
      "INFO >>> POST /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nContent-Type: application/json\r\nContent-Length: 13\r\n\n".
      "DEBUG {\"test\":true}\n".
      "INFO <<< HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 119\r\n\n".
      "DEBUG POST /test HTTP/1.1\r\nConnection: close\r\nHost: test\r\nContent-Type: application/json\r\nContent-Length: 13\r\n\r\n{\"test\":true}\n",
      $log->getBuffer()
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

    Assert::equals(403, $fixture->resource('/')->get()->status());
  }

  #[Test]
  public function use_streaming() {
    $resource= $this->newFixture()->resource('/test');
    Assert::equals(
      "POST /test HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: application/json\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "{\"test\":true}", // No chunk markers in body because we're using TestConnection
      $resource->post(['test' => true], 'application/json')->content()
    );
  }

  #[Test]
  public function use_buffering() {
    $resource= $this->newFixture()->buffered()->resource('/test');
    Assert::equals(
      "POST /test HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: application/json\r\n".
      "Content-Length: 13\r\n".
      "\r\n".
      "{\"test\":true}",
      $resource->post(['test' => true], 'application/json')->content()
    );
  }
}