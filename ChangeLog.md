Rest client change log
======================

## ?.?.? / ????-??-??

* Fixed *Creation of dynamic property [...] is deprecated* - @thekid
* Merged PR #31: Migrate to new testing library - @thekid

## 5.3.0 / 2022-09-30

* Merged PR #30: Add RestResource::uri() - @thekid

## 5.2.0 / 2022-09-04

* Merged PR #28: Support segments in TestEndpoint routes - @thekid

## 5.1.1 / 2022-08-26

* Fixed issue #27: Cannot deserialize from application/rdap+json - @thekid

## 5.1.0 / 2022-06-03

* Merged PR #24: Add possibility to modify read and connect timeouts
  (@thekid)

## 5.0.2 / 2022-03-30

* Fixed exchanging log categories - @thekid

## 5.0.1 / 2022-03-09

* Merged PR #23: Fix missing port - @thekid

## 5.0.0 / 2022-02-26

This release brings compression handling to this library. Algorithms
supported by the setup are transmitted with the *Accept-Encoding* header.
Handling for compressed response data is determined by looking at the
*Content-Encoding* header. **Heads up:** If you've been manually doing
this before, read the "BC break and refactoring" section in #22.

* Fixed "Creation of dynamic property" warnings in PHP 8.2 - @thekid
* Merged PR #22: Compression, implementing feature request #21. Adds a
  dependency on the new `xp-forge/compression` library.
  (@thekid)

## 4.0.0 / 2021-12-03

This major release makes the result API, which has proven to be far more
useful than the previous low-level one, the default. This makes a clean
cut but also breaks backward compatibility, and all your code will need
to be thoroughly checked before migrating!

* Merged PR #20: Fold result into response. The `value()` and `links()`
  methods' behavior has changed, `result()` has been removed.
  (@thekid)

## 3.2.0 / 2021-12-01

* Merged PR #19: Testability, adding a `webservices.rest.TestEndpoint`
  class which can be used for testing purposes
  (@thekid)

## 3.1.0 / 2021-11-13

* Merged PR #18: Add support for bearer tokens embedded directly in base
  URI, e.g. `https://token@example.org/api/v1`
  (@thekid)

## 3.0.0 / 2021-10-21

This major release drops compatibility with XP 9-SERIES. XP 9.0.0 was
released 2017-09-24, more than 4 years ago and has no support for PHP 8,
released roughly a year ago at the time of writing.

* **Heads up**: Minimum required XP version is 10, see xp-framework/rfc#341
  (@thekid)
* Added compatibility with XP 11, `xp-framework/logging` version 11.0.0
  and `xp-forge/json` version 5.0.0
  (@thekid)

## 2.3.0 / 2021-09-11

* Merged PR #17 - Implement file uploads. The new `upload()` method in the
  `webservices.rest.RestResource` class initiates a multipart/form-data
  request, to which both files and parameters can be added.
  (@thekid)

## 2.2.1 / 2021-08-16

* Fixed PHP 8.1 compatibility by declaring `getIterator()` with correct
  return type. See issue #16.
  (@thekid)

## 2.2.0 / 2021-06-03

* Added `webservices.rest.Endpoint::headers()` accessor - @thekid

## 2.1.0 / 2021-05-30

* Merged PR #15: Deprecate high-level functions on low-level response
  (@thekid)
* Added `webservices.rest.Result::status()` method to access HTTP status
  (@thekid)
* Added `webservices.rest.Result::link()` method to access links with a
  given `rel` attribute, see for example how GitHub implements pagination:
  https://docs.github.com/en/rest/overview/resources-in-the-rest-api#pagination
  (@thekid)
* Merged PR #14: Fluent result interface. This pull request adds a new
  `RestResponse::result()` method which returns `Result` instances with
  high-level access to responses including handling of unexpected status
  codes along typical REST usage patterns.
  (@thekid)

## 2.0.2 / 2021-05-30

* Fixed issue #13: PHP 8.1 warnings - @thekid

## 2.0.1 / 2020-12-27

* Fixed issue #12: Errors for responses without content type - @thekid

## 2.0.0 / 2020-04-10

This major release drops PHP 5 support. PHP 5 has been EOL since the end
2018, see https://www.php.net/eol.php. Dropping support for it enables us
to make use a variety of PHP 7 features.

* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Converted `newinstance` to anonymous classes
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 1.0.3 / 2020-04-10

* Made compatible with `xp-forge/uri` version 2.0.0 - @thekid

## 1.0.2 / 2020-04-10

* Implemented RFC #335: Remove deprecated key/value pair annotation syntax
  (@thekid)

## 1.0.1 / 2019-12-01

* Made compatible with XP 10 - @thekid

## 1.0.0 / 2019-01-26

The first release to be used in production. The API has now stabilized.

* Added `RestResponse::links()` method to access the *Link* header
  (@thekid)
* Added method to supply headers to be sent with every request using
  `Endpoint::with()`.
  (@thekid)

## 0.8.0 / 2019-01-22

* Merged PR #11: Implement support for ndjson - @mikey179

## 0.7.3 / 2018-12-25

* Fixed wrapping of exceptions from `execute()` in `RestException`s.
  (@thekid)

## 0.7.2 / 2018-12-21

* Fixed issue #9: Added missing RestException class - @thekid

## 0.7.1 / 2018-11-05

* Added compatibility with older xp-framework/logging releases - @thekid

## 0.7.0 / 2018-11-05

* Merged PR #8: RestFormat - @thekid
* Merged PR #7: Logging - @thekid
* Improved `Endpoint::connecting()` to also accept callables using either
  the array or "Class::method" string syntax.
  (@thekid)

## 0.6.0 / 2018-11-04

* Merged PR #6: Cookies - @thekid

## 0.5.1 / 2018-11-02

* Merged PR #5: Add new static `Links::in()` method which accepts null
  and returns an empty header; thus simplifying its usage.
  (@thekid)

## 0.5.0 / 2018-11-02

* Merged PR #4: Parse "Link" headers as defined per RFC 5988 - @thekid

## 0.4.1 / 2018-08-30

* Fixed parameters supplied in resource appearing twice in request URL
  (@thekid)

## 0.4.0 / 2018-08-30

* Default mime type for resources' `post()`, `put()` and `patch()` methods
  to `application/x-www-form-urlencoded`.
  (@thekid)
* Fixed `application/x-www-form-urlencoded` format not serializing data
  (@thekid)

## 0.3.0 / 2018-08-30

* Allowed transferring `NULL` as payload - @thekid
* Fixed *Too few arguments to {closure}()* when using buffered transfers
  (@thekid)

## 0.2.0 / 2018-08-30

* **Heads up:** All classes are now inside the package `webservices.rest`!
  (@thekid)
* Fixed issue #1: Incompatible types when using rest-client together
  with rest-api library
  (@thekid)

## 0.1.0 / 2018-08-30

* Hello World! First release - @thekid