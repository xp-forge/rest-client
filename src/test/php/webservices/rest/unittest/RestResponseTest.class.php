<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use unittest\TestCase;
use util\URI;
use util\data\Marshalling;
use webservices\rest\Cookie;
use webservices\rest\Cookies;
use webservices\rest\RestResponse;
use webservices\rest\format\Json;
use webservices\rest\io\Reader;

class RestResponseTest extends TestCase {

  #[@test]
  public function can_create() {
    new RestResponse(200, 'OK');
  }

  #[@test]
  public function can_create_with_headers() {
    new RestResponse(200, 'OK', ['Content-Type' => 'text/html']);
  }

  #[@test]
  public function can_create_with_reader() {
    $reader= new Reader(new MemoryInputStream('...'), new Json(), new Marshalling());
    new RestResponse(200, 'OK', [], $reader);
  }

  #[@test, @values(['http://localhost/', new URI('http://localhost/')])]
  public function can_create_with_uri($uri) {
    new RestResponse(200, 'OK', [], null, $uri);
  }

  #[@test]
  public function status() {
    $this->assertEquals(200, (new RestResponse(200, 'OK'))->status());
  }

  #[@test]
  public function message() {
    $this->assertEquals('OK', (new RestResponse(200, 'OK'))->message());
  }

  #[@test]
  public function stream() {
    $stream= new MemoryInputStream('...');
    $reader= new Reader($stream, new Json(), new Marshalling());
    $this->assertEquals($stream, (new RestResponse(200, 'OK', [], $reader))->stream());
  }

  #[@test]
  public function headers() {
    $headers= ['Content-Type' => 'text/html'];
    $this->assertEquals($headers, (new RestResponse(200, 'OK', $headers))->headers());
  }

  #[@test, @values(['content-type', 'Content-type', 'Content-Type'])]
  public function header($name) {
    $headers= ['Content-Type' => 'text/html'];
    $this->assertEquals('text/html', (new RestResponse(200, 'OK', $headers))->header($name));
  }

  #[@test]
  public function non_existant_header() {
    $this->assertNull((new RestResponse(200, 'OK', []))->header('Content-Type'));
  }

  #[@test]
  public function non_existant_header_uses_default() {
    $default= 'application/octet-stream';
    $this->assertEquals($default, (new RestResponse(200, 'OK', []))->header('Content-Type', $default));
  }

  #[@test]
  public function location_header() {
    $location= 'http://localhost/redirect';
    $this->assertEquals(new URI($location), (new RestResponse(200, 'OK', ['Location' => $location]))->location());
  }

  #[@test]
  public function non_existant_location_header() {
    $this->assertNull((new RestResponse(200, 'OK', []))->location());
  }

  #[@test]
  public function location_header_resolved() {
    $this->assertEquals(
      new URI('http://localhost/redirect'),
      (new RestResponse(200, 'OK', ['Location' => '/redirect'], null, 'http://localhost'))->location()
    );
  }

  #[@test]
  public function value() {
    $stream= new MemoryInputStream('{"key":"value"}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    $this->assertEquals(['key' => 'value'], (new RestResponse(200, 'OK', [], $reader))->value());
  }

  #[@test]
  public function type_coercion() {
    $stream= new MemoryInputStream('{"key":200}');
    $reader= new Reader($stream, new Json(), new Marshalling());
    $this->assertEquals(['key' => '200'], (new RestResponse(200, 'OK', [], $reader))->value('[:string]'));
  }

  #[@test]
  public function no_cookies() {
    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', []))->cookies());
  }

  #[@test]
  public function one_cookie() {
    $headers= ['Set-Cookie' => 'session=0x6100; HttpOnly; Path=/'];
    $list= [new Cookie('session', '0x6100', ['HttpOnly' => true, 'Path' => '/'])];

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[@test]
  public function multiple_cookies() {
    $headers= ['Set-Cookie' => ['session=0x6100; Max-Age=3600', 'lang=de; Secure', 'test=']];
    $list= [
      new Cookie('session', '0x6100', ['Max-Age' => '3600']),
      new Cookie('lang', 'de', ['Secure' => true]),
      new Cookie('test', null)
    ];

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[@test, @values([
  #  'evil.example.com',
  #  'evil.example',
  #  'example',
  #  'example.tld',
  #  'evil-example.com',
  #  'evil.example.com.tld',
  #])]
  public function cookie_from_invalid_domain_rejected($domain) {
    $headers= ['Set-Cookie' => 'session=0x6100; Domain='.$domain];
    $uri= new URI('http://app.example.com/');

    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[@test]
  public function cookie_from_parent_domain_accepted() {
    $headers= ['Set-Cookie' => 'session=0x6100; Domain=example.com'];
    $list= [new Cookie('session', '0x6100', ['Domain' => '.example.com'])];
    $uri= new URI('http://app.example.com/');

    $this->assertEquals(new Cookies($list), (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }

  #[@test]
  public function secure_cookies_cannot_be_set_by_inssecure_sites() {
    $headers= ['Set-Cookie' => 'session=0x6100; Secure'];
    $uri= new URI('http://app.example.com/');

    $this->assertEquals(Cookies::$EMPTY, (new RestResponse(200, 'OK', $headers, null, $uri))->cookies());
  }
}