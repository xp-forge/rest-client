Rest Client
========================================================================

[![Build status on GitHub](https://github.com/xp-forge/rest-client/workflows/Tests/badge.svg)](https://github.com/xp-forge/rest-client/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/rest-client/version.png)](https://packagist.org/packages/xp-forge/rest-client)

REST client

Usage
-----

The `Endpoint` class serves as the entry point to this API. Create a new instance of it with the REST service's endpoint URL and then invoke its `resource()` method to work with the resources.

### Creating: post

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');
$result= $api->resource('users')->post(['name' => 'Test'], 'application/json')->result();

// Get location from created response, raising an UnexpectedStatus
// exception for any statuscode outside of the range 200-299.
$url= $result->location();

// Same as above, but handle 201 *AND* 200 status codes - see
// https://stackoverflow.com/questions/1860645
$id= $result->match([
  200 => fn($r) => $r->value()['id'],
  201 => fn($r) => (int)basename($r->location())
]);
```

### Reading: get / head

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');

// Test for existance with HEAD, raising UnexpectedStatus exceptions
// for any status code other than 200 and 404.
$exists= $api->resource('users/1549')->head()->result()->match([
  200 => true,
  404 => false
]);

// Return user object, raising an UnexpectedStatus exception for any
// statuscode outside of the range 200-299.
$user= $api->resource('users/self')->get()->result()->value();

// Same as above, but returns NULL for 404s instead of an exception
$user= $api->resource('users/{0}', [$id])->get()->result()->optional();

// Pass parameters
$list= $api->resource('user')->get(['page' => 1, 'per_page' => 50])->result()->value();

// Access pagination via `Link: <...>; rel="next"` header
$resource= 'groups';
do {
  $result= $this->endpoint->resource($resource)->get(['per_page' => 200])->result();
  foreach ($result->value() as $group) {
    yield $group['id'] => $group;
  }
} while ($resource= $result->link('next'));
```

### Updating: put / patch

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');
$resource= $api->resource('users/self')
  ->sending('application/json')
  ->accepting('application/json')
;

// Default content type and accept types set on resource used
$updated= $resource->put(['name' => 'Tested', 'login' => $mail])->result()->value();

// Resources can be reused!
$updated= $resource->patch(['name' => 'Changed'])->result()->value();
```

### Deleting: delete

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');

// Pass segments, map 204 to true, 404 to null, raise UnexpectedStatus
// exception otherwise
$api->resource('users/{id}', $user)->delete()->result()->match([
  204 => true,
  404 => null
]);
```

### Uploads

```php
use io\File;
use webservices\rest\Endpoint;

$file= new File(...);
$endpoint= new Endpoint($url);

$result= $endpoint->resource('files')->upload()
  ->pass('tc', 'accepted')
  ->transfer('upload', $file->in(), $file->filename)
  ->finish()
  ->result()
;
```

### Deserialization

The REST API supports automatic result deserialization by passing a type to the `value()` method.

```php
use com\example\api\types\User;

$result= $api->resource('users/{0}', [$id])->get()->result();

// If a type is passed, the result will be unmarshalled to an object
$map= $result->value();
$object= $result->value(User::class);

// Same for optional, but map and object will be NULL for 404s
$map= $result->optional();
$object= $result->optional(User::class);

// Works with any type from the XP typesystem, e.g. arrays of objects
$list= $api->resource('users')->get()->result()->value('org.example.User[]');
```

### Error handling

Operations on the `Result` class raise `UnexpectedStatus` exceptions. Here's how to access their status and reason:

```php
use webservices\rest\UnexpectedStatus;
use util\cmd\Console;

// In unexpected cases
try {
  $user= $api->resource('users/self')->get()->result()->value();
} catch (UnexpectedStatus $e) {
  Console::writeLine('Unexpected ', $e->status(), ': ', $e->reason());
}

// More graceful handling
$result= $api->resource('users/self')->get()->result();
if ($error= $result->error()) {
  Console::writeLine('Unexpected ', $result->status(), ': ', $error);
}
```

### Authentication

Basic authentication is supported by embedding the credentials in the endpoint URL:

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://user:pass@api.example.com/');
```

Header-based authentication can be passed along as follows:

```php
use webservices\rest\Endpoint;

$api= (new Endpoint('https://api.example.com/'))->with(['Authorization' => 'Bearer '.$token]);
```