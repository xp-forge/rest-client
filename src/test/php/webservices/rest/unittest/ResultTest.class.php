<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use unittest\{Assert, AssertionFailedError, Test, Values};
use util\URI;
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

  /** Returns a link header */
  private function link($links) {
    $r= '';
    foreach ($links as $rel => $uri) {
      $r.= sprintf(', <https://example.org/%s>; rel="%s"', ltrim($uri, '/'), $rel);
    }
    return ['Link' => substr($r, 2)];
  }

  /** @return iterable */
  private function creation() {
    yield new RestResponse(200, 'OK', ...$this->json('{"id":6100,"value":"Created"}'));
    yield new RestResponse(201, 'Created', ['Location' => 'https://example.org/test/6100']);
  }

  #[Test]
  public function can_create() {
    new Result(new RestResponse(200, 'OK'));
  }

  #[Test]
  public function status() {
    $response= new RestResponse(200, 'OK');
    Assert::equals(200, (new Result($response))->status());
  }

  #[Test]
  public function location_on_creation() {
    $response= new RestResponse(201, 'Created', ['Location' => 'https://example.org/test/6100']);
    Assert::equals(new URI('https://example.org/test/6100'), (new Result($response))->location());
  }

  #[Test]
  public function location_resolved() {
    $response= new RestResponse(201, 'Created', ['Location' => '/test/6100'], null, 'https://example.org/');
    Assert::equals(new URI('https://example.org/test/6100'), (new Result($response))->location());
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 200 (OK)')]
  public function location_without_header() {
    $response= new RestResponse(200, 'OK', ...$this->json('{"id":6100}'));
    (new Result($response))->location();
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 422 (Unprocessable Entity)')]
  public function location_on_error() {
    $response= new RestResponse(422, 'Unprocessable Entity', ...$this->json('{"error":"Validation failed"}'));
    (new Result($response))->location();
  }

  #[Test]
  public function next_link() {
    $response= new RestResponse(200, 'OK', $this->link(['next' => '/users?page=2']));
    Assert::equals(new URI('https://example.org/users?page=2'), (new Result($response))->link('next'));
  }

  #[Test]
  public function next_link_resolved() {
    $response= new RestResponse(200, 'OK', ['Link' => '</>; rel="home"'], null, 'https://example.org/test/6100');
    Assert::equals(new URI('https://example.org/'), (new Result($response))->link('home'));
  }

  #[Test]
  public function no_next_link() {
    $response= new RestResponse(200, 'OK', $this->link(['prev' => '/users?page=1']));
    Assert::null((new Result($response))->link('next'));
  }

  #[Test]
  public function links() {
    $response= new RestResponse(200, 'OK', $this->link(['prev' => '/users?page=1', 'next' => '/users?page=3']));
    Assert::equals(
      [
        'prev' => new URI('https://example.org/users?page=1'),
        'next' => new URI('https://example.org/users?page=3')
      ],
      (new Result($response))->links()
    );
  }

  #[Test]
  public function no_links() {
    $response= new RestResponse(200, 'OK', []);
    Assert::equals([], (new Result($response))->links());
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 404 (Not Found)')]
  public function link_on_error() {
    $response= new RestResponse(404, 'Not Found', ...$this->json('{"error":"No such resource"}'));
    (new Result($response))->link('next');
  }

  #[Test]
  public function match_204() {
    $response= new RestResponse(204, 'No Content');
    Assert::true((new Result($response))->match([204 => true]));
  }

  #[Test]
  public function match_304() {
    $response= new RestResponse(304, 'Not Modified');
    Assert::null((new Result($response))->match([304 => null]));
  }

  #[Test, Values('creation')]
  public function match_200_or_201($response) {
    Assert::equals(6100, (new Result($response))->match([
      200 => function($r) { return $r->value()['id']; },
      201 => function($r) { return (int)basename($r->location()); }
    ]));
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 403 (Forbidden)')]
  public function match_on_error() {
    $response= new RestResponse(403, 'Forbidden');
    (new Result($response))->match([204 => true]);
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
    $response= new RestResponse(302, 'Found', ['Location' => 'https://example.org/']);
    (new Result($response))->value();
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

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 302 (Found)')]
  public function optional_on_redirect() {
    $response= new RestResponse(302, 'Found', ['Location' => 'https://example.org/']);
    (new Result($response))->optional();
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

  #[Test]
  public function access_reason_of_unexpected_status() {
    $response= new RestResponse(404, 'Not Found', ...$this->json('{"error":"No such test #0"}'));
    try {
      (new Result($response))->value();
      throw new AssertionFailedError('No exception raised');
    } catch (UnexpectedStatus $e) {
      Assert::equals(404, $e->status());
      Assert::equals(['error' => 'No such test #0'], $e->reason());
    }
  }
}