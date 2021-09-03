<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use unittest\{Assert, Test, Values};
use util\URI;
use util\data\Marshalling;
use webservices\rest\format\Json;
use webservices\rest\io\Reader;
use webservices\rest\{Cookie, Cookies, Link, RestResponse};

class RestResponseTest {
  const API = 'https://api.example.com/';

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
    $reader= new Reader(new MemoryInputStream('...'), new Json(), new Marshalling());
    new RestResponse(200, 'OK', [], $reader);
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
    Assert::equals([], iterator_to_array((new RestResponse(200, 'OK', []))->links()->all()));
  }

  #[Test]
  public function link() {
    Assert::equals(
      [new Link('meta.rdf', ['rel' => 'meta'])],
      iterator_to_array((new RestResponse(200, 'OK', ['Link' => '<meta.rdf>;rel=meta']))->links()->all())
    );
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
  public function value() {
    $stream= new MemoryInputStream('{"key":"value"}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    Assert::equals(['key' => 'value'], (new RestResponse(200, 'OK', [], $reader))->value());
  }

  #[Test]
  public function type_coercion() {
    $stream= new MemoryInputStream('{"key":200}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    Assert::equals(['key' => '200'], (new RestResponse(200, 'OK', [], $reader))->value('[:string]'));
  }

  #[Test]
  public function result() {
    $stream= new MemoryInputStream('{"key":"value"}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    Assert::equals(['key' => 'value'], (new RestResponse(200, 'OK', [], $reader))->result()->value());
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