Changelog
=========

## 3.30.0 (2025-06-??)

### Enhancements

* Set default endpoints based on API key
  [#679](https://github.com/bugsnag/bugsnag-php/pull/679)

## 3.29.3 (2025-03-06)

### Fixes

* Redact user metadata according to redacted keys configuration
  [#675](https://github.com/bugsnag/bugsnag-php/pull/675)

## 3.29.2 (2025-01-13)

This release should ensure compatibility with PHP 8.4 by removing the usage of certain
deprecated features by:
- Removing `E_STRICT` from the error list in PHP 8.4+
- Removing type annotations from parameters defaulting to `null`. Comment type annotations will continue to be present and accurate

### Fixes

* Ensure PHP 8.4 compatibility
  [#672](https://github.com/bugsnag/bugsnag-php/pull/672)


## 3.29.1 (2023-05-10)

### Fixes

* Avoid deprecated `utf8_encode` function
  [gabrielrbarbosa](https://github.com/gabrielrbarbosa)
  [#661](https://github.com/bugsnag/bugsnag-php/pull/661)
  [#662](https://github.com/bugsnag/bugsnag-php/pull/662)

## 3.29.0 (2022-10-19)

### Enhancements

* The maximum number of breadcrumbs can now be configured between 0-100 (inclusive)
  [#652](https://github.com/bugsnag/bugsnag-php/pull/652)

* Raised the default maximum number of breadcrumbs to 50
  [#652](https://github.com/bugsnag/bugsnag-php/pull/652)

* Add a `Report::getFeatureFlags` method to allow accessing feature flags in callbacks
  [#653](https://github.com/bugsnag/bugsnag-php/pull/653)

## 3.28.0 (2022-05-18)

### Enhancements

* New APIs to support feature flag and experiment functionality. For more information, please see https://docs.bugsnag.com/product/features-experiments.
  [#646](https://github.com/bugsnag/bugsnag-php/pull/646)

## 3.27.0 (2022-02-07)

### Enhancements

* Improve serialisation of backed enums. Previously a backed enum would JSON encode to their backing value, but will now include the enum name like `EnumName::CaseName (value)`
  [#639](https://github.com/bugsnag/bugsnag-php/pull/639)

### Fixes

* A number of errors in docblocks have been corrected
  [xPaw](https://github.com/xPaw)
  [#633](https://github.com/bugsnag/bugsnag-php/pull/633)
  [#637](https://github.com/bugsnag/bugsnag-php/pull/637)
* Handle serialising pure enums when added as metadata. Previously a pure enum would be JSON encoded as `null`, but will now be converted to a string like `EnumName::CaseName`
  [#639](https://github.com/bugsnag/bugsnag-php/pull/639)
* Remove use of the deprecated `strftime` function
  [#640](https://github.com/bugsnag/bugsnag-php/pull/640)
* Fix session throttling when storage backend doesn't store integers
  [#643](https://github.com/bugsnag/bugsnag-php/pull/643)

## 3.26.1 (2021-09-09)

### Fixes

* Avoid JSON encoding event payloads more than once, where possible
  [#628](https://github.com/bugsnag/bugsnag-php/pull/628)
* Add the `ReturnTypeWillChange` to `Bugsnag\Breadcrumbs\Recorder` to avoid a deprecation in PHP 8.1
  [#630](https://github.com/bugsnag/bugsnag-php/pull/630)

## 3.26.0 (2021-02-10)

### Enhancements

* Out of memory errors will now be reported by increasing the memory limit by 5 MiB. Use the new `memoryLimitIncrease` configuration option to change the amount of memory, or set it to `null` to disable the increase entirely.
  [#621](https://github.com/bugsnag/bugsnag-php/pull/621)

* Add a "discard classes" configuration option that allows events to be discarded based on the exception class name or PHP error name
  [#622](https://github.com/bugsnag/bugsnag-php/pull/622)

* Add a "redacted keys" configuration option. This is similar to `filters` but allows both strings and regexes. String matching is exact but case-insensitive. Regex matching allows for partial and wildcard matching.
  [#623](https://github.com/bugsnag/bugsnag-php/pull/623)

### Deprecations

* The `filters` configuration option is now deprecated as `redactedKeys` can express everything that filters could and more.

## 3.25.0 (2020-11-25)

### Enhancements

* Add a default timeout & connect_timeout to Guzzle instances created by bugsnag-php. This does not apply if you are providing a custom Guzzle instance.
  [#616](https://github.com/bugsnag/bugsnag-php/pull/616)

## 3.24.0 (2020-10-27)

This release changes how Bugsnag detects the error suppression operator in combination with the `errorReportingLevel` configuration option, for PHP 8 compatibility. Bugsnag's `errorReportingLevel` must now be a subset of `error_reporting` — i.e. every error level in `errorReportingLevel` must also be in `error_reporting`

If you use the `errorReportingLevel` option, you may need to change your Bugsnag or PHP configuration in order to report all expected errors. See [PR #611](https://github.com/bugsnag/bugsnag-php/pull/611) for more details

### Enhancements

* Improve the display of breadrumbs in the Bugsnag app by including milliseconds in timestamps
  [#612](https://github.com/bugsnag/bugsnag-php/pull/612)

### Fixes

* Make `Configuration::shouldIgnoreErrorCode` compatible with PHP 8 by requiring the `errorReportingLevel` option to be a subset of `error_reporting`
  [#611](https://github.com/bugsnag/bugsnag-php/pull/611)

## 3.23.1 (2020-10-19)

This release fixes several issues with Bugsnag's error handlers that caused it to affect the behaviour of shutdown functions ([#475](https://github.com/bugsnag/bugsnag-php/issues/475)) and CLI script exit codes ([#523](https://github.com/bugsnag/bugsnag-php/issues/523)). This does not apply if you are using the Laravel or Symfony integrations, as they use separate methods of error handling.

These bugs have been fixed and so registering Bugsnag's error handlers should not cause any change to PHP's default behaviour. Make sure that [PHP's `display_errors` option](https://www.php.net/manual/en/errorfunc.configuration.php#ini.display-errors) is disabled in production environments, as exceptions and errors will now go through PHP's default handling.

### Fixes

* The previous error and exception handler will now always be called

* Exceptions raised by the previous exception handler will now be reported

* The default PHP exception handler will now be called, unless this is prevented by a previous exception handler

* Avoid calling `method_exists` on non-objects
  [#603](https://github.com/bugsnag/bugsnag-php/pull/603)

### Deprecations

* `Bugsnag\Handler::registerWithPrevious` has been deprecated as calling previous error/exception handlers is now the default behaviour. Calls to `registerWithPrevious` can be safely replaced with `Bugsnag\Handler::register`

## 3.23.0 (2020-09-14)

### Enhancements

* Filters are now applied case insensitively, e.g. 'password' will now also match 'PASSWORD'
  [#595](https://github.com/bugsnag/bugsnag-php/pull/595)

* Cookies are now filtered from events by default
  [#596](https://github.com/bugsnag/bugsnag-php/pull/596)

* HTTP basic auth headers are filtered from events by default
  [#597](https://github.com/bugsnag/bugsnag-php/pull/597)

## 3.22.0 (2020-08-20)

### Enhancements

* Include all HTTP headers in request metadata
  [#588](https://github.com/bugsnag/bugsnag-php/pull/588)

* The `Client` and `SessionTracker` now share a single Guzzle instance
  [#587](https://github.com/bugsnag/bugsnag-php/pull/587)

### Deprecations

* `Client::ENDPOINT`
  This is ambiguous as we have three separate endpoints
  Use `Configuration::NOTIFY_ENDPOINT` instead

* `HttpClient::PAYLOAD_VERSION`
  This is ambiguous as there is a session payload version too
  Use `HttpClient::NOTIFY_PAYLOAD_VERSION` instead

* `Report::PAYLOAD_VERSION`
  As above. This was also unused by the notifier
  Use `HttpClient::NOTIFY_PAYLOAD_VERSION` instead

* `SessionTracker::$SESSION_PAYLOAD_VERSION`
  Use `HttpClient::SESSION_PAYLOAD_VERSION` instead

* `HttpClient::send`
  Use `HttpClient::sendEvents` instead

* `HttpClient::build`
  Use `HttpClient::getEventPayload` instead

* `HttpClient::postJson`
  Use `HttpClient::deliverEvents` instead

* Using the `base_uri`/`base_url` on a Guzzle instance
  The base URI is ambiguous as there are three separate endpoints which could be used, therefore all Guzzle requests now use absolute URIs. We will extract the `base_uri`/`base_url` Guzzle option if one is set and use it as the notification endpoint URI, however this will be removed in the next major version
  Set the notification endpoint manually with `Configuration::setNotifyEndpoint` instead

* Calling `HttpClient::getHeaders` without providing a payload version
  This is deprecated as the version is ambiguous between the notification payload version and session payload version
  Call this with the correct `HttpClient` payload version constants instead

* `Client::getSessionClient` and `Configuration::getSessionClient`
  This method is dangerous to use as there is now only one Guzzle instance used across every request, so changing this client would also affect the notification client. Changes to this Guzzle client will now be ignored
  Use the `$guzzle` parameter of the `Client` constructor to customise the Guzzle client instead

* `SessionData::$client`
  The `SessionData` class will be passed a `SessionTracker` instead of a `Client` instance in its constructor in the next major version

* `SessionTracker` should be constructed with a `HttpClient`
  The `SessionTracker` class should now always be passed a `HttpClient`
  In this version it will construct its own `HttpClient` if one is not provided
  In the next major version, this fallback will be removed and passing a `HttpClient` will be mandatory

## 3.21.0 (2020-04-29)

### Enhancements

* Use full class names in breadcrumbs
  [#578](https://github.com/bugsnag/bugsnag-php/pull/578)

* Added support for getting the "original error"
  [#576](https://github.com/bugsnag/bugsnag-php/pull/576)

### Bug Fixes

* Fixed session tracker possibly raising a warning on PHP 7.2 and above
  [#579](https://github.com/bugsnag/bugsnag-php/pull/579)

## 3.20.0 (2020-02-26)

### Enhancements

* Added support for Guzzle 7
  [#567](https://github.com/bugsnag/bugsnag-php/pull/567)

### Bug Fixes

* Added support for PHP 7.3 and 7.4
  [#552](https://github.com/bugsnag/bugsnag-php/pull/552)
  [#556](https://github.com/bugsnag/bugsnag-php/pull/556)

* Only try to search for the CA bundle on PHP 5.5
  [#566](https://github.com/bugsnag/bugsnag-php/pull/566)

## 3.19.0 (2019-11-18)

### Enhancements

* Added a strategy pattern for the Client shutdown behaviour
  [#547](https://github.com/bugsnag/bugsnag-php/pull/547)

* Replaced the dependency on phpdotenv with a simple "read" function that
  follows the same strategy ($_ENV, $_SERVER, getenv())
 [#511](https://github.com/bugsnag/bugsnag-php/pull/551)

* Removed the length restriction on the Breadcrumb "name" field to match the
  API and allow large names to be sent.
  [#544](https://github.com/bugsnag/bugsnag-php/pull/554)

### Bug Fixes

* Make session delivery obey the release stage rules used by notify
  [#542](https://github.com/bugsnag/bugsnag-php/pull/542)

## 3.18.0 (2019-08-28)

### Enhancements

* More robust environment variable fetching
  [GrahamCampbell](https://github.com/GrahamCampbell)
  [#530](https://github.com/bugsnag/bugsnag-php/pull/530)

* Support suppressed errors using "@" to ignore exceptions
  [#531](https://github.com/bugsnag/bugsnag-php/pull/537)

* Bump max payload limit to follow Bugsnag's backend limit to enable larger errors
  [#538](https://github.com/bugsnag/bugsnag-php/pull/538)

## 3.17.0 (2019-05-28)

Add the version of PHP to report information to allow easier diagnosis of version-specific issues.

### Enhancements

* Add PHP version string to report and session payloads (device.runtimeVersions)
  [#525](https://github.com/bugsnag/bugsnag-php/pull/525)

## 3.16.0 (2019-01-29)

### Enhancements

* Add `setProjectRootRegex` to enable directly setting the Project Root regex
  [Javier Pérez Cid](https://github.com/jpcid)
  [#514](https://github.com/bugsnag/bugsnag-php/pull/514)

### Bug Fixes

* Check `php_uname` isn't disabled before attempting to retrieve hostname
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#513](https://github.com/bugsnag/bugsnag-php/pull/513)

## 3.15.2 (2019-01-10)

### Bug Fixes

* Improve performance by removing reflection calls from Client to Configuration
  [#510](https://github.com/bugsnag/bugsnag-php/pull/510)

* Prevent automatic collection of POST form data
  [#511](https://github.com/bugsnag/bugsnag-php/pull/511)

## 3.15.1 (2018-12-19)

### Bug Fixes

* Allow modifying stack frame values by reference
  [#507](https://github.com/bugsnag/bugsnag-php/pull/507)

## 3.15.0 (2018-11-1)

### Enhancements

* Added `insertBefore` to `Pipeline` and `getPipeline` to `Client` to facilitate
  callback ordering
  [#495](https://github.com/bugsnag/bugsnag-php/pull/495)

## 3.14.1 (2018-07-20)

### Bug Fixes

* Fix `notifyError(name, message, callback)` and `notifyException(ex, callback)`
  to ensure that reports are sent when callback != null

## 3.14.0 (2018-07-06)

### Enhancements

* Added `registerMiddleware` method for external middleware additions
  [#478](https://github.com/bugsnag/bugsnag-php/pull/478)

## 3.13.0 (2018-06-06)

### Enhancements

* Added `GET` parameter support
  [#472](https://github.com/bugsnag/bugsnag-php/pull/472)

### Fixes

* Speed up error handler
  [#476](https://github.com/bugsnag/bugsnag-php/pull/476)

## 3.12.1 (2018-02-28)

### Fixes

* Updated the payload body version to 4.0
  [#467](https://github.com/bugsnag/bugsnag-php/pull/467)

* Ensured reports always have a valid name
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#463](https://github.com/bugsnag/bugsnag-php/pull/463)

* Better name handling in breadcrumbs
  [#427](https://github.com/bugsnag/bugsnag-php/pull/427)

## 3.12.0 (2018-01-24)

### Enhancements

* Deprecated the deploy API in favor of the build API
  Calls to `deploy` will now redirect to `build`.
  [#454](https://github.com/bugsnag/bugsnag-php/pull/454)

### Fixes

* Re-added apiKey parameter to payload for older API versions
  [#459](https://github.com/bugsnag/bugsnag-php/pull/459)

## 3.11.0 (2018-01-09)

### Enhancements

* Add support for tracking sessions and overall crash rate by setting
  `autoCaptureSessions` in configuration options. In addition, sessions can be
  indicated manually using `Client->startSession()`

## 3.10.0 (2017-12-14)

### Enhancements

* Add 'addMetaData' method to reports
  [#430](https://github.com/bugsnag/bugsnag-php/pull/430)

## 3.9.0 (2017-11-23)

### Enhancements

* Added Codeigniter example
  [Don Pinkus](https://github.com/donpinkus)

### Fixes

* Ensured `severity_reason` has sensible defaults
  [#421](https://github.com/bugsnag/bugsnag-php/pull/421)

## 3.8.1 (2017-10-04)

### Fixes

* Ensured Console Requests can handle empty argument array
  [#413](https://github.com/bugsnag/bugsnag-php/pull/413)

## 3.8.0 (2017-10-02)

### Enhancements

* Added data acquisition for handled/unhandled feature
  [#406](https://github.com/bugsnag/bugsnag-php/pull/406)

## 3.7.0 (2017-09-13)

### Enhancements

* Added issue template to repository
  [#401](https://github.com/bugsnag/bugsnag-php/pull/401)

* Added Architecture guide to aid contributors
  [#402](https://github.com/bugsnag/bugsnag-php/pull/402)

* Added ConsoleRequest handler for extracting data out of console-run applications
  [#398](https://github.com/bugsnag/bugsnag-php/pull/398)

## 3.6.0 (2017-08-10)

### Enhancements

* Allow configuring strip path regex
  [Paul Kuruvilla](https://github.com/rohitpaulk)
  [#390](https://github.com/bugsnag/bugsnag-php/pull/390)

* Chain previously defined error handlers. This behavior can be activated using
  `registerWithPrevious`
  [Matt Terwilliger](https://github.com/mterwill)
  [#395](https://github.com/bugsnag/bugsnag-php/pull/395)

* Make `Bugsnag\Handler` constructor public instead of protected to allow
  configuring custom clients
  [Adam Vercimak](https://github.com/Vmak11)
  [#381](https://github.com/bugsnag/bugsnag-php/pull/381)

## 3.5.1 (2017-06-07)

### Bug Fixes

* Fix in-project detection and path stripping support for paths
  with repeated strings.
  [Michael Tibben](https://github.com/mtibben)
  [#384](https://github.com/bugsnag/bugsnag-php/pull/384)

## 3.5.0 (2017-04-06)

### Enhancements

* Include more deeply nested exception causes in reports

## 3.4.0 (2016-09-05)

### Enhancements

* Return false from the error handler
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#361](https://github.com/bugsnag/bugsnag-php/pull/361)

* Add support for fallback app types
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#363](https://github.com/bugsnag/bugsnag-php/pull/363)

## 3.3.0 (2016-08-19)

### Enhancements

* Add support for stacktrace modification
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#351](https://github.com/bugsnag/bugsnag-php/pull/351)

### Bug Fixes

* Fixed custom endpoints that mounted on a subfolder
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#349](https://github.com/bugsnag/bugsnag-php/pull/349)

## 3.2.0 (2016-08-08)

### Enhancements

* Integrate support for deploy tracking
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#317](https://github.com/bugsnag/bugsnag-php/pull/317)

* Add support for sending breadrumbs with error reports
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#323](https://github.com/bugsnag/bugsnag-php/pull/323)

* Support both Guzzle 5 and 6
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#332](https://github.com/bugsnag/bugsnag-php/pull/332)

* Use Server API type as the default app type
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#342](https://github.com/bugsnag/bugsnag-php/pull/342)

### Bug Fixes

* Ensure the error timestamp is correct
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#337](https://github.com/bugsnag/bugsnag-php/pull/337)

## 3.1.1 (2016-07-25)

### Bug Fixes

* Fixed certificate verification on Windows
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#318](https://github.com/bugsnag/bugsnag-php/pull/318)

## 3.1.0 (2016-07-22)

### Bug Fixes

* Fallback to localhost when HTTP_HOST is not set
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#302](https://github.com/bugsnag/bugsnag-php/pull/302)

* Fixed certificate verification on some systems
  [Graham Campbell](https://github.com/GrahamCampbell)
  [#308](https://github.com/bugsnag/bugsnag-php/pull/308)

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
  the [properties of an error report](http://docs.bugsnag.com/platforms/php/other/customizing-error-reports/#the-report-object).

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
