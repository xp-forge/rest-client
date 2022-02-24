<?php namespace webservices\rest\unittest;

use io\streams\{Compression, MemoryInputStream};
use peer\http\HttpResponse;
use unittest\actions\ExtensionAvailable;
use unittest\{Action, Assert, Before, Test, Values};
use webservices\rest\Endpoint;
use webservices\rest\io\Transmission;

class CompressionHandlingTest {
  private $endpoint;

  /**
   * Creates a HTTP response with status 200 from given headers and payload
   *
   * @param  [:string] $headers
   * @param  string $payload
   * @return webservices.rest.RestResponse
   */
  private function response($headers, $payload) {
    return $this->endpoint->finish(new class($headers, $payload) extends Transmission {
      private $body;

      public function __construct($headers, $payload) {
        $this->body= "HTTP/1.1 200 OK\r\nContent-Length: ".strlen($payload)."\r\n";
        foreach ($headers as $name => $value) {
          $this->body.= $name.': '.$value."\r\n";
        }
        $this->body.= "\r\n".$payload;
      }

      public function finish() {
        return new HttpResponse(new MemoryInputStream($this->body), false);
      }
    });
  }

  #[Before]
  public function endpoint() {
    $this->endpoint= new Endpoint('http://api.example.com');
  }

  #[Test, Action(eval: 'new ExtensionAvailable("zlib")')]
  public function accept_includes_gzip() {
    Assert::notEquals('', strstr($this->endpoint->headers()['Accept-Encoding'], 'gzip'));
  }

  #[Test, Action(eval: 'new ExtensionAvailable("brotli")')]
  public function accept_includes_brotli() {
    Assert::notEquals('', strstr($this->endpoint->headers()['Accept-Encoding'], 'br'));
  }

  #[Test]
  public function sets_accept_encoding_header() {
    $this->endpoint->compressing(['gzip', 'br']);

    Assert::equals('gzip, br', $this->endpoint->headers()['Accept-Encoding']);
  }

  #[Test]
  public function do_not_use_compression() {
    $this->endpoint->compressing(Compression::$NONE);

    Assert::equals('identity', $this->endpoint->headers()['Accept-Encoding']);
  }

  #[Test]
  public function removes_accept_encoding_header() {
    $this->endpoint->compressing(null);

    Assert::false(isset($this->endpoint->headers()['Accept-Encoding']));
  }

  #[Test, Values([1, 6, 9]), Action(eval: 'new ExtensionAvailable("zlib")')]
  public function gzip($level) {
    $response= $this->response(
      ['Content-Type' => 'application/json', 'Content-Encoding' => 'gzip'],
      gzencode('{"result":true}', $level)
    );
    Assert::equals(['result' => true], $response->value());
  }

  #[Test, Values([0, 6, 11]), Action(eval: 'new ExtensionAvailable("brotli")')]
  public function brotli($level) {
    $response= $this->response(
      ['Content-Type' => 'application/json', 'Content-Encoding' => 'br'],
      brotli_compress('{"result":true}', $level)
    );
    Assert::equals(['result' => true], $response->value());
  }
}