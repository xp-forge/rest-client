Rest Client
========================================================================

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-forge/rest-client.png)](http://travis-ci.org/xp-forge/rest-client)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/rest-client/version.png)](https://packagist.org/packages/xp-forge/rest-client)

REST client

Usage
-----

The `Endpoint` class serves as the entry point to this API. Create a new instance of it with the REST service's endpoint URL and then invoke its `resource()` method to work with the resources.

### Creating: post

```php
use webservices\rest\Endpoint;
use lang\IllegalStateException;

$api= new Endpoint('https://api.example.com/');
$response= $api->resource('users')->post(['name' => 'Test'], 'application/json');

// Check status codes
if (201 !== $response->status()) {
  throw new IllegalStateException('Could not create user!');
}

// Retrieve location header
$url= $response->location();
```

### Reading: get / head

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');

// Unmarshal to object by optionally passing a type; otherwise returned as map
$user= $api->resource('users/self')->get()->value(User::class);

// Test for existance with HEAD
$exists= (200 === $api->resource('users/1549')->head()->status());

// Pass parameters
$list= $api->resource('user')->get(['page' => 1, 'per_page' => 50])->value();

// Access pagination
$resource= 'groups';
do {
  $response= $this->endpoint->resource($resource)->get(['per_page' => 200]);
  foreach ($response->value() as $group) {
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
$updated= $resource->put(['name' => 'Tested', 'login' => $mail])->value();

// Resources can be reused!
$updated= $resource->patch(['name' => 'Changed'])->value();
```

### Deleting: delete

```php
use webservices\rest\Endpoint;

$api= new Endpoint('https://api.example.com/');

// Pass segments
$api->resource('user/{id}', ['id' => 6100])->delete();
```

### Deserialization

The REST API supports automatic result deserialization by passing a type to the `value()` method.

```php
use com\example\api\types\Person;

$person= $response->value(Person::class);
$strings= $response->value('string[]');
$codes= $response->value('[:int]');
```

### Authentication

Basic authentication is supported by embedding the credentials in the endpoint URL:

```php
use webservices\rest\Endpoint;

$api= new Endpoint('http://user:pass@api.example.com/');
```