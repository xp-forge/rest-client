<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use test\{Assert, Test, Values};
use webservices\rest\{RestResponse, TestEndpoint};

class TestEndpointTest {

  #[Test]
  public function can_create() {
    new TestEndpoint([]);
  }

  #[Test, Values(['/users/self', 'users/self'])]
  public function routed_path($resource) {
    $fixture= new TestEndpoint([
      '/users/self' => function($call) {
        return $call->respond(307, 'Temporary Redirect', ['Location' => '/users/6100']);
      }
    ]);
    $r= $fixture->resource($resource)->get();

    Assert::equals(307, $r->status());
    Assert::equals('/users/6100', $r->header('Location'));
  }

  #[Test, Values(['/api/users/self', 'users/self'])]
  public function routed_path_under_base($resource) {
    $fixture= new TestEndpoint([
      '/api/users/self' => function($call) {
        return $call->respond(307, 'Temporary Redirect', ['Location' => '/api/users/6100']);
      }
    ], '/api');
    $r= $fixture->resource($resource)->get();

    Assert::equals(307, $r->status());
    Assert::equals('/api/users/6100', $r->header('Location'));
  }

  #[Test]
  public function routed_path_with_content() {
    $fixture= new TestEndpoint([
      '/users/6100' => function($call) {
        return $call->respond(200, 'OK', ['Content-Type' => 'application/json'], '{
          "id": 6100,
          "handle": "test"
        }');
      }
    ]);
    $r= $fixture->resource('/users/6100')->get();

    Assert::equals(200, $r->status());
    Assert::equals(['id' => 6100, 'handle' => 'test'], $r->value());
  }

  #[Test]
  public function routed_path_with_upload() {
    $fixture= new TestEndpoint([
      '/users/6100/picture' => function($call) {
        return $call->respond(202, 'Accepted');
      }
    ]);
    $r= $fixture->resource('/users/6100/picture')->upload()
      ->transfer('picture', new MemoryInputStream('...'), 'image.jpg')
      ->finish()
    ;

    Assert::equals(202, $r->status());
  }

  #[Test]
  public function routed_path_with_method() {
    $fixture= new TestEndpoint([
      'DELETE /users/1' => function($call) {
        return $call->respond(204, 'No content');
      }
    ]);
    $r= $fixture->resource('/users/1')->delete();

    Assert::equals(204, $r->status());
    Assert::equals('', $r->content());
  }

  #[Test]
  public function routed_path_with_segment() {
    $fixture= new TestEndpoint([
      '/users/{id}' => function($call, $segments) {
        return $call->respond(200, 'OK', ['Content-Type' => 'application/json'], '{
          "id": '.(int)$segments['id'].',
          "handle": "test"
        }');
      }
    ]);
    $r= $fixture->resource('/users/6100')->get();

    Assert::equals(200, $r->status());
    Assert::equals(['id' => 6100, 'handle' => 'test'], $r->value());
  }

  #[Test]
  public function yields_404_for_unrouted_paths() {
    $fixture= new TestEndpoint([]);
    Assert::equals(404, $fixture->resource('/')->get()->status());
  }

  #[Test]
  public function content() {
    $fixture= new TestEndpoint([
      'POST /users' => function($call) {
        $payload= json_decode($call->content(), true);
        return $call->respond(201, 'Created', ['Location' => '/users/'.md5($payload['name'])]);
      }
    ]);
    $r= $fixture->resource('/users')->post(['name' => 'Test'], 'application/json');

    Assert::equals(201, $r->status());
    Assert::equals('/users/'.md5('Test'), $r->headers()['Location']);
  }

}