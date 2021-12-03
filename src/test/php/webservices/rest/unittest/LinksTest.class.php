<?php namespace webservices\rest\unittest;

use lang\FormatException;
use unittest\{Assert, Expect, Test, Values};
use webservices\rest\{Link, Links};

class LinksTest {

  #[Test, Values([['<http://example.com/?page=2>;rel="next"'], ['<http://example.com/?page=2>; rel="next"'], ['<http://example.com/?page=2>; rel="next"; hreflang=de'], ['<http://example.com/?page=3>; rel="next", <http://example.com/?page=1>; rel="prev"'], ['<http://example.com/?page=1>; rel="next"; title="next chapter"'], ['<http://example.com/?page=1>; rel="next"; title="a;b"'], ['<http://example.com/?page=1>; rel="next"; title="a,b"']])]
  public function can_create($header) {
    new Links($header);
  }

  #[Test]
  public function can_create_from_array() {
    $list= [new Link('http://example.com/?page=2', ['rel' => 'next'])];
    Assert::equals($list, iterator_to_array((new Links($list))->all()));
  }

  #[Test, Expect(FormatException::class), Values([[null], [''], ['<>'], ['<http://example.com/?page=2'], ['<http://example.com/?page=2>; rel'], ['<http://example.com/?page=2>; rel="next']])]
  public function malformed($header) {
    new Links($header);
  }

  #[Test]
  public function all() {
    $links= new Links('<http://example.com/?page=2>; rel="next"');
    Assert::equals([new Link('http://example.com/?page=2', ['rel' => 'next'])], iterator_to_array($links->all()));
  }

  #[Test]
  public function all_with_rel() {
    $links= new Links('<http://example.com/?page=2>; rel="next", <http://example.com/>; title="Home"');
    Assert::equals([new Link('http://example.com/?page=2', ['rel' => 'next'])], iterator_to_array($links->all(['rel' => null])));
  }

  #[Test, Values([null, ''])]
  public function in($header) {
    $links= Links::in('<http://example.com/?page=2>; rel="next"');
    Assert::equals([new Link('http://example.com/?page=2', ['rel' => 'next'])], iterator_to_array($links->all()));
  }

  #[Test]
  public function in_can_handle_empty() {
    Assert::equals([], iterator_to_array(Links::in(null)->all()));
  }

  #[Test, Values([[['rel' => 'prev'], ['rel' => 'next']], ['prev', 'next']])]
  public function uri_by_rel($prev, $next) {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/?page=1>; rel="prev"');
    Assert::equals(
      ['http://example.com/?page=1', 'http://example.com/?page=3'],
      [$links->uri($prev), $links->uri($next)]
    );
  }

  #[Test, Values([[['rel' => 'prev']], ['prev']])]
  public function uri_by_non_existant_rel_returns_null($search) {
    $links= new Links('<http://example.com/?page=2>; rel="next"');
    Assert::null($links->uri($search));
  }

  #[Test, Values([[['rel' => 'prev']], ['prev']])]
  public function uri_by_non_existant_rel_returns_default($search) {
    $links= new Links('<http://example.com/?page=2>; rel="next"');
    Assert::equals('http://example.com', $links->uri($search, 'http://example.com'));
  }

  #[Test]
  public function mapping_by_rel() {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/?page=3>; rel="last", <http://example.com/?page=1>; rel="prev"');
    Assert::equals(
      [
        'next' => new Link('http://example.com/?page=3', ['rel' => 'next']),
        'last' => new Link('http://example.com/?page=3', ['rel' => 'last']),
        'prev' => new Link('http://example.com/?page=1', ['rel' => 'prev'])
      ],
      $links->map('rel')
    );
  }

  #[Test]
  public function mapping_by_title_excludes_links_without_title() {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/>; title="Home"');
    Assert::equals(
      ['Home' => new Link('http://example.com/', ['title' => 'Home'])],
      $links->map('title')
    );
  }

  #[Test]
  public function string_representation() {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/>; title="Home"');
    Assert::equals(
      "webservices.rest.Links@[\n".
      "  webservices.rest.Link<http://example.com/?page=3>; rel=\"next\"\n".
      "  webservices.rest.Link<http://example.com/>; title=\"Home\"\n".
      "]",
      $links->toString()
    );
  }
}