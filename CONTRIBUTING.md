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

1. Commit all outstanding changes
2. Bump the version in `src/Configuration.php`.
3. Update the CHANGELOG.md, and README if appropriate.
4. Commit, tag push
    ```
    git commit -am v3.x.x
    git tag v3.x.x
    git push origin master && git push --tags
    ```
5. Build a new phar package by running `make package` and attach it to the GitHub release.
6. Update the setup guides for PHP (and its frameworks) with any new content.
