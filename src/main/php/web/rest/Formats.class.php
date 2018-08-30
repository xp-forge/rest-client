<?php namespace web\rest;

use web\rest\format\FormUrlencoded;
use web\rest\format\Json;
use web\rest\format\Unsupported;

/**
 * Formats registry
 *
 * @test  xp://web.rest.unittest.FormatsTest
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
    $self->patterns['#^application/vnd\.(.+)\+json$#']= $self->matches['application/json']= new Json();
    $self->matches['application/x-www-form-urlencoded']= new FormUrlencoded();
    return $self;
  }

  /**
   * Adds a mime type
   *
   * @param  string $mime
   * @param  web.rest.format.Format $format
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
   * @param  web.rest.format.Format $format
   * @return self
   */
  public function matching($pattern, $format) {
    $this->patterns['#^'.str_replace('\*', '(.+)', preg_quote($pattern, '#')).'$#']= $format;
    return $this;
  }

  /**
   * Returns a type for a given header
   *
   * @param  string $header
   * @return web.rest.format.Format
   */
  public function named($header) {
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