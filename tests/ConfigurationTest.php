<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit_Framework_TestCase as TestCase;

class ConfigurationTest extends TestCase
{

    use PHPMock;

    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoesNotAcceptBadApiKey()
    {
        new Configuration([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExpcetionMessage Invalid strip path regex: [thisisnotavalidregex
     */
    public function testDoesNotAcceptBadStripPathRegex()
    {
        $this->config->setStripPathRegex('[thisisnotavalidregex');
    }

    public function testNotifier()
    {
        $this->assertSame('Bugsnag PHP (Official)', $this->config->getNotifier()['name']);
        $this->assertSame('https://bugsnag.com', $this->config->getNotifier()['url']);

        $this->config->setNotifier(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->config->getNotifier());
    }

    public function testShouldIgnore()
    {
        $this->config->setErrorReportingLevel(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

        $this->assertTrue($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testShouldNotIgnore()
    {
        $this->config->setErrorReportingLevel(E_ALL);

        $this->assertfalse($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testRootPath()
    {
        $this->assertFalse($this->config->isInProject('/root/dir/afile.php'));

        $this->config->setProjectRoot('/root/dir');

        $this->assertTrue($this->config->isInProject('/root/dir/afile.php'));
        $this->assertFalse($this->config->isInProject('/root'));
        $this->assertFalse($this->config->isInProject('/base/root/dir/afile.php'));

        $this->config->setProjectRoot('/root/dir/');

        $this->assertTrue($this->config->isInProject('/root/dir/afile.php'));
        $this->assertFalse($this->config->isInProject('/root'));
        $this->assertFalse($this->config->isInProject('/base/root/dir/afile.php'));
    }

    public function testRootPathNull()
    {
        $this->config->setProjectRoot('/root/dir');
        $this->config->setProjectRoot(null);

        $this->assertFalse($this->config->isInProject('/root/dir/afile.php'));
        $this->assertFalse($this->config->isInProject('/root'));
        $this->assertFalse($this->config->isInProject('/base/root/dir/afile.php'));
    }

    public function testRootPathSetsStripPath()
    {
        $this->config->setProjectRoot('/foo/bar');

        $this->assertSame('src/thing.php', $this->config->getStrippedFilePath('/foo/bar/src/thing.php'));
        $this->assertSame('/foo/src/thing.php', $this->config->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('x/src/thing.php', $this->config->getStrippedFilePath('x/src/thing.php'));
    }

    public function testRootPathRegex()
    {
        $this->assertFalse($this->config->isInProject('/root/dir/app/afile.php'));

        $this->config->setProjectRootRegex('/^('.preg_quote('/root/dir/app', '/').'|'.preg_quote('/root/dir/lib', '/').')[\\/]?/i');

        $this->assertTrue($this->config->isInProject('/root/dir/app/afile.php'));
        $this->assertTrue($this->config->isInProject('/root/dir/lib/afile.php'));
        $this->assertFalse($this->config->isInProject('/root'));
        $this->assertFalse($this->config->isInProject('/root/dir/other-directory/afile.php'));
        $this->assertFalse($this->config->isInProject('/base/root/dir/app/afile.php'));

        $this->config->setProjectRootRegex('/^('.preg_quote('/root/dir/app/', '/').'|'.preg_quote('/root/dir/lib/', '/').')[\\/]?/i');

        $this->assertTrue($this->config->isInProject('/root/dir/app/afile.php'));
        $this->assertTrue($this->config->isInProject('/root/dir/lib/afile.php'));
        $this->assertFalse($this->config->isInProject('/root'));
        $this->assertFalse($this->config->isInProject('/root/dir/other-directory/afile.php'));
        $this->assertFalse($this->config->isInProject('/base/root/dir/afile.php'));
    }

    public function testRootPathRegexNull()
    {
        $this->config->setProjectRootRegex('/^('.preg_quote('/root/dir/app', '/').'|'.preg_quote('/root/dir/lib', '/').')[\\/]?/i');
        $this->config->setProjectRootRegex(null);

        $this->assertFalse($this->config->isInProject('/root/dir/app/afile.php'));
        $this->assertFalse($this->config->isInProject('/root/dir/lib/afile.php'));
        $this->assertFalse($this->config->isInProject('/root'));
        $this->assertFalse($this->config->isInProject('/root/dir/other-directory/afile.php'));
        $this->assertFalse($this->config->isInProject('/base/root/dir/app/afile.php'));
    }

    public function testRootPathRegexSetsStripPathRegex()
    {
        $this->config->setProjectRootRegex('/^\\/(foo|bar)\\//');

        $this->assertSame('src/thing.php', $this->config->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('src/thing.php', $this->config->getStrippedFilePath('/bar/src/thing.php'));
        $this->assertSame('/baz/src/thing.php', $this->config->getStrippedFilePath('/baz/src/thing.php'));
        $this->assertSame('x/foo/thing.php', $this->config->getStrippedFilePath('x/foo/thing.php'));
    }

    public function testStripPath()
    {
        $this->config->setStripPath('/foo/bar');

        $this->assertSame('src/thing.php', $this->config->getStrippedFilePath('/foo/bar/src/thing.php'));
        $this->assertSame('/foo/src/thing.php', $this->config->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('x/src/thing.php', $this->config->getStrippedFilePath('x/src/thing.php'));
    }

    public function testStripPathNull()
    {
        $this->config->setStripPath('/foo/bar');
        $this->config->setStripPath(null);

        $this->assertSame('/foo/bar/src/thing.php', $this->config->getStrippedFilePath('/foo/bar/src/thing.php'));
        $this->assertSame('/foo/src/thing.php', $this->config->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('x/src/thing.php', $this->config->getStrippedFilePath('x/src/thing.php'));
    }

    public function testStripPathRegex()
    {
        $this->config->setStripPathRegex('/^\\/(foo|bar)\\//');

        $this->assertSame('src/thing.php', $this->config->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('src/thing.php', $this->config->getStrippedFilePath('/bar/src/thing.php'));
        $this->assertSame('/baz/src/thing.php', $this->config->getStrippedFilePath('/baz/src/thing.php'));
        $this->assertSame('x/foo/thing.php', $this->config->getStrippedFilePath('x/foo/thing.php'));
    }

    public function testStripPathRegexNull()
    {
        $this->config->setStripPathRegex('/^\\/(foo|bar)\\//');
        $this->config->setStripPathRegex(null);

        $this->assertSame('/foo/src/thing.php', $this->config->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('/bar/src/thing.php', $this->config->getStrippedFilePath('/bar/src/thing.php'));
        $this->assertSame('/baz/src/thing.php', $this->config->getStrippedFilePath('/baz/src/thing.php'));
        $this->assertSame('x/foo/thing.php', $this->config->getStrippedFilePath('x/foo/thing.php'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExpcetionMessage Invalid project root regex: [thisisnotavalidregex
     */
    public function testInvalidRootPathRegexThrows()
    {
        $this->config->setProjectRootRegex('[thisisnotavalidregex');
    }

    public function testAppData()
    {
        $this->assertSame(['type' => 'cli', 'releaseStage' => 'production'], $this->config->getAppData());

        $this->config->setReleaseStage('qa1');
        $this->config->setAppVersion('1.2.3');
        $this->config->setAppType('laravel');

        $this->assertSame(['type' => 'laravel', 'releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setAppType(null);

        $this->assertSame(['type' => 'cli', 'releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setFallbackType('foo');

        $this->assertSame(['type' => 'foo', 'releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setReleaseStage(null);

        $this->assertSame(['type' => 'foo', 'releaseStage' => 'production', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setAppVersion(null);

        $this->assertSame(['type' => 'foo', 'releaseStage' => 'production'], $this->config->getAppData());

        $this->config->setFallbackType(null);

        $this->assertSame(['releaseStage' => 'production'], $this->config->getAppData());
    }

    public function testDeviceData()
    {
        $this->assertSame(['hostname' => php_uname('n')], $this->config->getDeviceData());

        $this->config->setHostname('web1.example.com');

        $this->assertSame(['hostname' => 'web1.example.com'], $this->config->getDeviceData());
    }

    public function testSessionTrackingDefaults()
    {
        $this->assertTrue($this->config->shouldCaptureSessions());
        $this->assertTrue($this->config->sessionsEnabled());
    }

    public function testSessionTrackingSetFalse()
    {
        $this->assertTrue($this->config->shouldCaptureSessions());

        $this->config->setAutoCaptureSessions(false);

        $this->assertFalse($this->config->shouldCaptureSessions());
    }

    public function testSessionTrackingSetEndpoint()
    {
        $testUrl = 'https://testurl.com';
        $this->config->setSessionEndpoint($testUrl);

        $this->assertSame($testUrl, $this->config->getSessionEndpoint());
    }

    public function testEndpointDefaults()
    {
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_NOTIFY_ENDPOINT, $this->config->getNotifyEndpoint());
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_SESSION_ENDPOINT, $this->config->getSessionEndpoint());
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_BUILD_ENDPOINT, $this->config->getBuildEndpoint());
    }

    public function testSetEndpointsBothValid()
    {
        $notifyUrl = 'notify';
        $sessionUrl = 'session';

        $this->config->setEndpoints($notifyUrl, $sessionUrl);

        $this->assertSame($notifyUrl, $this->config->getNotifyEndpoint());
        $this->assertSame($sessionUrl, $this->config->getSessionEndpoint());
    }

    public function testSetEndpointsNoChange()
    {
        // If this throws or logs, something has gone wrong
        $this->config->setEndpoints(null, null);

        $this->assertSame(\Bugsnag\Configuration::DEFAULT_NOTIFY_ENDPOINT, $this->config->getNotifyEndpoint());
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_SESSION_ENDPOINT, $this->config->getSessionEndpoint());
    }

    public function testSetEndpointsNotifyNotSession()
    {
        $notifyUrl = 'notify';

        $env = $this->getFunctionMock('Bugsnag', 'syslog');
        $env->expects($this->once())->with(LOG_WARNING, 'The session endpoint has not been set, all further session capturing will be disabled');

        $this->config->setEndpoints($notifyUrl, null);

        $this->assertSame($notifyUrl, $this->config->getNotifyEndpoint());
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_SESSION_ENDPOINT, $this->config->getSessionEndpoint());
        $this->assertFalse($this->config->sessionsEnabled());
        $this->assertFalse($this->config->shouldCaptureSessions());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The session endpoint cannot be modified without the notify endpoint
     */
    public function testSetEndpointsSessionNotNotify()
    {
        $sessionUrl = 'session';

        $this->config->setEndpoints(null, $sessionUrl);
        $this->config->sessionsEnabled();
    }

    public function testSetGuzzleClient()
    {
        $guzzleClient = new GuzzleClient();

        $this->config->setGuzzleClient($guzzleClient);
        $this->assertSame($guzzleClient, $this->config->getGuzzleClient());
    }

    public function testGetGuzzleClientCreation()
    {
        $guzzle = $this->config->getGuzzleClient();
        $this->assertInstanceOf(GuzzleClient::class, $guzzle);
    }

}
