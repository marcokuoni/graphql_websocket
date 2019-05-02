# GraphQL Websocket Concrete5 Composer Package
This project is a concrete5 package that is powered entirely by [composer](https://getcomposer.org). It adds the ability to use graphql and websockets on a standard apache hosting with concrete5.

To install this package on a [composer based concrete5](https://github.com/concrete5/composer) site, make sure you already have `composer/installers` then run:

```sh
$ composer require lemonbrain/concrete5_graphql_websocket
```

Then install the package

```sh
$ ./vendor/bin/concrete5 c5:package-install graphql_websocket
```