# GraphQL Websocket Concrete5 Composer Package
You will find all infos on my old [repo](https://github.com/lemonbrain-mk/concrete5_next_gen). I will delete it as fast as possible.

This project is a concrete5 package that is powered entirely by [composer](https://getcomposer.org). It adds the ability to use graphql and websockets on a standard apache hosting with concrete5.

To install this package on a [composer based concrete5](https://github.com/concrete5/composer) site, make sure you already have `composer/installers` then run:

```sh
$ composer require lemonbrain/concrete5_graphql_websocket
```

Then install the package

```sh
$ ./vendor/bin/concrete5 c5:package-install concrete5_graphql_websocket
```

## To dos
* remove package: stop and remove all websocket servers
* replace the old repo with a new sample project which uses this package and is setuped with concrete5 composer
* transfer documentation to this repo