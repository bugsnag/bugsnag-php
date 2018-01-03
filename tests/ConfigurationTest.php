<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use PHPUnit_Framework_TestCase as TestCase;

class ConfigurationTest extends TestCase
{
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
        $this->assertFalse($this->config->shouldTrackSessions());

        $this->assertSame(null, $this->config->getSessionClient());
    }

    public function testSessionTrackingSetTrue()
    {
        $this->assertFalse($this->config->shouldTrackSessions());

        $this->config->setSessionTracking(true);

        $this->assertTrue($this->config->shouldTrackSessions());

        $client = $this->config->getSessionClient();

        $this->assertSame(GuzzleClient::class, get_class($client));

        if (ClientInterface::VERSION[0] == '5') {
            $clientUri = $client->getBaseUrl();
        } else {
            $baseUri = $client->getConfig('base_uri');
            $clientUri =  $baseUri->getScheme() . '://' . $baseUri->getHost();
        }

        $this->assertSame(Configuration::SESSION_ENDPOINT, $clientUri);
    }

    public function testSessionTrackingSetWithEndpoint()
    {
        $testUrl = 'https://testurl.com';

        $this->assertFalse($this->config->shouldTrackSessions());

        $this->config->setSessionTracking(true, $testUrl);

        $this->assertTrue($this->config->shouldTrackSessions());

        $client = $this->config->getSessionClient();

        $this->assertSame(GuzzleClient::class, get_class($client));

        if (ClientInterface::VERSION[0] == '5') {
            $clientUri = $client->getBaseUrl();
        } else {
            $baseUri = $client->getConfig('base_uri');
            $clientUri =  $baseUri->getScheme() . '://' . $baseUri->getHost();
        }
        
        $this->assertSame($testUrl, $clientUri);
    }
}
