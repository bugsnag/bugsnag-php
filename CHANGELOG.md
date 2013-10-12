Changelog
=========

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