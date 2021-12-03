<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use unittest\{Assert, Expect, Test, Values};
use util\URI;
use util\data\Marshalling;
use webservices\rest\format\{Json, Unsupported};
use webservices\rest\io\Reader;
use webservices\rest\{Cookie, Cookies, Link, RestResponse, UnexpectedStatus};

class RestResponseTest {
  const API = 'https://api.example.com/';

  /**
   * Creates headers and reader using JSON from a given source
   * 
   * @param  string $source
   * @return var[]
   */
  private function json($source) {
    return [
      ['Content-Type' => 'application/json'],
      new Reader(new MemoryInputStream($source), new Json(), new Marshalling())
    ];
  }

  #[Test]
  public function can_create() {
    new RestResponse(200, 'OK');
  }

  #[Test]
  public function can_create_with_headers() {
    new RestResponse(200, 'OK', ['Content-Type' => 'text/html']);
  }

  #[Test]
  public function can_create_with_reader() {
    new RestResponse(200, 'OK', ...$this->json('{}'));
  }

  #[Test, Values(eval: '["http://localhost/", new URI("http://localhost/")])]')]
  public function can_create_with_uri($uri) {
    new RestResponse(200, 'OK', [], null, $uri);
  }

  #[Test]
  public function status() {
    Assert::equals(200, (new RestResponse(200, 'OK'))->status());
  }

  #[Test]
  public function message() {
    Assert::equals('OK', (new RestResponse(200, 'OK'))->message());
  }

  #[Test]
  public function no_uri() {
    Assert::null((new RestResponse(200, 'OK'))->uri());
  }

  #[Test]
  public function uri() {
    Assert::equals(
      new URI('http://example.com/'),
      (new RestResponse(200, 'OK', [], null, 'http://example.com/'))->uri()
    );
  }

  #[Test, Values([[null, 'http://example.com/test/'], [self::API, self::API], ['/', 'http://example.com/'], ['/user/', 'http://example.com/user/']])]
  public function resolve_uri($uri, $resolved) {
    Assert::equals(
      new URI($resolved),
      (new RestResponse(200, 'OK', [], null, 'http://example.com/test/'))->resolve($uri)
    );
  }

  #[Test]
  public function stream() {
    $stream= new MemoryInputStream('...');
    $reader= new Reader($stream, new Json(), new Marshalling());
    Assert::equals($stream, (new RestResponse(200, 'OK', [], $reader))->stream());
  }

  #[Test]
  public function without_links() {
    Assert::equals([], (new RestResponse(200, 'OK', []))->links());
  }

  #[Test]
  public function links() {
    $links= '</search?page=1>;rel=prev, </search?page=3>;rel=next';
    Assert::equals(
      ['prev' => new URI('/search?page=1'), 'next' => new URI('/search?page=3')],
      (new RestResponse(200, 'OK', ['Link' => $links]))->links()
    );
  }

  #[Test]
  public function select_link_by_rel() {
    Assert::equals(
      new URI('meta.rdf'),
      (new RestResponse(200, 'OK', ['Link' => '<meta.rdf>;rel=meta']))->link('meta')
    );
  }

  #[Test]
  public function select_non_existant_link() {
    Assert::null((new RestResponse(200, 'OK', ['Link' => '<meta.rdf>;rel=meta']))->link('next'));
  }

  #[Test]
  public function select_link_when_no_link_header_is_present() {
    Assert::null((new RestResponse(200, 'OK', []))->link('next'));
  }

  #[Test]
  public function headers() {
    $headers= ['Content-Type' => 'text/html'];
    Assert::equals($headers, (new RestResponse(200, 'OK', $headers))->headers());
  }

  #[Test, Values(['content-type', 'Content-type', 'Content-Type'])]
  public function header($name) {
    $headers= ['Content-Type' => 'text/html'];
    Assert::equals('text/html', (new RestResponse(200, 'OK', $headers))->header($name));
  }

  #[Test]
  public function non_existant_header() {
    Assert::null((new RestResponse(200, 'OK', []))->header('Content-Type'));
  }

  #[Test]
  public function non_existant_header_uses_default() {
    $default= 'application/octet-stream';
    Assert::equals($default, (new RestResponse(200, 'OK', []))->header('Content-Type', $default));
  }

  #[Test]
  public function location_header() {
    $location= 'http://localhost/redirect';
    Assert::equals(new URI($location), (new RestResponse(200, 'OK', ['Location' => $location]))->location());
  }

  #[Test]
  public function non_existant_location_header() {
    Assert::null((new RestResponse(200, 'OK', []))->location());
  }

  #[Test]
  public function location_header_resolved() {
    Assert::equals(
      new URI('http://localhost/redirect'),
      (new RestResponse(200, 'OK', ['Location' => '/redirect'], null, 'http://localhost'))->location()
    );
  }

  #[Test]
  public function value_on_success() {
    Assert::equals(['key' => 'value'], (new RestResponse(200, 'OK', ...$this->json('{"key":"value"}')))->value());
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 400 (Bad Request)')]
  public function value_on_error() {
    (new RestResponse(400, 'Bad Request', ...$this->json('{"message":"Error"}')))->value();
  }

  #[Test]
  public function value_with_type_coercion() {
    Assert::equals(['key' => '200'], (new RestResponse(200, 'OK', ...$this->json('{"key":200}')))->value('[:string]'));
  }

  #[Test]
  public function optional_on_success() {
    Assert::equals(['key' => 'value'], (new RestResponse(200, 'OK', ...$this->json('{"key":"value"}')))->value());
  }

  #[Test]
  public function optional_on_error() {
    Assert::null((new RestResponse(404, 'Not Found', ...$this->json('{"error":"Unknown user"}')))->optional());
  }

  #[Test]
  public function optional_with_type_coercion() {
    Assert::equals(['key' => '200'], (new RestResponse(200, 'OK', ...$this->json('{"key":200}')))->optional('[:string]'));
  }

  #[Test]
  public function error_on_success() {
    Assert::null((new RestResponse(200, 'OK', ...$this->json('{"key":"value"}')))->error());
  }

  #[Test]
  public function error_on_error() {
    Assert::equals(
      ['error' => 'Unknown user'],
      (new RestResponse(404, 'Not Found', ...$this->json('{"error":"Unknown user"}')))->error()
    );
  }

  #[Test]
  public function error_with_type_coercion() {
    Assert::equals(
      ['error' => '6100'],
      (new RestResponse(404, 'Not Found', ...$this->json('{"error":6100}')))->error('[:string]')
    );
  }

  #[Test]
  public function error_and_value_on_success() {
    $response= new RestResponse(200, 'OK', ...$this->json('{"key":"value"}'));
    Assert::null($response->error());
    Assert::equals(['key' => 'value'], $response->value());
  }

  #[Test]
  public function error_with_unsupported_format() {
    $reader= new Reader(new MemoryInputStream('Unknown user'), new Unsupported('text/plain'), new Marshalling());
    Assert::equals('Unknown user', (new RestResponse(404, 'Not Found', [], $reader))->error());
  }

  #[Test]
  public function match_on_success() {
    Assert::equals(['key' => 'value'], (new RestResponse(200, 'OK', ...$this->json('{"key":"value"}')))->match([
      200 => function($response) { return $response->value(); },
      404 => null
    ]));
  }

  #[Test]
  public function match_on_error() {
    Assert::null((new RestResponse(404, 'Not Found', ...$this->json('{"error":"Unknown user"}')))->match([
      200 => function($response) { return $response->value(); },
      404 => null
    ]));
  }

  #[Test, Expect(class: UnexpectedStatus::class, withMessage: 'Unexpected 503 (Service Unavailable)')]
  public function match_on_unhandled() {
    (new RestResponse(503, 'Service Unavailable', ...$this->json('{"error":"Database down"}')))->match([
      200 => function($response) { return $response->value(); },
      404 => null
    ]);
  }

  /** @deprecated */
  #[Test]
  public function result() {
    Assert::equals(
      ['key' => 'value'],
      (new RestResponse(200, 'OK', ...$this->json('{"key":"value"}')))->result()->value()
    );
    \xp::gc();
  }

  #[Test]
  public function no_cookies() {
    Assert::equals(Cookies::$EMPTY, (new RestResponse(200, 'OK', []))->cookies());
  }

  #[Test]
  public function one_cookie() {
    $headers= ['Set-Cookie' => 'session=0x6100; HttpOnly; Path=/'];
    $list= [new Cookie('session', '0x6100', ['HttpOnly' => true, 'Path' => '/'])];

    Assert::equals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test]
  public function multiple_cookies() {
    $headers= ['Set-Cookie' => ['session=0x6100; Max-Age=3600', 'lang=de; Secure', 'test=']];
    $list= [
      new Cookie('session', '0x6100', ['Max-Age' => '3600']),
      new Cookie('lang', 'de', ['Secure' => true]),
      new Cookie('test', null),
    ];

    Assert::equals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test, Values(['evil.example.com', 'evil.example', 'example', 'example.tld', 'evil-example.com', 'evil.example.com.tld',])]
  public function cookie_from_invalid_domain_rejected($domain) {
    $headers= ['Set-Cookie' => 'session=0x6100; Domain='.$domain];
    $uri= new URI('http://app.example.com/');

    Assert::equals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test]
  public function cookie_from_parent_domain_accepted() {
    $headers= ['Set-Cookie' => 'session=0x6100; Domain=example.com'];
    $list= [new Cookie('session', '0x6100', ['Domain' => '.example.com'])];
    $uri= new URI('http://app.example.com/');

    Assert::equals(new Cookies($list), (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test]
  public function secure_cookies_cannot_be_set_by_inssecure_sites() {
    $headers= ['Set-Cookie' => 'session=0x6100; Secure'];
    $uri= new URI('http://app.example.com/');

    Assert::equals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test]
  public function secure_prefix() {
    $headers= ['Set-Cookie' => '__Secure-SID=12345; Secure'];
    $list= [new Cookie('SID', '12345', ['Secure' => true])];

    Assert::equals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test]
  public function secure_prefix_rejects_insecure_cookies() {
    $headers= ['Set-Cookie' => '__Secure-SID=12345'];

    Assert::equals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test]
  public function host_prefix() {
    $headers= ['Set-Cookie' => '__Host-SID=12345; Secure; Path=/'];
    $list= [new Cookie('SID', '12345', ['Domain' => 'app.example.com', 'Path' => '/', 'Secure' => true])];
    $uri= new URI('https://app.example.com/');

    Assert::equals(new Cookies($list), (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test, Values(['__Host-SID=12345', '__Host-SID=12345; Secure', '__Host-SID=12345; Domain=example.com', '__Host-SID=12345; Domain=example.com; Path=/', '__Host-SID=12345; Secure; Domain=example.com; Path=/',])]
  public function host_prefix_rejects($cookie) {
    $headers= ['Set-Cookie' => $cookie];
    $uri= new URI('https://app.example.com/');

    Assert::equals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }
}