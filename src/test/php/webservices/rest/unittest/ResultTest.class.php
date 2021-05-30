<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use unittest\{Assert, Test};
use util\data\Marshalling;
use webservices\rest\format\{Json, Unsupported};
use webservices\rest\io\Reader;
use webservices\rest\{Result, RestResponse, UnexpectedError};

class ResultTest {
  const TEXT= ['Content-Type' => 'text/plain'];
  const JSON= ['Content-Type' => 'application/json'];

  /** Returns a textual response */
  private function text($body) {
    return new Reader(new MemoryInputStream($body), new Unsupported('text/plain'), new Marshalling());
  }

  /** Returns a JSON response */
  private function json($body) {
    return new Reader(new MemoryInputStream($body), new Json(), new Marshalling());
  }


  #[Test]
  public function can_create() {
    new Result(new RestResponse(200, 'OK'));
  }

  #[Test]
  public function value_on_success() {
    $response= new RestResponse(200, 'OK', self::JSON, $this->json('{"key":"value"}'));
    Assert::equals(['key' => 'value'], (new Result($response))->value());
  }

  #[Test, Expect(class: UnexpectedError::class, withMessage: 'Unexpected 404 (Not Found)')]
  public function value_on_error() {
    $response= new RestResponse(404, 'Not Found', self::JSON, $this->json('{"error":"No such test #0"}'));
    (new Result($response))->value();
  }

  #[Test]
  public function error_is_null_for_successful_requests() {
    $response= new RestResponse(200, 'OK', self::JSON, $this->json('{"key":"value"}'));
    Assert::null((new Result($response))->error());
  }

  #[Test]
  public function error_unserialized_from_response() {
    $response= new RestResponse(404, 'Not Found', self::JSON, $this->json('{"error":"No such test #0"}'));
    Assert::equals(['error' => 'No such test #0'], (new Result($response))->error());
  }

  #[Test]
  public function error_for_raw_response() {
    $response= new RestResponse(504, 'Gateway Timeout', self::TEXT, $this->text('Could not reach database'));
    Assert::equals('Could not reach database', (new Result($response))->error());
  }
}