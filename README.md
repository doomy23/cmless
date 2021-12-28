# Cmless project

Open Source project about creating a small, efficient and customizable **MVC framework** for PHP developers.
This framework was inspired by [Django](https://www.djangoproject.com/), especially for the templates syntax.
It gives the opportunity to create a complete web application without losing much time and it's easy to learn.

## Requirements
- PHP 5.3 or newer
- URL rewriting

## Features
- Template system
- Easy URL rewriting patterns
- Debug mod with traceback
- Customizable 403, 404 and 500 pages
- Template caching system
- Optional queries saver
- CSRF protection
- Basic and extendable User model with login

## Features to come
- Internationalisation and localization
- Support for SQLite and PostgreSQL
- More documentation

## Documentation

Index available [here](docs/index.md).

## Security tips

- Always perform input and output validation. The Template system is very flexible but also susceptible to XXS. Always `htmlspecialchars` in templates and controllers.
- Use CSRF tokens in forms to prevent cross-site scripting.
- Secure your website with HTTPS

## Copyrights and license

The code is released under [MIT license](LICENSE).
Some parts in the front-end belong to their original owners as specified in the licence.
