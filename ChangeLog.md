Rest client change log
======================

## ?.?.? / ????-??-??

## 1.0.0 / 2019-01-22

* Add method to supply headers to be sent with every request using
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