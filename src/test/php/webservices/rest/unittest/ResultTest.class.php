<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use unittest\{Assert, AssertionFailedError, Test, Values};
use util\data\Marshalling;
use webservices\rest\format\{Json, Unsupported};
use webservices\rest\io\Reader;
use webservices\rest\{Result, RestResponse, UnexpectedStatus};

class ResultTest {

  /** Returns a textual response */
  private function text(string $body) {
    return [
      ['Content-Type' => 'text/plain'],
      new Reader(new MemoryInputStream($body), new Unsupported('text/plain'), new Marshalling())
    ];
  }

  /** Returns a JSON response */
  private function json(string $body) {
    return [
      ['Content-Type' => 'application/json'],
      new Reader(new MemoryInputStream($body), new Json(), new Marshalling())
    ];
  }

  #[Test]
  public function can_create() {
    new Result(new RestResponse(200, 'OK'));
  }

  #[Test]
  public function value_on_success() {
    $response= new RestResponse(200, 'OK', ...$this->json('{"key":"value"}'));
    Assert::equals(['key' => 'value'], (new Result($response))->value());
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 404 (Not Found)')]
  public function value_on_error() {
    $response= new RestResponse(404, 'Not Found', ...$this->json('{"error":"No such test #0"}'));
    (new Result($response))->value();
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 302 (Found)')]
  public function value_on_redirect() {
    $response= new RestResponse(302, 'Found', ['Location' => 'http://example.org/']);
    (new Result($response))->value();
  }

  #[Test]
  public function access_error() {
    $response= new RestResponse(404, 'Not Found', ...$this->json('{"error":"No such test #0"}'));
    try {
      (new Result($response))->value();
      throw new AssertionFailedError('No exception raised');
    } catch (UnexpectedStatus $e) {
      Assert::equals(404, $e->status());
      Assert::equals(['error' => 'No such test #0'], $e->error());
    }
  }

  #[Test]
  public function error_is_null_for_successful_requests() {
    $response= new RestResponse(200, 'OK', ...$this->json('{"key":"value"}'));
    Assert::null((new Result($response))->error());
  }

  #[Test]
  public function error_unserialized_from_response() {
    $response= new RestResponse(404, 'Not Found', ...$this->json('{"error":"No such test #0"}'));
    Assert::equals(['error' => 'No such test #0'], (new Result($response))->error());
  }

  #[Test]
  public function error_for_raw_response() {
    $response= new RestResponse(504, 'Gateway Timeout', ...$this->text('Could not reach database'));
    Assert::equals('Could not reach database', (new Result($response))->error());
  }

  #[Test]
  public function optional_on_success() {
    $response= new RestResponse(200, 'OK', ...$this->json('{"key":"value"}'));
    Assert::equals(['key' => 'value'], (new Result($response))->optional());
  }

  #[Test]
  public function optional_on_404() {
    $response= new RestResponse(404, 'Not Found', ...$this->json('{"error":"No such test #0"}'));
    Assert::null((new Result($response))->optional());
  }

  #[Test]
  public function optional_on_supplied_status_code() {
    $response= new RestResponse(406, 'Not Acceptable', ...$this->json('{"error":"This is an XML-free API"}'));
    Assert::null((new Result($response))->optional(null, [404, 406]));
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 504 (Gateway Timeout)')]
  public function optional_on_error() {
    $response= new RestResponse(504, 'Gateway Timeout', ...$this->text('Could not reach database'));
    (new Result($response))->value();
  }

  #[Test, Values([['0', false], ['false', false], ['""', false], ['1', true], ['true', true], ['"1"', true]])]
  public function value_type_coercion($body, $result) {
    $response= new RestResponse(200, 'OK', ...$this->json($body));
    Assert::equals($result, (new Result($response))->value('bool'));
  }

  #[Test, Values([['0', false], ['false', false], ['""', false], ['1', true], ['true', true], ['"1"', true]])]
  public function optional_type_coercion($body, $result) {
    $response= new RestResponse(200, 'OK', ...$this->json($body));
    Assert::equals($result, (new Result($response))->optional('bool'));
  }
}