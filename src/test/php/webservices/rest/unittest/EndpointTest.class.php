<?php namespace webservices\rest\unittest;

use lang\Error;
use lang\FormatException;
use lang\IllegalArgumentException;
use peer\URL;
use unittest\TestCase;
use unittest\actions\RuntimeVersion;
use util\URI;
use webservices\rest\Endpoint;
use webservices\rest\RestResource;

class EndpointTest extends TestCase {
  const BASE_URL = 'https://api.example.com/';

  /**
   * Creates a new Endpoint fixture with a given base
   *
   * @param  string|utio.URI|peer.URL $base
   * @return web.rest.Endpoint
   */
  private function newFixture($base= self::BASE_URL) {
    return new Endpoint($base);
  }

  #[@test, @values([self::BASE_URL, new URL(self::BASE_URL)])]
  public function can_create($base) {
    $this->newFixture($base);
  }

  #[@test, @values([null, '']), @expect(FormatException::class)]
  public function cannot_create_with_illegal_url($base) {
    $this->newFixture($base);
  }

  #[@test, @values([self::BASE_URL, new URL(self::BASE_URL)])]
  public function base($base) {
    $this->assertEquals(new URI(self::BASE_URL), $this->newFixture($base)->base());
  }

  #[@test]
  public function resource() {
    $this->assertInstanceOf(RestResource::class, $this->newFixture()->resource('/users'));
  }

  #[@test]
  public function resource_with_named_segment() {
    $this->assertInstanceOf(RestResource::class, $this->newFixture()->resource('/users/{id}', ['id' => 6100]));
  }

  #[@test]
  public function resource_with_positional_segment() {
    $this->assertInstanceOf(RestResource::class, $this->newFixture()->resource('/users/{0}', [6100]));
  }

  #[@test, @action(new RuntimeVersion('>=7.0.0')), @expect(Error::class)]
  public function execute_given_illegal_argument7() {
    $this->newFixture()->execute(null);
  }

  #[@test, @action(new RuntimeVersion('<7.0.0')), @expect(IllegalArgumentException::class)]
  public function execute_given_illegal_argument() {
    $this->newFixture()->execute(null);
  }
}