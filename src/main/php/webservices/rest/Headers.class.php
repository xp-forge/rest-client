<?php namespace webservices\rest;

trait Headers {
  private $headers= [], $lookup= [];

  /**
   * Adds headers
   *
   * @param  [:string|string[]] $headers
   * @return void
   */
  public function add($headers) {
    foreach ((array)$headers as $name => $value) {
      $lookup= strtolower($name);
      if (isset($this->lookup[$lookup])) {
        $this->headers[$this->lookup[$lookup]][]= $value;
      } else {
        $this->headers[$name]= (array)$value;
        $this->lookup[$lookup]= $name;
      }
    }
  }

  /**
   * Gets a header by name. Performs a case-insensitive lookup.
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function header($name, $default= null) {
    $lookup= strtolower($name);
    return isset($this->lookup[$lookup])
      ? implode(', ', $this->headers[$this->lookup[$lookup]])
      : $default
    ;
  }

  /** @return [:string|string[]] */
  public function headers() {
    $r= [];
    foreach ($this->headers as $name => $header) {
      $r[$name]= 1 === sizeof($header) ? $header[0] : $header;
    }
    return $r;
  }
}