<?php namespace webservices\rest;

use webservices\rest\format\{FormUrlencoded, Json, NdJson, Unsupported};

/**
 * Formats registry
 *
 * @test  xp://webservices.rest.unittest.FormatsTest
 */
class Formats {
  private $matches= [], $patterns= [];

  /**
   * Returns defaults
   *
   * @return self
   */
  public static function defaults() {
    $self= new self();
    $self->matches[NdJson::MIMETYPE]= new NdJson();
    $self->patterns['#^application/vnd\.(.+)\+json$#']= $self->matches['application/json']= new Json();
    $self->matches['application/x-www-form-urlencoded']= new FormUrlencoded();
    return $self;
  }

  /**
   * Adds a mime type
   *
   * @param  string $mime
   * @param  webservices.rest.format.Format $format
   * @return self
   */
  public function with($mime, $format) {
    $this->matches[$mime]= $format;
    return $this;
  }

  /**
   * Adds a mime type pattern
   *
   * @param  string $pattern Pattern, using `*` as placeholder for one or more characters
   * @param  webservices.rest.format.Format $format
   * @return self
   */
  public function matching($pattern, $format) {
    $this->patterns['#^'.str_replace('\*', '(.+)', preg_quote($pattern, '#')).'$#']= $format;
    return $this;
  }

  /**
   * Returns a type for a given header
   *
   * @param  ?string|webservices.rest.RestFormat $header
   * @return webservices.rest.format.Format
   */
  public function named($header) {
    if (null === $header) return new Unsupported('(no content type)');

    $mime= substr($header, 0, strcspn($header, ';'));    // FIXME: What to do with charset?

    // Check for direct match
    if (isset($this->matches[$mime])) return $this->matches[$mime];

    // Check patterns
    foreach ($this->patterns as $pattern => $format) {
      if (preg_match($pattern, $mime)) return $format;
    }

    return new Unsupported($mime);
  }
}