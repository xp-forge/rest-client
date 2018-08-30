<?php namespace web\rest\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use unittest\TestCase;
use util\URI;
use web\rest\RestResponse;
use web\rest\io\Reader;

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
    new RestResponse(200, 'OK', [], new Reader(new MemoryInputStream('...'), null));
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
  public function headers() {
    $headers= ['Content-Type' => 'text/html'];
    $this->assertEquals($headers, (new RestResponse(200, 'OK', $headers))->headers());
  }

  #[@test]
  public function stream() {
    $stream= new MemoryInputStream('...');
    $this->assertEquals($stream, (new RestResponse(200, 'OK', [], new Reader($stream, null)))->stream());
  }

  #[@test, @values(['content-type', 'Content-type', 'Content-Type'])]
  public function header($name) {
    $this->assertEquals('text/html', (new RestResponse(200, 'OK', ['Content-Type' => 'text/html']))->header($name));
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
}