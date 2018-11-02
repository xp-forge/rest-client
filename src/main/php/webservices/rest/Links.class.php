<?php namespace webservices\rest;

use lang\FormatException;
use lang\Value;
use text\StringTokenizer;

/**
 * Link header
 *
 * @test xp://webservices.rest.unittest.LinksTest
 * @see  https://www.w3.org/wiki/LinkHeader
 * @see  https://tools.ietf.org/html/rfc5988 Web Linking
 */
class Links implements Value {
  private $links= [];

  /**
   * Parser helper function
   *
   * @param  text.Tokenizer $st
   * @param  string $tokens
   * @return string
   * @throws lang.FormatException
   */
  private function expect($st, $tokens) {
    $parsed= $st->nextToken($tokens);
    if (null === $parsed || false === strpos($tokens, $parsed)) {
      throw new FormatException('Expected ['.$tokens.'], have '.\xp::stringOf($parsed));
    }
    return $parsed;
  }

  /**
   * Parses a Link header into a links structure
   *
   * @param  string|webservices.rest.Link[] $arg
   * @throws lang.FormatException If a string is passed and it's malformed
   */
  public function __construct($arg) {
    if (is_array($arg)) {
      $this->links= $arg;
    } else {
      $this->links= $this->parse($arg);
    }
  }

  /**
   * Parse an input string
   *
   * @param  string $header
   * @return webservices.rest.Link[]
   * @throws lang.FormatException If the header is malformed
   */
  private function parse($header) {
    $links= [];
    $st= new StringTokenizer($header, '<>', true);
    do {
      $this->expect($st, '<');
      $uri= $st->nextToken('>');
      $this->expect($st, '>');

      $params= [];
      do {
        if (',' === $this->expect($st, ';,')) break;

        $param= ltrim($st->nextToken('='));
        $this->expect($st, '=');
        if ('"' === ($value= $st->nextToken('";,'))) {
          $value= $st->nextToken('"');
          $this->expect($st, '"');
        }
        $params[$param]= $value;
      } while ($st->hasMoreTokens());

      $links[]= new Link($uri, $params);
    } while ($st->nextToken('<'));
    return $links;
  }

  /**
   * Create a Links instance from a header; returning an empty collection if
   * `NULL` is passed.
   *
   * @param  ?string $header
   * @return self
   * @throws lang.FormatException If the header is malformed
   */
  public static function in($header) {
    return new self(null === $header ? [] : (string)$header);
  }

  /**
   * Returns a map of link URIs to parameters, optionally restricted to a given search
   *
   * @param  [:string] $search If omitted, all links are returned
   * @return iterable
   */
  public function all($search= null) {
    if (null === $search) {
      foreach ($this->links as $link) {
        yield $link;
      }
    } else {
      foreach ($this->links as $link) {
        foreach ($search as $param => $compare) {
          if (!$link->present($param) || (null !== $compare && $compare !== $link->param($param))) continue 2;
        }
        yield $link;
      }
    }
  }

  /**
   * Searches for the first link URI by a given search
   *
   * @param  [:string] $search
   * @param  string $default
   * @return string
   */
  public function uri($search, $default= null) {
    foreach ($this->all($search) as $link) {
      return $link->uri();
    }
    return $default;
  }

  /**
   * Creates a lookup map to by a given link parameter
   *
   * @param  string $param
   * @return [:webservices.rest.Link]
   */
  public function map($param) {
    $map= [];
    foreach ($this->links as $link) {
      if ($link->present($param)) $map[$link->param($param)]= $link;
    }
    return $map;
  }

  /**
   * Compares this links to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->links, $value->links) : 1;
  }

  /** @return string */
  public function hashCode() {
    return 'L'.Objects::hashOf($this->links);
  }

  /** @return string */
  public function toString() {
    $s= nameof($this)."@[\n";
    foreach ($this->links as $link) {
      $s.= '  '.$link->toString()."\n";
    }
    return $s.']';
  }
}
