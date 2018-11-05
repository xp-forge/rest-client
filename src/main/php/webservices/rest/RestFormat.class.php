<?php namespace webservices\rest;

use lang\Enum;

class RestFormat extends Enum {
  public static $JSON, $XML, $FORM;

  private $mime;

  static function __static() {
    self::$JSON= new self(1, 'JSON', 'application/json');
    self::$XML= new self(2, 'XML', 'text/xml');
    self::$FORM= new self(3, 'FORM', 'application/x-www-form-urlencoded');
  }

  /**
   * Creates a new REST Format
   *
   * @param  int $ordinal
   * @param  string $name
   * @param  string $mime
   */
  public function __construct($ordinal, $name, $mime) {
    parent::__construct($ordinal, $name);
    $this->mime= $mime;
  }

  /** @return string */
  public function mime() { return $this->mime; }

  /** @return string */
  public function __toString() { return $this->mime; }
}