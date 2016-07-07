Upgrading
=========


## 2.x to 3.x

*Our PHP library has gone through some major improvements, and there are some small changes you'll need to make to get onto the new version.*

#### PHP 5.5.9+

We now require PHP 5.5.9 or higher. If you're using an older version of PHP, you can still use v2. We will containue to maintain v2 along side v3. For more information, see the [legacy PHP integration guide](http://docs.bugsnag.com/platforms/php/legacy).

#### Namespaces

We are now using namespaces. Simply replace `Bugsnag_` with `Bugsnag\`, and you're good to go.

#### Deprecation

We've removed any deprecated functionality. The main thing to watch out for is the removal of the `setUseSSL` function. You should instead just provide URIs that include the scheme. This change will likely only affect enterprise users.

The method for setting the application type (`setType`) has also been removed in favor of `setAppType` to make the intent clearer.

#### Configuration

We've changed how our configuration system works. You can now build up our config object in a similar way to how you configured the client in v2, and then pass that as the first paramater when you construct the client object. In addition, we've removed some configuration options in favour of using our new notification pipeline system. Now you can register multiple callbacks to have maximum flexibility. We've also switched to using [Guzzle](http://guzzlephp.org), so you can change the base URI and proxy details by directly interacting with guzzle. For more information, see the [advanced configuration guide](http://docs.bugsnag.com/platforms/php/other/advanced-client-configuration).

#### Customizing handled errors

We have changed how you attach metadata and update the severity of handled errors in `Bugsnag::notify`. Previously metadata and severity were additional parameters to the `notify` method, now the method takes an optional callable with the Error object, so these properties and more can be changed directly. See the documentation for [reporting handled errors](http://docs.bugsnag.com/platforms/php/other/reporting-handled-errors/) for more information.
