<?php namespace webservices\rest;

use Traversable, IteratorAggregate;
use lang\{ElementNotFoundException, Value};
use util\{Date, Objects, URI};

/**
 * Cookie JAR
 *
 * @test  xp://webservices.rest.unittest.CookiesTest
 * @see   https://tools.ietf.org/html/rfc6265
 * @see   https://tools.ietf.org/html/draft-ietf-httpbis-rfc6265bis-02
 */
class Cookies implements Value, IteratorAggregate {
  public static $EMPTY;
  private $list= [];

  static function __static() {
    self::$EMPTY= new self([]);  // @codeCoverageIgnore
  }

  /** @param webservices.rest.Cookie[]|[:?string] $cookies */
  public function __construct($cookies) {
    $this->update($cookies);
  }

  /**
   * Parse cookies from Set-Cookie headers. Reject cookies from invalid domains,
   * handles prefixes and will not accept secure cookies from insecure sites.
   * However, it does *not* take https://publicsuffix.org/list/ into account!
   *
   * @param  string[] $headers
   * @param  ?util.URI $uri
   * @return self
   */
  public static function parse($headers, $uri) {
    $list= [];
    foreach ($headers as $cookie) {
      $attr= [];
      preg_match('/([^=]+)=("([^"]+)"|([^;]+))?(;(.+))*/', $cookie, $matches);
      if (isset($matches[6])) {
        foreach (explode(';', $matches[6]) as $attribute) {
          $r= sscanf(trim($attribute), "%[^=]=%[^\r]", $name, $value);
          $attr[$name]= 2 === $r ? urldecode($value) : true;
        }
      }

      // Cookies names with the prefixes __Secure- and __Host- can be used only if they are
      // set with the secure directive. In addition, cookies with the __Host-prefix must have
      // a path of "/" (the entire host) and must not have a domain attribute
      if (0 === strncmp($matches[1], '__Host-', 7)) {
        if (!isset($attr['Secure']) || !isset($attr['Path']) || '/' !== $attr['Path'] || isset($attr['Domain'])) continue;
        $name= substr($matches[1], 7);
      } else if (0 === strncmp($matches[1], '__Secure-', 9)) {
        if (!isset($attr['Secure'])) continue;
        $name= substr($matches[1], 9);
      } else {
        $name= $matches[1];
      }

      // Reject cookies if:
      // * They belong to a domain that does not include the origin server
      // * An insecure site tries to set a cookie with a "Secure" directive
      if ($uri && (
        (isset($attr['Domain']) && !preg_match('/^.+'.preg_quote($attr['Domain']).'$/', $uri->host())) ||
        (isset($attr['Secure']) && 'https' !== $uri->scheme())
      )) continue;

      // Normalize domain: If a domain is specified, subdomains are always included.
      // Otherwise, defaults to current host; not including subdomains.
      if (isset($attr['Domain'])) {
        $attr['Domain']= '.'.ltrim($attr['Domain'], '.');
      } else if ($uri) {
        $attr['Domain']= $uri->host();
      }

      $list[]= new Cookie($name, isset($matches[2]) ? urldecode($matches[2]) : null, $attr);
    }
    return new self($list);
  }

  /** @return bool */
  public function present() { return sizeof($this->list) > 0; }

  /** @return self */
  public function clear() { $this->list= []; return $this; }

  /**
   * Update these cookies with a given list of cookies
   *
   * @param  webservices.rest.Cookie[]|[:?string] $cookies
   * @return self
   */
  public function update($cookies) {
    foreach ($cookies as $name => $cookie) {
      if ($cookie instanceof Cookie) {
        $domain= $cookie->domain() ?? '';
        $key= sprintf(
          '#^%s://%s%s/%s#',
          $cookie->secure() ? 'https': 'https?',
          0 === strncmp($domain, '.', 1) ? '.+' : '',
          preg_quote($domain),
          ltrim($cookie->path() ?? '', '/')
        );
        if (null === ($value= $cookie->value())) {
          unset($this->list[$key][$cookie->name()]);
        } else {
          $this->list[$key][$cookie->name()]= $cookie;
        }
      } else {
        $key= '#^https?://[^/]+/#';
        if (null === $cookie) {
          unset($this->list[$key][$name]);
        } else {
          $this->list[$key][$name]= new Cookie($name, $cookie);
        }
      }
    }

    // RFC 6265: The user agent SHOULD sort the cookie-list in the following order:
    // Cookies with longer paths are listed before cookies with shorter paths.
    uksort($this->list, fn($a, $b) => strlen($b) - strlen($a));
    return $this;
  }

  /** Returns all cookies */
  public function getIterator(): Traversable {
    foreach ($this->list as $lookup) {
      foreach ($lookup as $cookie) {
        yield $cookie;
      }
    }
  }

  /**
   * Retrieves non-expired cookies for a given URI.
   *
   * @param  string|util.URI $arg
   * @param  ?util.Date $rel
   * @return iterable
   */
  public function validFor($arg, $rel= null) {
    $uri= $arg instanceof URI ? $arg : new URI($arg);
    $normalized= (string)$uri->canonicalize();
    $rel || $rel= Date::now();

    $yielded= [];
    foreach ($this->list as $key => $lookup) {
      if (preg_match($key, $normalized)) foreach ($lookup as $name => $cookie) {
        if (isset($yielded[$name])) continue;

        $expires= $cookie->expires();
        if (null === $expires || $expires->isAfter($rel)) {
          yield $cookie;
          $yielded[$name]= true;
        }
      }
    }
  }

  /** @return string */
  public function toString() {
    $s= nameof($this)."@{\n";
    foreach ($this->list as $lookup) {
      foreach ($lookup as $cookie) {
        $s.= '  '.str_replace("\n", "\n  ", $cookie->toString())."\n";
      }
    }
    return $s.'}';
  }

  /** @return string */
  public function hashCode() { return Objects::hashOf($this->list); }

  /**
   * Compares this cookie to another given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->list, $value->list) : 1;
  }
}