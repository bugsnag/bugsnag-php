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

1.  Copy `bugsnag.php` to your PHP project and require it in your app:

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

    *Note: You can also call call `Bugsnag::errorHandler` and call 
    `Bugsnag::exceptionHandler` directly if you already have your own error 
    handler functions, simply pass all parameters through.*


Sending Custom Data With Exceptions
-----------------------------------

It is often useful to send additional meta-data about your app, such as 
information about the currently logged in user, along with any
exceptions, to help debug problems. 

To send custom data, simply call `Bugsnag::setMetaDataFunction`:

```php
// Define a bugsnag metadata callable
function bugsnag_metadata() {
    return array(
      "user" => array(
        name => "Bob Hoskins",
        email => "bob@example.com",
        role => "Super Mario"
      )
    );
}

// Now tell Bugsnag about the new metadata callable
Bugsnag::setMetaDataFunction("bugsnag_metadata");
```


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

Both of these functions can also be passed an optional $metaData parameter,
which should take the same format as the return value of
[setMetaDataFunction](#setmetadatafunction) below.


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
error. In order to do this, we send along a userId with every exception. 
By default we will generate a unique ID and send this ID along with every 
exception from an individual device.
    
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

###setNotifyReleaseStages

By default, we will only notify Bugsnag of exceptions that happen when 
your `releaseStage` is set to be "production". If you would like to 
change which release stages notify Bugsnag of exceptions you can
call `setNotifyReleaseStages`:
    
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

###setMetaDataFunction

Set a custom metadata generation function to call before notifying
Bugsnag of an error. You can use this to add custom tabs of data
to each error on your Bugsnag dashboard.

This function should return an array of arrays, the outer array should 
represent the "tabs" to display on your Bugsnag dashboard, and the inner
array should be the values to display on each tab, for example:

```php
Bugsnag::setMetaDataFunction("bugsnag_metadata");

function bugsnag_metadata() {
    return array(
        "user" => array(
            "name" => "James",
            "email" => "james@example.com"
        )
    );
}
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

The Bugsnag Android notifier is free software released under the MIT License. 
See [LICENSE.txt](https://github.com/bugsnag/bugsnag-php/blob/master/LICENSE.txt) for details.