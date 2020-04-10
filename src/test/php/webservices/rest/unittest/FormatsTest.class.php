<?php namespace webservices\rest\unittest;

use unittest\TestCase;
use webservices\rest\format\{FormUrlencoded, Format, Json, NdJson, Unsupported};
use webservices\rest\{Formats, RestFormat};

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
  public function supports_ndjson_by_default() {
    $this->assertInstanceOf(NdJson::class, Formats::defaults()->named(NdJson::MIMETYPE));
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
  public function using_restformat_enum() {
    $this->assertInstanceOf(Json::class, Formats::defaults()->named(RestFormat::$JSON));
  }

  #[@test]
  public function unsupported_mimetype() {
    $this->assertInstanceOf(Unsupported::class, Formats::defaults()->named('application/vnd.php.serialized'));
  }

  #[@test]
  public function with_vendor_mimetype() {
    $mime= 'application/vnd.php.serialized';
    $format= new class() extends Format {
      public function serialize($value, $stream) { /* TBI */ }
      public function deserialize($stream) { /* TBI */ }
    };

    $this->assertEquals($format, (new Formats())->with($mime, $format)->named($mime));
  }

  #[@test, @values([
  #  'application/vnd.com.example.customer+xml',
  #  'application/vnd.com.example.customer-v2+xml'
  #])]
  public function matching_vendor_mimetype($mime) {
    $pattern= 'application/vnd.*+xml';
    $format= new class() extends Format {
      public function serialize($value, $stream) { /* TBI */ }
      public function deserialize($stream) { /* TBI */ }
    };

    $this->assertEquals($format, (new Formats())->matching($pattern, $format)->named($mime));
  }
}