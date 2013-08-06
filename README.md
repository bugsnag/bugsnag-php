Bugsnag Notifier for PHP
========================

The Bugsnag Notifier for PHP gives you instant notification of errors and
exceptions in your PHP applications.

[Bugsnag](https://bugsnag.com) captures errors in real-time from your web, 
mobile and desktop applications, helping you to understand and resolve them 
as fast as possible. [Create a free account](https://bugsnag.com) to start 
capturing errors from your applications.


How to Install
--------------

1.  Copy [bugsnag.php](https://raw.github.com/bugsnag/bugsnag-php/master/lib/bugsnag.php)
    to your PHP project and require it in your app:

    ```php
    require_once("path/to/bugsnag.php");
    ```

    *Note: If your project uses [Composer](http://getcomposer.org/), you can 
    instead add `bugsnag/bugsnag` as a dependency in your `composer.json`.*

2.  Configure Bugsnag with your API key:

    ```php
    Bugsnag::register("YOUR-API-KEY-HERE");
    ```

3.  Attach Bugsnag's error and exception handlers:

    ```php
    set_error_handler("Bugsnag::errorHandler");
    set_exception_handler("Bugsnag::exceptionHandler");
    ```

    *Note: You can also call `Bugsnag::errorHandler` or 
    `Bugsnag::exceptionHandler` from within your own error handler functions,
    simply pass all parameters through.*


*Note: The Bugsnag PHP notifier requires cURL support to be enabled on your
PHP installation.*


Sending Custom Data With Exceptions
-----------------------------------

It is often useful to send additional meta-data about your app, such as 
information about the currently logged in user, along with any
error or exceptions, to help debug problems. 

To send custom data, you should define a *before-notify* function, 
adding an array of "tabs" of custom data to the $metaData parameter.
For an example, see the [setBeforeNotifyFunction](#setbeforenotifyfunction)
documentation below.


Sending Custom Errors or Non-Fatal Exceptions
---------------------------------------------

You can easily tell Bugsnag about non-fatal or caught exceptions by 
calling `Bugsnag::notifyException`:

```php
Bugsnag::notifyException(new Exception("Something bad happened"));
```

You can also send custom errors to Bugsnag with `Bugsnag.notifyError`:

```php
Bugsnag::notifyError("ErrorType", "Something bad happened here too");
```

Both of these functions can also be passed an optional `$metaData` parameter,
which should take the following format:

```php
$metaData =  array(
    "user" => array(
        "name" => "James",
        "email" => "james@example.com"
    )
);
```


Additional Configuration
------------------------

###setContext

Bugsnag uses the concept of "contexts" to help display and group your
errors. Contexts represent what was happening in your application at the
time an error occurs. By default this will be set to the current request
URL and HTTP method, eg "GET /pages/documentation".

If you would like to set the bugsnag context manually, you can call 
`setContext`:

```php
Bugsnag::setContext("Backport Job");
```

###setUserId

Bugsnag helps you understand how many of your users are affected by each
error. In order to do this, we send along a userId with every error. 
By default we will generate a unique ID and send this ID along with every 
error.
    
If you would like to override this `userId`, for example to set it to be a
username of your currently logged in user, you can call `setUserId`:

```php
Bugsnag::setUserId("leeroy-jenkins");
```

###setReleaseStage

If you would like to distinguish between errors that happen in different
stages of the application release process (development, production, etc)
you can set the `releaseStage` that is reported to Bugsnag.

```php
Bugsnag::setReleaseStage("development");
```
    
By default this is set to be "production".

*Note: If you would like errors from stages other than production to be sent
to Bugsnag, you'll also have to call `setNotifyReleaseStages`.*

###setNotifyReleaseStages

By default, we will only notify Bugsnag of errors that happen in any
`releaseStage` If you would like to change which release stages notify 
Bugsnag of errors you can call `setNotifyReleaseStages`:
    
```php
Bugsnag::setNotifyReleaseStages(array("development", "production"));
```

###setFilters

Sets the strings to filter out from the `metaData` arrays before sending
them to Bugsnag. Use this if you want to ensure you don't send 
sensitive data such as passwords, and credit card numbers to our 
servers. Any keys which contain these strings will be filtered.

```php
Bugsnag::setFilters(array("password", "credit_card"));
```

By default, this is set to be `array("password")`.

###setUseSSL

Enforces all communication with bugsnag.com be made via ssl.

```php
Bugsnag::setUseSSL(true);
```

By default, this is set to be true.

###setBeforeNotifyFunction

Set a custom function to call before notifying Bugsnag of an error.
You can use this to call your own error handling functions, or to add custom
tabs of data to each error on your Bugsnag dashboard.

To add custom tabs of meta-data, simply add to the $metaData array
that is passed as the first parameter to your function, for example:

```php
Bugsnag::setBeforeNotifyFunction("before_bugsnag_notify");

function before_bugsnag_notify($metaData) {
    // Do any custom error handling here

    // Also add some meta data to each error
    $metaData = array(
        "user" => array(
            "name" => "James",
            "email" => "james@example.com"
        )
    );
}
```

###setErrorReportingLevel

Set the levels of PHP errors to report to Bugsnag, by default we'll use
the value of `error_reporting` from your `php.ini` or any value you set
at runtime using the `error_reporting(...)` function.

If you'd like to send different levels of errors to Bugsnag, you can call
`setErrorReportingLevel`:

```php
Bugsnag::setErrorReportingLevel(E_ALL & ~E_NOTICE);
```

See PHP's [error reporting documentation](http://php.net/manual/en/errorfunc.configuration.php#ini.error-reporting)
for allowed values.

###setProjectRoot

We mark stacktrace lines as in-project if they come from files inside your
`projectRoot`. By default this value is automatically set to be
`$_SERVER['DOCUMENT_ROOT']` but sometimes this can cause problems with
stacktrace highlighting. You can set this manually by calling `setProjectRoot`:

```php
Bugsnag::setProjectRoot("/path/to/your/app");
```

If your app has files in many different locations, you can disable the 
projectRoot as follows:

```php
Bugsnag::setProjectRoot(null);
```


PHP Frameworks
--------------

###CakePHP

If you are using CakePHP, installation is easy:

1.  Copy [bugsnag.php](https://raw.github.com/bugsnag/bugsnag-php/master/lib/bugsnag.php)
    into your CakePHP project

2.  Edit `App/Config/core.php`:

    ```php
    // Require Bugsnag
    require_once("path/to/bugsnag.php");

    // Initialize Bugsnag
    Bugsnag::register("YOUR-API-KEY-HERE");

    // Change the default error handler to be Bugsnag
    Configure::write('Error', array(
        'handler' => 'Bugsnag::errorHandler',
        'level' => E_ALL & ~E_DEPRECATED,
        'trace' => true
    ));

    // Change the default exception handler to be Bugsnag
    Configure::write('Exception', array(
        'handler' => 'Bugsnag::exceptionHandler',
        'renderer' => 'ExceptionRenderer',
        'log' => true
    ));
    ```


Reporting Bugs or Feature Requests
----------------------------------

Please report any bugs or feature requests on the github issues page for this
project here:

<https://github.com/bugsnag/bugsnag-php/issues>


Contributing
------------

-   [Fork](https://help.github.com/articles/fork-a-repo) the [notifier on github](https://github.com/bugsnag/bugsnag-php)
-   Commit and push until you are happy with your contribution
-   [Make a pull request](https://help.github.com/articles/using-pull-requests)
-   Thanks!


License
-------

The Bugsnag PHP notifier is free software released under the MIT License. 
See [LICENSE.txt](https://github.com/bugsnag/bugsnag-php/blob/master/LICENSE.txt) for details.