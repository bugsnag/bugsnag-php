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
4. Check out a new branch and commit your changes
5. Open a pull request for the release
6. Once merged, pull the latest changes and then tag the release:
    ```
    git tag v3.x.x
    git push --tags
    ```
7. Build a new phar package by running `make package` and attach it to the GitHub release.
8. Ensure `utility/bugsnag-prepend.php` works with the example php project and attach it to the Github release.
9. Update the setup guides for PHP (and its frameworks) with any new content.
