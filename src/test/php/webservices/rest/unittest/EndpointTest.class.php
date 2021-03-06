<?php namespace webservices\rest\unittest;

use lang\{Error, FormatException, IllegalArgumentException};
use peer\URL;
use unittest\{Expect, Test, TestCase, Values};
use util\URI;
use webservices\rest\{Endpoint, RestResource};

class EndpointTest extends TestCase {
  const BASE_URL = 'https://api.example.com/';

  /**
   * Creates a new Endpoint fixture with a given base
   *
   * @param  string|util.URI|peer.URL $base
   * @return web.rest.Endpoint
   */
  private function newFixture($base= self::BASE_URL) {
    return new Endpoint($base);
  }

  #[Test, Values(eval: '[self::BASE_URL, new URI(self::BASE_URL), new URL(self::BASE_URL)]')]
  public function can_create($base) {
    $this->newFixture($base);
  }

  #[Test, Values([null, '']), Expect(FormatException::class)]
  public function cannot_create_with_illegal_url($base) {
    $this->newFixture($base);
  }

  #[Test, Values(eval: '[self::BASE_URL, new URI(self::BASE_URL), new URL(self::BASE_URL)]')]
  public function base($base) {
    $this->assertEquals(new URI(self::BASE_URL), $this->newFixture($base)->base());
  }

  #[Test]
  public function headers_empty_by_default() {
    $this->assertEquals([], $this->newFixture()->headers());
  }

  #[Test]
  public function headers_added_via_with() {
    $this->assertEquals(['X-API-Key' => '6100'], $this->newFixture()->with('X-API-Key', '6100')->headers());
  }

  #[Test]
  public function resource() {
    $this->assertInstanceOf(RestResource::class, $this->newFixture()->resource('/users'));
  }

  #[Test]
  public function resource_with_named_segment() {
    $this->assertInstanceOf(RestResource::class, $this->newFixture()->resource('/users/{id}', ['id' => 6100]));
  }

  #[Test]
  public function resource_with_positional_segment() {
    $this->assertInstanceOf(RestResource::class, $this->newFixture()->resource('/users/{0}', [6100]));
  }

  #[Test, Expect(Error::class)]
  public function execute_given_illegal_argument() {
    $this->newFixture()->execute(null);
  }
}