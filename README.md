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
$url= $response->result()->location();
```

### Reading: get / head

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');

// Unmarshal to object by optionally passing a type; otherwise returned as map
$user= $api->resource('users/self')->get()->result()->value(User::class);

// Return a user object on success or NULL for 404s
$user= $api->resource('users/{0}', [$id])->get()->result()->optional(User::class);

// Test for existance with HEAD
$exists= (200 === $api->resource('users/1549')->head()->status());

// Pass parameters
$list= $api->resource('user')->get(['page' => 1, 'per_page' => 50])->result()->value();

// Access pagination
$resource= 'groups';
do {
  $response= $this->endpoint->resource($resource)->get(['per_page' => 200]);
  foreach ($response->result()->value() as $group) {
    yield $group['id'] => $group;
  }
} while ($resource= $response->links()->uri(['rel' => 'next']));
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
$api->resource('user/{id}', ['id' => 6100])->delete()->result()->match([
  204 => true,
  404 => null
]);
```

### Deserialization

The REST API supports automatic result deserialization by passing a type to the `value()` method.

```php
use com\example\api\types\Person;

$person= $result->value(Person::class);
$strings= $result->value('string[]');
$codes= $result->value('[:int]');
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