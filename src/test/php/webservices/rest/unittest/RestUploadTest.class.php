<?php namespace webservices\rest\unittest;

use io\streams\MemoryInputStream;
use test\{Assert, Test};
use webservices\rest\{Endpoint, RestRequest, RestUpload};

class RestUploadTest {

  /** Returns a new endpoint using the `TestConnection` class */
  private function newEndpoint(string $base= 'http://test') {
    return (new Endpoint($base, null, []))->connecting([TestConnection::class, 'new']);
  }

  #[Test]
  public function can_create() {
    new RestUpload($this->newEndpoint(), new RestRequest('POST', '/'));
  }

  #[Test]
  public function pass_parameter() {
    $upload= new RestUpload($this->newEndpoint(), new RestRequest('POST', '/'));
    $upload->pass('name', 'Test');

    Assert::equals(
      "POST / HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: multipart/form-data; boundary=---------------boundary1xp6132872336bc4\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "-----------------boundary1xp6132872336bc4\r\n".
      "Content-Disposition: form-data; name=\"name\"\r\n".
      "\r\n".
      "Test\r\n".
      "-----------------boundary1xp6132872336bc4--\r\n",
      $upload->finish()->content()
    );
  }

  #[Test]
  public function transfer_file() {
    $upload= new RestUpload($this->newEndpoint(), new RestRequest('POST', '/'));
    $upload->transfer('file', new MemoryInputStream('Test'), 'test.txt');

    Assert::equals(
      "POST / HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: multipart/form-data; boundary=---------------boundary1xp6132872336bc4\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "-----------------boundary1xp6132872336bc4\r\n".
      "Content-Disposition: form-data; name=\"file\"; filename=\"test.txt\"\r\n".
      "Content-Type: text/plain\r\n".
      "\r\n".
      "Test\r\n".
      "-----------------boundary1xp6132872336bc4--\r\n",
      $upload->finish()->content()
    );
  }

  #[Test]
  public function stream_to_part() {
    $upload= new RestUpload($this->newEndpoint(), new RestRequest('POST', '/'));
    $part= $upload->stream('file', 'test.txt');
    $part->write('Te');
    $part->write('st');
    $part->close();

    Assert::equals(
      "POST / HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: multipart/form-data; boundary=---------------boundary1xp6132872336bc4\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "-----------------boundary1xp6132872336bc4\r\n".
      "Content-Disposition: form-data; name=\"file\"; filename=\"test.txt\"\r\n".
      "Content-Type: text/plain\r\n".
      "\r\n".
      "Test\r\n".
      "-----------------boundary1xp6132872336bc4--\r\n",
      $upload->finish()->content()
    );
  }
}