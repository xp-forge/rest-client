<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use peer\http\HttpConnection;
use peer\http\HttpOutputStream;
use peer\http\HttpRequest;
use peer\http\HttpResponse;

class TestConnection extends HttpConnection {

  public function open(HttpRequest $request) {
    $header= $request->method.' '.$request->target().' HTTP/'.$request->version."\r\n";
    foreach ($request->headers as $name => $values) {
      foreach ($values as $value) {
        $header.= $name.': '.$value."\r\n";
      }
    }
    return new TestOutputStream($header);
  }

  public function finish(HttpOutputStream $stream) {
    return new HttpResponse(new MemoryInputStream(sprintf(
      "HTTP/1.0 200 OK\r\nContent-Type: text/plain\r\nContent-Length: %d\r\n\r\n%s",
      strlen($stream->bytes),
      $stream->bytes
    )));
  }
}