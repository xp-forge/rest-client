<?php namespace webservices\rest\unittest;

use peer\http\HttpConnection;
use unittest\{Assert, Before, Test};
use webservices\rest\{Endpoint, RestRequest};

class TimeoutTest {
  const API = 'https://api.example.com';

  private $endpoint, $conn;

  #[Before]
  public function endpoint() {
    $this->endpoint= new Endpoint(self::API);
    $this->conn= new HttpConnection(self::API);
  }

  #[Test]
  public function takes_timeouts_from_httpconnection() {
    $endpoint= (clone $this->endpoint)->connecting(function($uri) {
      $c= new HttpConnection($uri);
      $c->setTimeout(600);
      $c->setConnectTimeout(30);
      return $c;
    });
    $conn= $endpoint->open(new RestRequest('GET', '/'))->connection();

    Assert::equals(600, $conn->getTimeout(), 'read');
    Assert::equals(30, $conn->getConnectTimeout(), 'connect');
  }

  #[Test]
  public function default_timeouts() {
    $conn= $this->endpoint->open(new RestRequest('GET', '/'))->connection();

    Assert::equals($this->conn->getTimeout(), $conn->getTimeout(), 'read');
    Assert::equals($this->conn->getConnectTimeout(), $conn->getConnectTimeout(), 'connect');
  }

  #[Test]
  public function change_read_timeout() {
    $conn= $this->endpoint->open((new RestRequest('GET', '/'))->waiting(600, null))->connection();

    Assert::equals(600, $conn->getTimeout(), 'read');
    Assert::equals($this->conn->getConnectTimeout(), $conn->getConnectTimeout(), 'connect');
  }

  #[Test]
  public function change_connect_timeout() {
    $conn= $this->endpoint->open((new RestRequest('GET', '/'))->waiting(null, 30))->connection();

    Assert::equals($this->conn->getTimeout(), $conn->getTimeout(), 'read');
    Assert::equals(30, $conn->getConnectTimeout(), 'connect');
  }

  #[Test]
  public function usage_via_resource_request_method() {
    $conn= $this->endpoint->open($this->endpoint->resource('/')->request('GET')->waiting(600, 30))->connection();

    Assert::equals(600, $conn->getTimeout(), 'read');
    Assert::equals(30, $conn->getConnectTimeout(), 'connect');
  }
}