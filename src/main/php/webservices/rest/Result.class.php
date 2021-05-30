<?php namespace webservices\rest;

use util\URI;
use webservices\rest\format\Unsupported;

/**
 * Result is a high-level abstraction of a REST API's results.
 *
 * @see   https://github.com/xp-forge/rest-client/pull/14
 * @see   webservices.rest.RestResponse::result()
 * @test  webservices.rest.unittest.ResultTest
 */
class Result {
  private $response;

  /** @param webservices.rest.RestResponse */
  public function __construct($response) { $this->response= $response; }

  /** @param int */
  public function status() { return $this->response->status(); }

  /**
   * Returns links sent by server as a map indexed by the `rel` attribute.
   *
   * @return [:util.URI]
   */
  public function links() {
    $r= [];
    foreach (Links::in($this->response->header('Link'))->all() as $link) {
      $r[$link->param('rel')]= $this->response->resolve($link->uri());
    }
    return $r;
  }

  /**
   * Returns link URI with a given `rel` attribute or NULL if the given
   * link is not present (or no `Link` header is present at all). Throws
   * an exception if the HTTP statuscode is not in the 200-299 range.
   *
   * @param  string $rel
   * @return ?util.URI
   * @throws webservices.rest.UnexpectedStatus
   */
  public function link($rel) {
    $s= $this->response->status();
    if ($s >= 200 && $s < 300) {
      foreach (Links::in($this->response->header('Link'))->all(['rel' => $rel]) as $link) {
        return $this->response->resolve($link->uri());
      }
      return null;
    }

    throw new UnexpectedStatus($this->response);
  }

  /**
   * Returns the resolved `Location` header from the response. Throws an
   * exception if the header is not present.
   *
   * @return util.URI
   * @throws webservices.rest.UnexpectedStatus
   */
  public function location() {
    if ($h= $this->response->header('Location')) return $this->response->resolve($h);

    throw new UnexpectedStatus($this->response);
  }

  /**
   * Matches response status codes and returns values based on the given cases.
   * A case is an integer status code mapped to either a given value or a
   * function which receives this result instance and returns a value. Throws
   * an exception if the HTTP statuscode is not in the 200-299 range.
   *
   * @param  [:var] $cases
   * @return var
   * @throws webservices.rest.UnexpectedStatus
   */
  public function match(array $cases) {
    $s= $this->response->status();
    if (array_key_exists($s, $cases)) return $cases[$s] instanceof \Closure
      ? $cases[$s]($this)
      : $cases[$s]
    ;

    throw new UnexpectedStatus($this->response);
  }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Throws an exception if the HTTP statuscode not in the 200-299 range.
   *
   * @param  ?string $type
   * @return var
   * @throws webservices.rest.UnexpectedStatus
   */
  public function value($type= null) {
    $s= $this->response->status();
    if ($s >= 200 && $s < 300) return $this->response->reader()->read($type ?? 'var');

    throw new UnexpectedStatus($this->response);
  }

  /**
   * Returns a value from the response, using the given type for deserialization.
   * Returns NULL for a given list of status codes indicating absence, defaulting
   * to 404s. Throws an exception if the HTTP statuscode not in the 200-299 range.
   *
   * @param  ?string $type
   * @param  int[] $absent Status code indicating absence
   * @return var
   * @throws webservices.rest.UnexpectedStatus
   */
  public function optional($type= null, $absent= [404]) {
    $s= $this->response->status();
    if ($s >= 200 && $s < 300) return $this->response->reader()->read($type ?? 'var');
    if (in_array($s, $absent)) return null;

    throw new UnexpectedStatus($this->response);
  }

  /**
   * Returns the error from the response, using the given type for deserialization.
   * Falls back to using the complete body as a string if the response format is
   * unsupported.
   *
   * @param  ?string $type
   * @return var
   */
  public function error($type= null) {
    if ($this->response->status() < 400) return null;

    $r= $this->response->reader();
    return $r->format() instanceof Unsupported ? $r->content() : $r->read($type ?? 'var');
  }
}