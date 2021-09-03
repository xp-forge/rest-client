<?php namespace webservices\rest\unittest;

use lang\ElementNotFoundException;
use unittest\{Assert, Expect, Test, Values};
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

  #[Test, Expect(['class' => ElementNotFoundException::class, 'withMessage' => 'No such segment "id"'])]
  public function missing_segment_raises_error() {
    new RestResource($this->endpoint(), '/users/{id}');
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

  #[Test, Values([[[]], [['X-User-ID' => 6100]], [['X-User-ID' => 6100, 'Accept' => 'application/json;q=0.8']],])]
  public function with_headers($headers) {
    $endpoint= $this->endpoint();
    $resource= new RestResource($endpoint, '/users');
    $resource->with($headers)->get();

    Assert::equals([(new RestRequest('GET', '/users'))->with($headers)], $endpoint->sent);
  }

  #[Test, Values('cookies')]
  public function including_cookies($cookies) {
    $endpoint= $this->endpoint();
    $resource= new RestResource($endpoint, '/users');
    $resource->including($cookies)->get();

    Assert::equals([(new RestRequest('GET', '/users'))->including($cookies)], $endpoint->sent);
  }
}