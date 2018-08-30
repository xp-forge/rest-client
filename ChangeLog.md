Rest client change log
======================

## ?.?.? / ????-??-??

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