Upgrading
=========

Our PHP library has gone through some major improvements, and there are some small changes you'll need to make to get onto the new version.

1. We now require PHP 5.5.9 or higher. If you're using an older version of PHP, you can still use v2. We will containue to maintain v2 along side v3.
2. We are now using namespaces. Simply replace `Bugsnag_` with `Bugsnag\`, and you're good to go.
3. We've removed any deprecated functionality. The main thing to watch out for is the removal of the `setUseSSL` function. You should instead just provide URIs that include the scheme. This change will likely only affect enterprise users.
4. We've changed how our configuration system works. You can now build up our config object in a similar way to how you configured the client in v2, and then pass that as the first paramater when you construct the client object.
