<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use unittest\{Assert, Test};
use util\data\Marshalling;
use webservices\rest\format\Json;
use webservices\rest\io\Reader;
use webservices\rest\{Result, RestResponse, UnexpectedError};

class ResultTest {
  const JSON = ['Content-Type' => 'application/json'];

  private function reader($body) {
    return new Reader(new MemoryInputStream($body), new Json(), new Marshalling());
  }

  #[Test]
  public function can_create() {
    new Result(new RestResponse(200, 'OK'));
  }

  #[Test]
  public function value_on_succes() {
    $response= new RestResponse(200, 'OK', self::JSON, $this->reader('{"key":"value"}'));
    Assert::equals(['key' => 'value'], (new Result($response))->value());
  }

  #[Test, Expect(class: UnexpectedError::class, withMessage: 'Unexpected 404 (Not Found)')]
  public function value_on_error() {
    $response= new RestResponse(404, 'Not Found', self::JSON, $this->reader('{"error":"No such test #0"}'));
    (new Result($response))->value();
  }
}