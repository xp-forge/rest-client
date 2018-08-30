<?php namespace web\rest\io;

interface Transfer {

  public function writer($request, $payload, $format);
}