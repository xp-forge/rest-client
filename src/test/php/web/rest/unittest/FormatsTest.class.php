<?php namespace web\rest\unittest;

use unittest\TestCase;
use web\rest\Formats;
use web\rest\format\FormUrlencoded;
use web\rest\format\Format;
use web\rest\format\Json;
use web\rest\format\Unsupported;

class FormatsTest extends TestCase {

  #[@test]
  public function can_create() {
    new Formats();
  }

  #[@test]
  public function supports_json_by_default() {
    $this->assertInstanceOf(Json::class, Formats::defaults()->named('application/json'));
  }

  #[@test]
  public function supports_form_urlencoded_by_default() {
    $this->assertInstanceOf(FormUrlencoded::class, Formats::defaults()->named('application/x-www-form-urlencoded'));
  }

  #[@test]
  public function supports_json_vendor_types_by_default() {
    $this->assertInstanceOf(Json::class, Formats::defaults()->named('application/vnd.github.v3+json'));
  }

  #[@test]
  public function unsupported_mimetype() {
    $this->assertInstanceOf(Unsupported::class, Formats::defaults()->named('application/vnd.php.serialized'));
  }

  #[@test]
  public function with_vendor_mimetype() {
    $mime= 'application/vnd.php.serialized';
    $format= newinstance(Format::class, [], [
      'serialize'   => function($value, $stream) { /* TBI */ },
      'deserialize' => function($stream, $type) { /* TBI */ }
    ]);

    $this->assertEquals($format, (new Formats())->with($mime, $format)->named($mime));
  }

  #[@test, @values([
  #  'application/vnd.com.example.customer+xml',
  #  'application/vnd.com.example.customer-v2+xml'
  #])]
  public function matching_vendor_mimetype($mime) {
    $pattern= 'application/vnd.*+xml';
    $format= newinstance(Format::class, [], [
      'serialize'   => function($value, $stream) { /* TBI */ },
      'deserialize' => function($stream, $type) { /* TBI */ }
    ]);

    $this->assertEquals($format, (new Formats())->matching($pattern, $format)->named($mime));
  }
}