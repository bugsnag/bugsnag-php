Upgrading
=========

## 3.x to 4.x

#### Setting Endpoints

The way endpoints will be configured has been change slightly, in order to make sure all of your requests get send to the correct place.  You'll no longer be able to pass the `endpoint` parameter to your `Bugsnag\Client::make` calls, and should instead use the [`setEndpoints` configuration option](https://docs.bugsnag.com/platforms/php/other/configuration-options/#endpoints).  If you've previously used the `BUGSNAG_ENDPOINT` environment variable, it's now been renamed to `BUGSNAG_NOTIFY_ENDPOINT`, and will only work in conjunction with the `BUGSNAG_SESSION_ENDPOINT` environment variable, to make sure that both endpoints are being used correctly.

Old way:

```
$bugsnag = Bugsnag\Client::make("YOUR_API_KEY", "http://bugsnag-notify.example.com);
$bugsnag->setSessionEndpoint("http://bugsnag-session.example.com);
```

New way:

```
$bugnsag = Bugsnag\Client::make("YOUR_API_KEY");
$bugsnag->setEndpoints(
    "http://bugsnag-notify.example.com",
    "http://bugsnag-session.example.com"
);
```

#### Configuring the GuzzleHttp Client

If you've previously customized the GuzzleHttp Client we use to deliver notifications, you'll no longer be able to pass it in to a `new Bugsnag\Client` call. Instead you'll need to add it to Bugsnag using the [`setGuzzleClient` configuration option.](https://docs.bugsnag.com/platforms/php/other/configuration-options/#guzzle-client)

Old way:

```
$bugsnag = new Bugsnag\Client(new Bugsnag\Configuration("YOUR_API_KEY"), null, $myCustomGuzzle);
```

New way:

```
$bugsnag = new Bugsnag\Client::make("YOUR_API_KEY");
$bugsnag->setGuzzleClient($myCustomGuzzle);
```

#### Deprecation

The `deploy` method has been removed, so in order to report a new version build to bugsnag you should use the `build` method, [as described here.](https://docs.bugsnag.com/platforms/php/other/#tracking-releases)

Old way:

```
$bugsnag->deploy(
    "https://github.com/owner/repo",           // Your repository
    "master",                                  // The branch being deployed from, this has been removed from the build API
    "52097f461bf76a824212eb11de53467c094d0cd4" // The revision, or commit hash, of the deployment
);
```

New way:

```
$bugsnag->build(
    "https://github.com/owner/repo",            // Your repository
    "52097f461bf76a824212eb11de53467c094d0cd4", // The revision, or commit hash, of the build
    "github",                                   // The provider of the source control repository
    "Joe Summer"                                // The name of the person or machine making the build
);
```

## 2.x to 3.x

*Our PHP library has gone through some major improvements, and there are some small changes you'll need to make to get onto the new version.*

#### PHP 5.5+

We now require PHP 5.5 or higher. If you're using an older version of PHP, you can still use v2. We will continue to maintain v2 along side v3. For more information, see the [legacy PHP integration guide](https://docs.bugsnag.com/platforms/php/other/legacy/).

#### Namespaces

We are now using namespaces. Simply replace `Bugsnag_` with `Bugsnag\`, and you're good to go.

#### Deprecation

We've removed any deprecated functionality. The main thing to watch out for is the removal of the `setUseSSL` function. You should instead just provide URIs that include the scheme. This change will likely only affect enterprise users.

The method for setting the application type (`setType`) has also been removed in favor of `setAppType` to make the intent clearer.

#### Configuration

We've changed how our configuration system works. You can now build up our config object in a similar way to how you configured the client in v2, and then pass that as the first parameter when you construct the client object. In addition, we've removed some configuration options in favour of using our new notification pipeline system. Now you can register multiple callbacks to have maximum flexibility. We've also switched to using [Guzzle](http://guzzlephp.org), so you can change the base URI and proxy details by directly interacting with guzzle. For more information, see the [advanced configuration guide](https://docs.bugsnag.com/platforms/php/other/advanced-client-configuration).

#### Customizing handled errors

We have changed how you attach metadata and update the severity of handled errors in `Bugsnag::notify`. Previously metadata and severity were additional parameters to the `notify` method, now the method takes an optional callable with the Error object, so these properties and more can be changed directly. See the documentation for [reporting handled errors](https://docs.bugsnag.com/platforms/php/other/reporting-handled-errors/) for more information.
