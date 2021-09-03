<?php namespace webservices\rest\unittest;

use lang\ElementNotFoundException;
use unittest\{Assert, Test};
use webservices\rest\{Cookie, Cookies};

class CookiesTest {

  #[Test]
  public function can_create_from_empty() {
    new Cookies([]);
  }

  #[Test]
  public function present() {
    $cookies= new Cookies(['session' => '0x6100']);
    Assert::true($cookies->present());
  }

  #[Test]
  public function not_present() {
    $cookies= Cookies::$EMPTY;
    Assert::false($cookies->present());
  }

  #[Test]
  public function no_longer_present_after_clearing() {
    $cookies= new Cookies(['session' => '0x6100']);
    Assert::false($cookies->clear()->present());
  }

  #[Test]
  public function can_be_iterated() {
    $cookies= new Cookies(['session' => '0x6100']);
    Assert::equals([new Cookie('session', '0x6100')], iterator_to_array($cookies));
  }

  #[Test]
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
    Assert::equals($included, iterator_to_array($cookies->validFor('http://sub.example.com/path')));
  }

  #[Test]
  public function cookies_with_same_name_overwritten_during_merge() {
    $cookies= new Cookies(['session' => '0x6100']);
    Assert::equals(
      [new Cookie('session', '0x6200')],
      iterator_to_array($cookies->update(['session' => '0x6200']))
    );
  }

  #[Test]
  public function cookies_without_value_erased() {
    $cookies= new Cookies(['session' => '0x6100', 'lang' => 'de']);
    Assert::equals(
      [new Cookie('lang', 'de')],
      iterator_to_array($cookies->update(['session' => null]))
    );
  }

  #[Test]
  public function string_representation() {
    $cookies= new Cookies([
      new Cookie('session', '0x6100', ['Secure' => true]),
      new Cookie('lang', 'de'),
    ]);

    Assert::equals(
      "webservices.rest.Cookies@{\n".
      "  webservices.rest.Cookie(lang=de)@[]\n".
      "  webservices.rest.Cookie(session=0x6100)@[\n".
      "    Secure => true\n".
      "  ]\n".
      "}",
      $cookies->toString()
    );
  }
}