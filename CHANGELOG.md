Changelog
=========

2.2.0
-----
-   Support previous exceptions on PHP >= 5.3.0
-   Allow overriding notifier
-   Use manual loading in place of custom autoloading

2.1.4
-----
-   Make cURL timeout configurable (thanks pauloschilling)

2.1.3
-----
-   Fix crash during stacktrace generation that happened when a closure was
    the first stackframe.

2.1.2
-----
-   Add `ErrorTypes::getLevelsForSeverity` function to fetch an
    `error_reporting` bitmask for a particular Bugsnag severity

2.1.1
-----
-   Fix crash during stacktrace generation for frameworks that have their own
    `shutdown_handler` method (eg. Laravel)

2.1.0
-----
-   Add `setAppType` for sending app type (script, request, resque, etc)
-   Add `setUser` for sending structured user data
-   Automatically send the severity level of each PHP error
-   Added ability to chain setters (eg $bugsnag->setUser(...)->setReleaseStage(...))

2.0.4
-----
-   Add hostname collection to help with debugging

2.0.3
-----
-   Add `setBatchSending` function to disable batch sending of errors at the
    end of each request

2.0.2
-----
-   Fix bug which caused `setNotifyReleaseStages` being ignored

2.0.1
-----
-   Fix minor request meta-data issues introduced in 2.0.0

2.0.0
-----
-   Backwards-incompatible rewrite (using non-static access)
-   Full suite of tests and Travis CI testing on PHP 5.2+
-   Add `setBeforeNotify` functionality to add meta-data or execute code
    before each error is sent to Bugsnag

1.0.9
-----
-   Add `setAutoNotify` function to allow disabling of automatic error handling
-   Fix bug where error reporting level was being ignored for fatal errors

1.0.8
-----
-   Added a `setMetaData` function for sending custom data with every error

1.0.7
-----
-   Don't default `notifyReleaseStages` to anything to reduce confusion

1.0.6
-----
-   Fix PHP 5.2 bug with missing constants

1.0.5
-----
-   Protect against missing $_SERVER variables

1.0.4
-----
-   Send JSON POST params to Bugsnag if available
-   Send HTTP headers to Bugsnag if available

1.0.3
-----
-   Remove unnecessary post to Bugsnag when error list is empty

1.0.2
-----
-   Fix bug with 'internal' stacktrace lines (missing line/file)

1.0.1
-----
-   Renamed default error classes for clarity
-   Batch-send errors at the end of each request
-   `Bugsnag::errorHandler` now respects PHP's `error_reporting` settings
-   Added `setErrorReportingLevel` function to override PHP's error_reporting settings

1.0.0
-----
-   First public release
