<?php namespace webservices\rest\unittest;

use peer\http\HttpOutputStream;

class TestOutputStream extends HttpOutputStream {
  public $bytes= '';

  /** @param string $header */
  public function __construct($header) { $this->bytes= $header."\r\n"; }

  /** @param string $bytes */
  public function write($bytes) { $this->bytes.= $bytes; }

}