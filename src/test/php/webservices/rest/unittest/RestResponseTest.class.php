<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use unittest\TestCase;
use util\URI;
use util\data\Marshalling;
use webservices\rest\Cookie;
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
  public function one_cookie() {
    $headers= ['Set-Cookie' => 'session=0x6100'];
    $this->assertEquals(['session' => new Cookie('session', '0x6100')], (new RestResponse(200, 'OK', $headers))->cookies());
  }

  #[@test]
  public function multiple_cookies() {
    $headers= ['Set-Cookie' => ['session=0x6100', 'language=de']];
    $this->assertEquals(
      ['session' => new Cookie('session', '0x6100'), 'language' => new Cookie('language', 'de')],
      (new RestResponse(200, 'OK', $headers))->cookies()
    );
  }
}