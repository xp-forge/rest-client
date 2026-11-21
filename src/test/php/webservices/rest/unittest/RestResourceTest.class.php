<?php namespace webservices\rest\unittest;

use lang\ElementNotFoundException;
use test\{Assert, Expect, Test, Values};
use util\URI;
use webservices\rest\{Cookie, Cookies, Endpoint, RestRequest, RestResource, RestResponse};

class RestResourceTest {

  /** @return webservices.rest.Endpoint */
  private function endpoint() {
    return new class('https://api.example.com/') extends Endpoint {
      public $sent= [];
      public function execute(RestRequest $request) {
        $this->sent[]= $request;
        return new RestResponse(200, 'OK');
      }
    };
  }

  /** @return iterable */
  private function cookies() {
    yield [['lang' => 'de', 'uid' => 6100, 'not-sent' => null]];
    yield [[new Cookie('lang', 'de'), new Cookie('uid',  6100), new Cookie('not-sent', null)]];
    yield [new Cookies(['lang' => 'de', 'uid' => 6100, 'not-sent' => null])];
    yield [new Cookies([new Cookie('lang', 'de'), new Cookie('uid',  6100), new Cookie('not-sent', null)])];
  }

  #[Test]
  public function can_create() {
    new RestResource($this->endpoint(), '/users');
  }

  #[Test]
  public function can_create_with_named_segment() {
    new RestResource($this->endpoint(), '/users/{id}', ['id' => 6100]);
  }

  #[Test]
  public function can_create_with_positional_segment() {
    new RestResource($this->endpoint(), '/users/{0}', [6100]);
  }

  #[Test, Expect(class: ElementNotFoundException::class, message: 'No such segment "id"')]
  public function missing_segment_raises_error() {
    new RestResource($this->endpoint(), '/users/{id}');
  }

  #[Test]
  public function uri() {
    Assert::equals(
      new URI('https://api.example.com/users'),
      (new RestResource($this->endpoint(), '/users'))->uri()
    );
  }

  #[Test]
  public function uri_with_segments() {
    Assert::equals(
      new URI('https://api.example.com/users/6100/avatar/self'),
      (new RestResource($this->endpoint(), '/users/{0}/avatar/{1}', [6100, 'self']))->uri()
    );
  }

  #[Test]
  public function headers_empty_by_default() {
    Assert::equals([], (new RestResource($this->endpoint(), '/'))->request('GET')->headers());
  }

  #[Test, Values([[[]], [['X-User-ID' => 6100]], [['X-User-ID' => 6100, 'Accept' => 'application/json;q=0.8']],])]
  public function with_headers($headers) {
    Assert::equals($headers, (new RestResource($this->endpoint(), '/'))->with($headers)->request('GET')->headers());
  }

  #[Test]
  public function reset_headers() {
    Assert::equals([], (new RestResource($this->endpoint(), '/'))
      ->with(['X-User-ID' => 6100])
      ->with(null)
      ->request('GET')
      ->headers()
    );
  }

  #[Test]
  public function cookies_empty_by_default() {
    Assert::equals(
      new Cookies([]),
      (new RestResource($this->endpoint(), '/'))->request('GET')->cookies()
    );
  }

  #[Test, Values(from: 'cookies')]
  public function including_cookies($cookies) {
    Assert::equals(
      new Cookies([new Cookie('lang', 'de'), new Cookie('uid',  6100)]),
      (new RestResource($this->endpoint(), '/'))->including($cookies)->request('GET')->cookies()
    );
  }

  #[Test]
  public function sending_type() {
    Assert::equals(
      ['Content-Type' => 'text/plain'],
      (new RestResource($this->endpoint(), '/'))->sending('text/plain')->request('GET')->headers()
    );
  }

  #[Test]
  public function accepting_type() {
    Assert::equals(
      ['Accept' => 'text/plain'],
      (new RestResource($this->endpoint(), '/'))->accepting('text/plain')->request('GET')->headers()
    );
  }

  #[Test]
  public function accepting_types() {
    Assert::equals(
      ['Accept' => ['text/plain', 'text/*']],
      (new RestResource($this->endpoint(), '/'))->accepting(['text/plain', 'text/*'])->request('GET')->headers()
    );
  }

  #[Test]
  public function accepting_type_with_qvalue() {
    Assert::equals(
      ['Accept' => 'text/plain;q=0.8'],
      (new RestResource($this->endpoint(), '/'))->accepting('text/plain', 0.8)->request('GET')->headers()
    );
  }

  #[Test]
  public function accepting_types_with_qvalues() {
    Assert::equals(
      ['Accept' => ['text/plain;q=1', 'text/*;q=0.8']],
      (new RestResource($this->endpoint(), '/'))->accepting(['text/plain' => 1.0, 'text/*' => 0.8])->request('GET')->headers()
    );
  }

  #[Test]
  public function reset_type() {
    Assert::equals([], (new RestResource($this->endpoint(), '/'))
      ->sending('text/plain')
      ->sending(null)
      ->request('GET')
      ->headers()
    );
  }

  #[Test]
  public function reset_accept() {
    Assert::equals([], (new RestResource($this->endpoint(), '/'))
      ->accepting('text/plain')
      ->accepting(null)
      ->request('GET')
      ->headers()
    );
  }

  #[Test]
  public function get() {
    $endpoint= $this->endpoint();
    $resource= new RestResource($endpoint, '/users');
    $resource->get();

    Assert::equals([new RestRequest('GET', '/users')], $endpoint->sent);
  }

  #[Test]
  public function get_with_parameters() {
    $endpoint= $this->endpoint();
    $resource= new RestResource($endpoint, '/users');
    $resource->get(['active' => 'true']);

    Assert::equals([(new RestRequest('GET', '/users'))->passing(['active' => 'true'])], $endpoint->sent);
  }

  #[Test]
  public function head() {
    $endpoint= $this->endpoint();
    $resource= new RestResource($endpoint, '/users');
    $resource->head();

    Assert::equals([new RestRequest('HEAD', '/users')], $endpoint->sent);
  }

  #[Test]
  public function head_with_parameters() {
    $endpoint= $this->endpoint();
    $resource= new RestResource($endpoint, '/users');
    $resource->head(['active' => 'true']);

    Assert::equals([(new RestRequest('HEAD', '/users'))->passing(['active' => 'true'])], $endpoint->sent);
  }

  #[Test]
  public function post() {
    $endpoint= $this->endpoint();
    $resource= new RestResource($endpoint, '/users');
    $resource->post(['name' => 'Tester'], 'application/json');

    Assert::equals(
      [(new RestRequest('POST', '/users'))->with(['Content-Type' => 'application/json'])->transfer(['name' => 'Tester'])],
      $endpoint->sent
    );
  }
}