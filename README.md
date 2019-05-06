# GraphQL Websocket Concrete5 Composer Package
We build a C5 Version with Siler GraphQL, Apollo V2, React and Material UI. checkout the showdown here [concrete5.lemonbrain.ch](https://concrete5.lemonbrain.ch/index.php/person#/)
The idea of this repo is to include GraphQL and Websockets in Concrete5.

The documentation is in the [wiki](https://github.com/lemonbrain-mk/graphql_websocket/wiki)

This project is a concrete5 package that is powered entirely by [composer](https://getcomposer.org). It adds the ability to use graphql and websockets on a standard apache hosting with concrete5.

To install this package on a [composer based concrete5](https://github.com/concrete5/composer) site, make sure you already have `composer/installers` then run:

```sh
$ composer require lemonbrain/concrete5_graphql_websocket
```

Then install the package

```sh
$ ./vendor/bin/concrete5 c5:package-install concrete5_graphql_websocket
```