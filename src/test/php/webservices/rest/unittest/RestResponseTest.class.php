<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use unittest\{Test, TestCase, Values};
use util\URI;
use util\data\Marshalling;
use webservices\rest\format\Json;
use webservices\rest\io\Reader;
use webservices\rest\{Cookie, Cookies, Link, RestResponse};

class RestResponseTest extends TestCase {
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
    $this->assertEquals(200, (new RestResponse(200, 'OK'))->status());
  }

  #[Test]
  public function message() {
    $this->assertEquals('OK', (new RestResponse(200, 'OK'))->message());
  }

  #[Test]
  public function no_uri() {
    $this->assertNull((new RestResponse(200, 'OK'))->uri());
  }

  #[Test]
  public function uri() {
    $this->assertEquals(
      new URI('http://example.com/'),
      (new RestResponse(200, 'OK', [], null, 'http://example.com/'))->uri()
    );
  }

  #[Test, Values([[null, 'http://example.com/test/'], [self::API, self::API], ['/', 'http://example.com/'], ['/user/', 'http://example.com/user/']])]
  public function resolve_uri($uri, $resolved) {
    $this->assertEquals(
      new URI($resolved),
      (new RestResponse(200, 'OK', [], null, 'http://example.com/test/'))->resolve($uri)
    );
  }

  #[Test]
  public function stream() {
    $stream= new MemoryInputStream('...');
    $reader= new Reader($stream, new Json(), new Marshalling());
    $this->assertEquals($stream, (new RestResponse(200, 'OK', [], $reader))->stream());
  }

  #[Test]
  public function without_links() {
    $this->assertEquals([], iterator_to_array((new RestResponse(200, 'OK', []))->links()->all()));
  }

  #[Test]
  public function link() {
    $this->assertEquals(
      [new Link('meta.rdf', ['rel' => 'meta'])],
      iterator_to_array((new RestResponse(200, 'OK', ['Link' => '<meta.rdf>;rel=meta']))->links()->all())
    );
  }

  #[Test]
  public function headers() {
    $headers= ['Content-Type' => 'text/html'];
    $this->assertEquals($headers, (new RestResponse(200, 'OK', $headers))->headers());
  }

  #[Test, Values(['content-type', 'Content-type', 'Content-Type'])]
  public function header($name) {
    $headers= ['Content-Type' => 'text/html'];
    $this->assertEquals('text/html', (new RestResponse(200, 'OK', $headers))->header($name));
  }

  #[Test]
  public function non_existant_header() {
    $this->assertNull((new RestResponse(200, 'OK', []))->header('Content-Type'));
  }

  #[Test]
  public function non_existant_header_uses_default() {
    $default= 'application/octet-stream';
    $this->assertEquals($default, (new RestResponse(200, 'OK', []))->header('Content-Type', $default));
  }

  #[Test]
  public function location_header() {
    $location= 'http://localhost/redirect';
    $this->assertEquals(new URI($location), (new RestResponse(200, 'OK', ['Location' => $location]))->location());
  }

  #[Test]
  public function non_existant_location_header() {
    $this->assertNull((new RestResponse(200, 'OK', []))->location());
  }

  #[Test]
  public function location_header_resolved() {
    $this->assertEquals(
      new URI('http://localhost/redirect'),
      (new RestResponse(200, 'OK', ['Location' => '/redirect'], null, 'http://localhost'))->location()
    );
  }

  #[Test]
  public function value() {
    $stream= new MemoryInputStream('{"key":"value"}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    $this->assertEquals(['key' => 'value'], (new RestResponse(200, 'OK', [], $reader))->value());
  }

  #[Test]
  public function type_coercion() {
    $stream= new MemoryInputStream('{"key":200}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    $this->assertEquals(['key' => '200'], (new RestResponse(200, 'OK', [], $reader))->value('[:string]'));
  }

  #[Test]
  public function result() {
    $stream= new MemoryInputStream('{"key":"value"}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    $this->assertEquals(['key' => 'value'], (new RestResponse(200, 'OK', [], $reader))->result()->value());
  }

  #[Test]
  public function no_cookies() {
    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', []))->cookies());
  }

  #[Test]
  public function one_cookie() {
    $headers= ['Set-Cookie' => 'session=0x6100; HttpOnly; Path=/'];
    $list= [new Cookie('session', '0x6100', ['HttpOnly' => true, 'Path' => '/'])];

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test]
  public function multiple_cookies() {
    $headers= ['Set-Cookie' => ['session=0x6100; Max-Age=3600', 'lang=de; Secure', 'test=']];
    $list= [
      new Cookie('session', '0x6100', ['Max-Age' => '3600']),
      new Cookie('lang', 'de', ['Secure' => true]),
      new Cookie('test', null),
    ];

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test, Values(['evil.example.com', 'evil.example', 'example', 'example.tld', 'evil-example.com', 'evil.example.com.tld',])]
  public function cookie_from_invalid_domain_rejected($domain) {
    $headers= ['Set-Cookie' => 'session=0x6100; Domain='.$domain];
    $uri= new URI('http://app.example.com/');

    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test]
  public function cookie_from_parent_domain_accepted() {
    $headers= ['Set-Cookie' => 'session=0x6100; Domain=example.com'];
    $list= [new Cookie('session', '0x6100', ['Domain' => '.example.com'])];
    $uri= new URI('http://app.example.com/');

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test]
  public function secure_cookies_cannot_be_set_by_inssecure_sites() {
    $headers= ['Set-Cookie' => 'session=0x6100; Secure'];
    $uri= new URI('http://app.example.com/');

    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test]
  public function secure_prefix() {
    $headers= ['Set-Cookie' => '__Secure-SID=12345; Secure'];
    $list= [new Cookie('SID', '12345', ['Secure' => true])];

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test]
  public function secure_prefix_rejects_insecure_cookies() {
    $headers= ['Set-Cookie' => '__Secure-SID=12345'];

    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[Test]
  public function host_prefix() {
    $headers= ['Set-Cookie' => '__Host-SID=12345; Secure; Path=/'];
    $list= [new Cookie('SID', '12345', ['Domain' => 'app.example.com', 'Path' => '/', 'Secure' => true])];
    $uri= new URI('https://app.example.com/');

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[Test, Values(['__Host-SID=12345', '__Host-SID=12345; Secure', '__Host-SID=12345; Domain=example.com', '__Host-SID=12345; Domain=example.com; Path=/', '__Host-SID=12345; Secure; Domain=example.com; Path=/',])]
  public function host_prefix_rejects($cookie) {
    $headers= ['Set-Cookie' => $cookie];
    $uri= new URI('https://app.example.com/');

    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }
}