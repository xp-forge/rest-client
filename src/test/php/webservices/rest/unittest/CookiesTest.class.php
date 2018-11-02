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
  public function can_be_iterated() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertEquals([new Cookie('session', '0x6100')], iterator_to_array($cookies));
  }

  #[@test]
  public function for_domain_and_path() {
    $expired= gmdate('D, d M Y H:i:s \G\M\T', time() - 86400);
    $included= [
      new Cookie('session', '0x6100', ['Domain' => 'sub.example.com', 'Path' => '/path']),
      new Cookie('lang', 'de', ['Domain' => '.example.com', 'Path' => '/path']),
      new Cookie('cookies', 'true', ['Domain' => '.example.com', 'Path' => '/']),
    ];
    $excluded= [
      new Cookie('session', '0x6100', ['Domain' => '.example.com', 'Path' => '/path']),
      new Cookie('uid', '1549', ['Domain' => '.example.com', 'Expires' => $expired]),
      new Cookie('accept', 'yes', ['Domain' => '.example.com', 'Max-Age' => 0]),
      new Cookie('session', '0x6100', ['Domain' => '.example.com', 'Path' => '/other']),
      new Cookie('session', '0x6100', ['Domain' => '.example.com', 'Path' => '/', 'Secure' => true]),
      new Cookie('session', '0x6100', ['Domain' => 'example.com', 'Path' => '/']),
      new Cookie('session', '0x6100', ['Domain' => 'other.com', 'Path' => '/']),
    ];

    $cookies= new Cookies(array_merge($excluded, $included));
    $this->assertEquals($included, iterator_to_array($cookies->validFor('http://sub.example.com/path')));
  }

  #[@test]
  public function cookies_with_same_name_overwritten_during_merge() {
    $cookies= new Cookies(['session' => '0x6100']);
    $this->assertEquals(
      [new Cookie('session', '0x6200')],
      iterator_to_array($cookies->merge(['session' => '0x6200']))
    );
  }

  #[@test]
  public function cookies_without_value_erased() {
    $cookies= new Cookies(['session' => '0x6100', 'lang' => 'de']);
    $this->assertEquals(
      [new Cookie('lang', 'de')],
      iterator_to_array($cookies->merge(['session' => null]))
    );
  }
}