<?php namespace webservices\rest\unittest;

use lang\ElementNotFoundException;
use unittest\TestCase;
use webservices\rest\Cookie;
use webservices\rest\Cookies;

class CookiesTest extends TestCase {

  #[@test]
  public function can_create_from_empty() {
    new Cookies([]);
  }

  #[@test]
  public function present() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertTrue($cookies->present());
  }

  #[@test]
  public function not_present() {
    $cookies= Cookies::$EMPTY;
    $this->assertFalse($cookies->present());
  }

  #[@test]
  public function no_longer_present_after_clearing() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertFalse($cookies->clear()->present());
  }

  #[@test]
  public function named() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertEquals('0x6100', $cookies->named('session')->value());
  }

  #[@test, @expect(ElementNotFoundException::class)]
  public function named_raises_exception_when_not_existant() {
    Cookies::$EMPTY->named('session');
  }

  #[@test]
  public function provides() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertTrue($cookies->provides('session'));
  }

  #[@test]
  public function does_not_provide_non_existant() {
    $this->assertFalse(Cookies::$EMPTY->provides('session'));
  }

  #[@test]
  public function can_be_iterated() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertEquals(
      ['session' => new Cookie('session', '0x6100')],
      iterator_to_array($cookies)
    );
  }

  #[@test]
  public function cookies_with_same_name_overwritten_during_merge() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertEquals(
      ['session' => new Cookie('session', '0x6200')],
      iterator_to_array($cookies->merge(['session' => '0x6200']))
    );
  }

  #[@test]
  public function cookies_without_value_erased() {
    $cookies= new Cookies(['session' => '0x6100', 'lang' => 'de']);
    $this->assertEquals(
      ['lang' => new Cookie('lang', 'de')],
      iterator_to_array($cookies->merge(['session' => null]))
    );
  }
}