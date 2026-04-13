Contributing
============

-   [Fork](https://help.github.com/articles/fork-a-repo) the [notifier on github](https://github.com/bugsnag/bugsnag-laravel)
-   Build and test your changes using `make build` and `make test`
-   Commit and push until you are happy with your contribution
-   [Make a pull request](https://help.github.com/articles/using-pull-requests)
-   Thanks!

Example apps
============

Test the notifier by running the application locally.

[Install composer](http://getcomposer.org/doc/01-basic-usage.md), and then cd into `example/php` and start the server:

    composer install
    php index.php

Releasing
=========

1. Merge all outstanding PRs to go into the release.
1. Create a new release branch from `next`, named in the format `release/v1.x.x`.
1. Bump the version in `src/Configuration.php`.
1. Update the CHANGELOG.md, and README if appropriate.
1. Open a pull request into `master` and get it approved.
1. Merge the pull request using a message of the form "Release v1.x.x".
1. Pull the latest `master` branch locally.
1. Build a new phar package by running `make package`. 
1. Create a new release on GitHub, attaching the phar.
