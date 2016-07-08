Changelog
=========

## 3.0.1 (2016-07-08)

### Bug Fixes

* Lowered the minimum PHP version to 5.5.0
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#295](https://github.com/bugsnag/bugsnag-php/pull/295)

## 3.0.0 (2016-07-07)

This is a major refactor to make the library clearer and easier to use. The
minimum PHP version supported has been updated to 5.5.9. For upgrading
instructions, see
[the upgrading guide](https://github.com/bugsnag/bugsnag-php/blob/master/UPGRADING.md#2x-to-3x).

### Enhancements

* Added a pipeline system for loading request information, app information, and
  other metadata. Each component can be individually loaded.

* Make request resolution customizable
  [#99](https://github.com/bugsnag/bugsnag-php/issues/99)

* Replaced transport handling with [Guzzle](http://guzzlephp.org)

* `notify()`, `notifyException()` and `notifyError()` now accept a callable
  –instead of `metaData` and `severity`– which can be used to modify any of
  the [properties of an error report](http://docs.bugsnag.com.dev/platforms/php/other/customizing-error-reports/#the-report-object).

* Deprecated methods from v2 have been removed

* Namespaced the library under `Bugsnag`

### Bug Fixes

* Every bug

## 2.9.1 (2016-07-06)

### Bug Fixes

* Fixed broken header parsing
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#260](https://github.com/bugsnag/bugsnag-php/pull/260)

## 2.9.0 (2016-06-24)

## Enhancements

* Support completely overriding the user on errors
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#209](https://github.com/bugsnag/bugsnag-php/pull/209)

## Bug Fixes

* Deal with large payloads and batching correctly
  [Graham Campbell](https://github.com/GrahamCampbell)
  [64c00d3](https://github.com/bugsnag/bugsnag-php/commit/64c00d3f9b872f4d87f0ea03e950831e55bba8d2)

* Completed the fix for double input reading
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#199](https://github.com/bugsnag/bugsnag-php/pull/199)

## 2.8.0 (2016-06-21)

## Enhancements

* Add ability to optionally merge metadata with existing properties, otherwise
  overwrite them
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#162](https://github.com/bugsnag/bugsnag-php/pull/162)

## Bug Fixes

* Fix regression where an empty throwable message was cast to an empty string
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#159](https://github.com/bugsnag/bugsnag-php/pull/159)

* Restore support for PHP 5.2
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#148](https://github.com/bugsnag/bugsnag-php/pull/148)

* Enforce integer type on stack frame line numbers
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#157](https://github.com/bugsnag/bugsnag-php/pull/157)

## 2.7.3 (2016-06-20)

* Improve performance when using cURL
  [aleemb](https://github.com/aleemb)
  [#115](https://github.com/bugsnag/bugsnag-php/pull/115)

* Add stricter type checking for name and message contents
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#130](https://github.com/bugsnag/bugsnag-php/pull/130)

## 2.7.2 (2016-06-17)

### Bug Fixes

* Fix filtering in the dashboard by IP address
  [William Starling](https://github.com/foygl)
  [#123](https://github.com/bugsnag/bugsnag-php/pull/123)

## 2.7.1 (2016-06-02)

### Bug Fixes

* Fix failure to merge array due to type conflicts
  [Jesse Collis](https://github.com/jessedc)
  [#118](https://github.com/bugsnag/bugsnag-php/pull/118)

## 2.7.0 (2016-03-09)

### Enhancements

* Support `timeout` settings outside of cURL
  [Ivan Shalganov](https://github.com/bzick)
  [#111](https://github.com/bugsnag/bugsnag-php/pull/111)

* Support PUT request payloads
  [forgadenny](https://github.com/forgandenny)
  [#83](https://github.com/bugsnag/bugsnag-php/pull/83)

### Bug Fixes

* Remove exception code filtering
  [Duncan Hewett](https://github.com/duncanhewett)
  [#113](https://github.com/bugsnag/bugsnag-php/pull/113)

2.6.1 (2016-01-28)
-----

### Bug Fixes

* Fixes an error thrown when sending an `Error` instance using PHP 7
  [Petr Bugyík](https://github.com/o5)
  [#110](https://github.com/bugsnag/bugsnag-php/pull/110)

* Fix error which occurs when `$_SERVER['SERVER_PORT']` is unset
  [Michael Curry](https://github.com/michaelcurry)
  [#109](https://github.com/bugsnag/bugsnag-php/pull/109)

2.6.0 (2015-12-23)
-----

### Enhancements

* Add support for PHP 7's Throwable
  [Chris Stone](https://github.com/cmstone)
  [#106](https://github.com/bugsnag/bugsnag-php/pull/106)

* Fix errors which arise from from error payloads not encoded using UTF-8
  [GaetanNaulin](https://github.com/GaetanNaulin)
  [#104](https://github.com/bugsnag/bugsnag-php/pull/104)
  [#105](https://github.com/bugsnag/bugsnag-php/pull/105)

2.5.6
-----
-   Added a debug flag to help diagnose notification problems

2.5.5
-----
-   Ensure no unnecessary code is executed when errors should be skipped

2.5.4
-----
-   Fix HHVM support for release 2.5.3

2.5.3
-----
-   Add support for custom curl options

2.5.2
-----
-   Add support for setHostname

2.5.1
-----
-   Extract file and line numbers better for crashes in eval'd code

2.5.0
-----
-   Collect and send snippets of source code to Bugsnag for easier debugging
-   Update `setEndpoint` to accept full URLs
-   Add support for `Error#setGroupingHash` to customize error grouping in
    `setBeforeNotify` functions

2.4.0
-----
-   Don't send $_ENV by default

2.3.1
-----
-   Warn if neither curl or fopen are available

2.3.0
-----
-   Remove cURL requirement, fallback to using fopen() if cURL not available

2.2.10
------
-   Remove default for `setProjectRoot` since it was sometimes overzealous

2.2.9
-----
-   Fix boolean metadata handling

2.2.8
-----
-   Fix various metadata-encoding bugs

2.2.7
-----
-   Allow configuration of projectRoot stripping from stacktraces

2.2.6
-----
-   Fix calling `mb_detect_encoding` on non-objects

2.2.5
-----
-   Remove deprecated "fatal" severity state

2.2.4
-----
-   Prepare 'severity' feature for release

2.2.3
-----
-   Fix invalid utf-8 errors for people using iso-8859-1 by default.

2.2.2
-----
-   Make frames public on the stacktrace.

2.2.1
-----
-   Log any curl errors to the command line, increase default timeout to 10s

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
