Rest Client
========================================================================

[![Build status on GitHub](https://github.com/xp-forge/rest-client/workflows/Tests/badge.svg)](https://github.com/xp-forge/rest-client/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/rest-client/version.svg)](https://packagist.org/packages/xp-forge/rest-client)

REST client

Usage
-----

The `Endpoint` class serves as the entry point to this API. Create a new instance of it with the REST service's endpoint URL and then invoke its `resource()` method to work with the resources.

### Creating: post

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');
$result= $api->resource('users')->post(['name' => 'Test'], 'application/json');

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
$exists= $api->resource('users/1549')->head()->match([
  200 => true,
  404 => false
]);

// Return user object, raising an UnexpectedStatus exception for any
// statuscode outside of the range 200-299.
$user= $api->resource('users/self')->get()->value();

// Same as above, but returns NULL for 404s instead of an exception
$user= $api->resource('users/{0}', [$id])->get()->optional();

// Pass parameters
$list= $api->resource('user')->get(['page' => 1, 'per_page' => 50])->value();

// Access pagination via `Link: <...>; rel="next"` header
$resource= 'groups';
do {
  $result= $this->endpoint->resource($resource)->get(['per_page' => 200]);
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
$updated= $resource->put(['name' => 'Tested', 'login' => $mail])->value();

// Resources can be reused!
$updated= $resource->patch(['name' => 'Changed'])->value();
```

### Deleting: delete

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');

// Pass segments, map 204 to true, 404 to null, raise UnexpectedStatus
// exception otherwise
$api->resource('users/{id}', $user)->delete()->match([
  204 => true,
  404 => null
]);
```

### Uploads

Multipart file uploads are initiated by the `upload()` method, may include parameters and can upload from any input stream.

```php
use io\File;
use io\streams\MemoryInputStream;
use webservices\rest\Endpoint;

$stream= new MemoryInputStream('Hello');
$file= new File(...);
$endpoint= new Endpoint($url);

$result= $endpoint->resource('files')->upload()
  ->pass('tc', 'accepted')
  ->transfer('letter', $stream, 'letter.txt', 'text/plain')
  ->transfer('cv', $file->in(), $file->filename)
  ->finish()
  
;
```

### Deserialization

Automatic result deserialization is supported by passing a type to the `value()` method.

```php
use com\example\api\types\User;

$result= $api->resource('users/{0}', [$id])->get();

// If a type is passed, the result will be unmarshalled to an object
$map= $result->value();
$object= $result->value(User::class);

// Same for optional, but map and object will be NULL for 404s
$map= $result->optional();
$object= $result->optional(User::class);

// Works with any type from the XP typesystem, e.g. arrays of objects
$list= $api->resource('users')->get()->value('org.example.User[]');
```

### Error handling

Operations on the `Result` class raise `UnexpectedStatus` exceptions. Here's how to access their status and reason:

```php
use webservices\rest\UnexpectedStatus;
use util\cmd\Console;

// In unexpected cases
try {
  $user= $api->resource('users/self')->get()->value();
} catch (UnexpectedStatus $e) {
  Console::writeLine('Unexpected ', $e->status(), ': ', $e->reason());
}

// More graceful handling
$result= $api->resource('users/self')->get();
if ($error= $result->error()) {
  Console::writeLine('Unexpected ', $result->status(), ': ', $error);
} else {
  $user= $result->value();
}
```

### Authentication

Basic authentication is supported by embedding the credentials in the endpoint URL:

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://user:pass@api.example.com/');
```

Bearer tokens can also be embedded in the endpoint URL:

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://token@api.example.com/');
```

Other header-based authentication values can be passed along as follows:

```php
use webservices\rest\Endpoint;

$api= (new Endpoint('https://api.example.com/'))->with(['X-API-Key' => $key]);
```

### Compression

This library handlees compressed data transparently, sending an *Accept-Encoding* header containing compression algorithms supported in the PHP setup (*based on loaded extensions like e.g. [zlib](https://www.php.net/zlib)*) and using the *Content-Encoding* response header to determine which algorithm to select.

```php
use webservices\rest\Endpoint;
use io\streams\Compression;

// Detect supported compression algorithms and set "Accept-Encoding" accordingly
$endpoint= new Endpoint($api);

// Send "Accept-Encoding: identity", indicating the server should not compress
$endpoint= (new Endpoint($api))->compressing(Compression::$NONE);

// Send "Accept-Encoding: gzip, br"
$endpoint= (new Endpoint($api))->compressing(['gzip', 'br']);

// Do not send an "Accept-Encoding" header, i.e. no preference is expressed
$endpoint= (new Endpoint($api))->compressing(null);
```

### Testability

This library also includes facilities to ease writing unittests for code making REST API calls. By using the *TestEndpoint* class and supplying it with routes it should respond to, various scenarios can be easily tested without the need for HTTP protocol and I/O overhead.

```php
use webservices\rest\TestEndpoint;

$endpoint= new TestEndpoint([
  '/users/6100' => function($call) {
    return $call->respond(200, 'OK', ['Content-Type' => 'application/json'], '{
      "id": 6100,
      "username": "binford"
    }');
  },
  'POST /users' => function($call) {
    return $call->respond(201, 'Created', ['Location' => '/users/6100']);
  },
]);

$response= $endpoint->resource('/users/me')->get();
// Responds with HTTP status 200 and the above JSON payload
```