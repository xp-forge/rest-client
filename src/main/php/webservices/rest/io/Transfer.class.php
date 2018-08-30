<?php namespace webservices\rest\io;

interface Transfer {

  public function writer($request, $payload, $format, $marshalling);
}