# oxid-esales/graphql-account

[![Build Status](https://flat.badgen.net/travis/OXID-eSales/graphql-account-module/?icon=travis&label=build&cache=300&scale=1.1)](https://travis-ci.com/OXID-eSales/graphql-account-module)
[![PHP Version](https://flat.badgen.net/packagist/php/OXID-eSales/graphql-account/?cache=300&scale=1.1)](https://github.com/oxid-esales/graphql-account-module)

This module provides account related [GraphQL](https://www.graphql.org) queries and mutations for the [OXID eShop](https://www.oxid-esales.com/)

This module is not maintained anymore. Hava a look at new module that have all the functionality of this one and more: https://github.com/OXID-eSales/graphql-storefront-module

## Usage

This assumes you have OXID eShop (at least `oxid-esales/oxideshop_ce: v6.5.0` component, which is part of the `v6.2.0` compilation) up and running.

### Install

```bash
$ composer require oxid-esales/graphql-account
```

After requiring the module, you need to head over to the OXID eShop admin and activate the GraphQL Account module. If
you did not have the `oxid-esales/graphql-base` and `oxid-esales/graphql-catalogue` module already installed, composer
will do that for you, but don't forget to also activate those modules in the OXID eShop admin.

### How to use

A good starting point is to check the [How to use section in the GraphQL Base Module](https://github.com/OXID-eSales/graphql-base-module/#how-to-use)

## Testing

### Linting, syntax check, static analysis and unit tests

```bash
$ composer test
```

### Integration/Acceptance tests

- install this module into a running OXID eShop
- change the `test_config.yml`
  - add `oe/graphql-account` to the `partial_module_paths`
  - set `activate_all_modules` to `true`

```bash
$ ./vendor/bin/runtests
```

## Contributing

You like to contribute? 🙌 AWESOME 🙌\
Go and check the [contribution guidelines](CONTRIBUTING.md)

## Build with

- [GraphQLite](https://graphqlite.thecodingmachine.io/)

## License

GPLv3, see [LICENSE file](LICENSE).
