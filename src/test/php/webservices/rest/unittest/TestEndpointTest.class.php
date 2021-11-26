<?php namespace webservices\rest\unittest;

use unittest\{Assert, Test};
use webservices\rest\{TestEndpoint, RestResponse};

class TestEndpointTest {

  #[Test]
  public function can_create() {
    new TestEndpoint([]);
  }

  #[Test]
  public function routed_path() {
    $fixture= new TestEndpoint([
      '/user/self' => function($call) {
        return $call->respond(307, 'Temporary Redirect', ['Location' => '/user/6100']);
      }
    ]);
    $r= $fixture->resource('/user/self')->get();

    Assert::equals(307, $r->status());
    Assert::equals('/user/6100', $r->header('Location'));
  }

  #[Test]
  public function routed_path_with_content() {
    $fixture= new TestEndpoint([
      '/user/6100' => function($call) {
        return $call->respond(200, 'OK', ['Content-Type' => 'application/json'], '{
          "id": 6100,
          "handle": "test"
        }');
      }
    ]);
    $r= $fixture->resource('/user/6100')->get();

    Assert::equals(200, $r->status());
    Assert::equals(['id' => 6100, 'handle' => 'test'], $r->value());
  }

  #[Test]
  public function routed_path_with_method() {
    $fixture= new TestEndpoint([
      'DELETE /user/1' => function($call) {
        return $call->respond(204, 'No content');
      }
    ]);
    $r= $fixture->resource('/user/1')->delete();

    Assert::equals(204, $r->status());
    Assert::equals('', $r->content());
  }

  #[Test]
  public function yields_404_for_unrouted_paths() {
    $fixture= new TestEndpoint([]);
    Assert::equals(404, $fixture->resource('/')->get()->status());
  }
}