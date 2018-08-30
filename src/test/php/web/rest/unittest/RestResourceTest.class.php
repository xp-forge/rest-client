<?php namespace web\rest\unittest;

use lang\ElementNotFoundException;
use unittest\TestCase;
use web\rest\Endpoint;
use web\rest\RestRequest;
use web\rest\RestResource;
use web\rest\RestResponse;

class RestResourceTest extends TestCase {
  private $endpoint;

  /** @return void */
  public function setUp() {
    $this->endpoint= newinstance(Endpoint::class, ['https://api.example.com/'], [
      'sent'    => [],
      'execute' => function(RestRequest $request) {
        $this->sent[]= $request;
        return new RestResponse(200, 'OK');
      }
    ]);
  }

  #[@test]
  public function can_create() {
    new RestResource($this->endpoint, '/users');
  }

  #[@test]
  public function can_create_with_named_segment() {
    new RestResource($this->endpoint, '/users/{id}', ['id' => 6100]);
  }

  #[@test]
  public function can_create_with_positional_segment() {
    new RestResource($this->endpoint, '/users/{0}', [6100]);
  }

  #[@test, @expect(class= ElementNotFoundException::class, withMessage= 'No such segment "id"')]
  public function missing_segment_raises_error() {
    new RestResource($this->endpoint, '/users/{id}');
  }

  #[@test]
  public function get() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->get();

    $this->assertEquals([new RestRequest('GET', '/users')], $this->endpoint->sent);
  }

  #[@test]
  public function get_with_parameters() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->get(['active' => 'true']);

    $this->assertEquals(
      [(new RestRequest('GET', '/users'))->passing(['active' => 'true'])],
      $this->endpoint->sent
    );
  }

  #[@test]
  public function head() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->head();

    $this->assertEquals([new RestRequest('HEAD', '/users')], $this->endpoint->sent);
  }

  #[@test]
  public function head_with_parameters() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->head(['active' => 'true']);

    $this->assertEquals(
      [(new RestRequest('HEAD', '/users'))->passing(['active' => 'true'])],
      $this->endpoint->sent
    );
  }

  #[@test]
  public function post() {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->post(['name' => 'Tester'], 'application/json');

    $this->assertEquals(
      [(new RestRequest('POST', '/users'))->with(['Content-Type' => 'application/json'])->transfer(['name' => 'Tester'])],
      $this->endpoint->sent
    );
  }

  #[@test, @values([
  #  [[]],
  #  [['X-User-ID' => 6100]],
  #  [['X-User-ID' => 6100, 'Accept' => 'application/json;q=0.8']],
  #])]
  public function with_headers($headers) {
    $resource= new RestResource($this->endpoint, '/users');
    $resource->with($headers)->get();

    $this->assertEquals(
      [(new RestRequest('GET', '/users'))->with($headers)],
      $this->endpoint->sent
    );
  }
}