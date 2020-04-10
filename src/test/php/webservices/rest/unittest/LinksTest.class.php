<?php namespace webservices\rest\unittest;

use lang\FormatException;
use webservices\rest\{Link, Links};

class LinksTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['<http://example.com/?page=2>;rel="next"'],
  #  ['<http://example.com/?page=2>; rel="next"'],
  #  ['<http://example.com/?page=2>; rel="next"; hreflang=de'],
  #  ['<http://example.com/?page=3>; rel="next", <http://example.com/?page=1>; rel="prev"'],
  #  ['<http://example.com/?page=1>; rel="next"; title="next chapter"'],
  #  ['<http://example.com/?page=1>; rel="next"; title="a;b"'],
  #  ['<http://example.com/?page=1>; rel="next"; title="a,b"']
  #])]
  public function can_create($header) {
    new Links($header);
  }

  #[@test]
  public function can_create_from_array() {
    $list= [new Link('http://example.com/?page=2', ['rel' => 'next'])];
    $this->assertEquals($list, iterator_to_array((new Links($list))->all()));
  }

  #[@test, @expect(FormatException::class), @values([
  #  [null],
  #  [''],
  #  ['<>'],
  #  ['<http://example.com/?page=2'],
  #  ['<http://example.com/?page=2>; rel'],
  #  ['<http://example.com/?page=2>; rel="next']
  #])]
  public function malformed($header) {
    new Links($header);
  }

  #[@test]
  public function all() {
    $links= new Links('<http://example.com/?page=2>; rel="next"');
    $this->assertEquals([new Link('http://example.com/?page=2', ['rel' => 'next'])], iterator_to_array($links->all()));
  }

  #[@test]
  public function all_with_rel() {
    $links= new Links('<http://example.com/?page=2>; rel="next", <http://example.com/>; title="Home"');
    $this->assertEquals([new Link('http://example.com/?page=2', ['rel' => 'next'])], iterator_to_array($links->all(['rel' => null])));
  }

  #[@test, @values([null, ''])]
  public function in($header) {
    $links= Links::in('<http://example.com/?page=2>; rel="next"');
    $this->assertEquals([new Link('http://example.com/?page=2', ['rel' => 'next'])], iterator_to_array($links->all()));
  }

  #[@test]
  public function in_can_handle_empty() {
    $this->assertEquals([], iterator_to_array(Links::in(null)->all()));
  }

  #[@test]
  public function uri_by_rel() {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/?page=1>; rel="prev"');
    $this->assertEquals(
      ['http://example.com/?page=1', 'http://example.com/?page=3'],
      [$links->uri(['rel' => 'prev']), $links->uri(['rel' => 'next'])]
    );
  }

  #[@test]
  public function uri_by_non_existant_rel_returns_null() {
    $links= new Links('<http://example.com/?page=2>; rel="next"');
    $this->assertEquals(null, $links->uri(['rel' => 'prev']));
  }

  #[@test]
  public function uri_by_non_existant_rel_returns_default() {
    $links= new Links('<http://example.com/?page=2>; rel="next"');
    $this->assertEquals('http://example.com', $links->uri(['rel' => 'prev'], 'http://example.com'));
  }

  #[@test]
  public function mapping_by_rel() {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/?page=3>; rel="last", <http://example.com/?page=1>; rel="prev"');
    $this->assertEquals(
      [
        'next' => new Link('http://example.com/?page=3', ['rel' => 'next']),
        'last' => new Link('http://example.com/?page=3', ['rel' => 'last']),
        'prev' => new Link('http://example.com/?page=1', ['rel' => 'prev'])
      ],
      $links->map('rel')
    );
  }

  #[@test]
  public function mapping_by_title_excludes_links_without_title() {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/>; title="Home"');
    $this->assertEquals(
      ['Home' => new Link('http://example.com/', ['title' => 'Home'])],
      $links->map('title')
    );
  }

  #[@test]
  public function string_representation() {
    $links= new Links('<http://example.com/?page=3>; rel="next", <http://example.com/>; title="Home"');
    $this->assertEquals(
      "webservices.rest.Links@[\n".
      "  webservices.rest.Link<http://example.com/?page=3>; rel=\"next\"\n".
      "  webservices.rest.Link<http://example.com/>; title=\"Home\"\n".
      "]",
      $links->toString()
    );
  }
}