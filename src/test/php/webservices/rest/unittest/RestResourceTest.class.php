<?php namespace webservices\rest\unittest;

use lang\ElementNotFoundException;
use unittest\{Expect, Test, TestCase, Values};
use webservices\rest\{Cookie, Cookies, Endpoint, RestRequest, RestResource, RestResponse};

class RestResourceTest extends TestCase {
  private $endpoint;

  /** @return void */
  public function setUp() {
    $this->endpoint= new class('https://api.example.com/') extends Endpoint {
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
    new RestResource($this->endpoint, '/users');
  }

  #[Test]
  public function can_create_with_named_segment() {
    new RestResource($this->endpoint, '/users/{id}', ['id' => 6100]);
  }

  #[Test]
  public function can_create_with_positional_segment() {
    new RestResource($this->endpoint, '/users/{0}', [6100]);
  }

  #[Test, Expect(['class' => ElementNotFoundException::class, 'withMessage' => 'No such segment "id"'])]
  public function missing_segment_raises_error() {
    new RestResource($this->endpoint, '/users/{id}');
  }

  #[Test]
  public function get() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->get();

    $this->assertEquals([new RestRequest('GET', '/users')], $this->endpoint->sent);
  }

  #[Test]
  public function get_with_parameters() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->get(['active' => 'true']);

    $this->assertEquals(
      [(new RestRequest('GET', '/users'))->passing(['active' => 'true'])],
      $this->endpoint->sent
    );
  }

  #[Test]
  public function head() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->head();

    $this->assertEquals([new RestRequest('HEAD', '/users')], $this->endpoint->sent);
  }

  #[Test]
  public function head_with_parameters() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->head(['active' => 'true']);

    $this->assertEquals(
      [(new RestRequest('HEAD', '/users'))->passing(['active' => 'true'])],
      $this->endpoint->sent
    );
  }

  #[Test]
  public function post() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->post(['name' => 'Tester'], 'application/json');

    $this->assertEquals(
      [(new RestRequest('POST', '/users'))->with(['Content-Type' => 'application/json'])->transfer(['name' => 'Tester'])],
      $this->endpoint->sent
    );
  }

  #[Test, Values([[[]], [['X-User-ID' => 6100]], [['X-User-ID' => 6100, 'Accept' => 'application/json;q=0.8']],])]
  public function with_headers($headers) {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->with($headers)->get();

    $this->assertEquals(
      [(new RestRequest('GET', '/users'))->with($headers)],
      $this->endpoint->sent
    );
  }

  #[Test, Values('cookies')]
  public function including_cookies($cookies) {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->including($cookies)->get();

    $this->assertEquals(
      [(new RestRequest('GET', '/users'))->including($cookies)],
      $this->endpoint->sent
    );
  }
}