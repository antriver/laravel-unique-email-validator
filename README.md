# Laravel Unique Email Validator

This provides a Laravel validation rule that improves upon the built in 'unique' rule when preventing the same email
address being used more than once.

Some email providers (Gmail) allow you to use + in the email address to create 'aliases'. For example if your email
address is somename@gmail.com you can also use:
* somename+words@gmail.com
* somename+123@gmail.com
* somename+other.stuff@gmail.com

and they all work. The normal 'unique' rule would allow these as they are all different. This rule sees these all as
the same and will disallow using the same 'somename@gmail.com' account.

Some email providers (Gmail) also allow you to place periods anywhere before the @ sign in the email address and these
will all work. For example if your email address is somename@gmail.com you can also use:
* some.name@gmail.com
* s.o.m.e.name@gmail.com

## Requirements

Your MySQL server must support the non-greedy regex operator. This has been tested with MySQL 8 and MariaDB 10.2 so
those versions or newer should work.

## Installation

    composer require antriver/laravel-unique-email-validator

## Usage
```php
    $this->validate(
        $request,
        [
            // Check for other use of the same email address in the users.email column.
            'email' => [new \Antriver\LaravelUniqueEmailValidator\UniqueEmailRule('users', 'email')],
        ]
    );
```
Like the original unique rule you can specify certain rows should be excluded. This is useful when updating a user and
that should not reject the user's existing registered email address:
```php
    $this->validate(
        $request,
        [
            'email' => [new \Antriver\LaravelUniqueEmailValidator\UniqueEmailRule('users', 'email', ['id' => 123])],
            // Where 123 is the 'id' of the existing user.
        ]
    );
```php
