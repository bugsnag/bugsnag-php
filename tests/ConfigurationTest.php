<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\FeatureFlag;

class ConfigurationTest extends TestCase
{
    protected $config;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testDoesNotAcceptBadApiKey()
    {
        $this->expectedException(\InvalidArgumentException::class);

        new Configuration([]);
    }

    public function testDoesNotAcceptBadStripPathRegex()
    {
        $this->expectedException(
            \InvalidArgumentException::class,
            'Invalid strip path regex: [thisisnotavalidregex'
        );

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

    public function testInvalidRootPathRegexThrows()
    {
        $this->expectedException(
            \InvalidArgumentException::class,
            'Invalid project root regex: [thisisnotavalidregex'
        );

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
        $this->assertEquals(2, count($this->config->getDeviceData()));
        $this->assertSame(php_uname('n'), $this->config->getDeviceData()['hostname']);
        $this->assertSame(phpversion(), $this->config->getDeviceData()['runtimeVersions']['php']);

        $this->config->setHostname('web1.example.com');

        $this->assertEquals(2, count($this->config->getDeviceData()));
        $this->assertSame('web1.example.com', $this->config->getDeviceData()['hostname']);
        $this->assertSame(phpversion(), $this->config->getDeviceData()['runtimeVersions']['php']);
    }

    public function testMergeDeviceDataEmptyArray()
    {
        $newData = [];

        $this->config->mergeDeviceData($newData);

        $this->assertEquals(2, count($this->config->getDeviceData()));
        $this->assertSame(php_uname('n'), $this->config->getDeviceData()['hostname']);
        $this->assertSame(phpversion(), $this->config->getDeviceData()['runtimeVersions']['php']);
    }

    public function testMergeDeviceDataSingleValue()
    {
        $newData = ['field1' => 'value'];

        $this->config->mergeDeviceData($newData);

        $this->assertEquals(3, count($this->config->getDeviceData()));
        $this->assertSame(php_uname('n'), $this->config->getDeviceData()['hostname']);
        $this->assertSame(phpversion(), $this->config->getDeviceData()['runtimeVersions']['php']);
        $this->assertSame('value', $this->config->getDeviceData()['field1']);
    }

    public function testMergeDeviceDataMultiValues()
    {
        $newData = ['field1' => 'value', 'field2' => 2];

        $this->config->mergeDeviceData($newData);

        $this->assertEquals(4, count($this->config->getDeviceData()));
        $this->assertSame(php_uname('n'), $this->config->getDeviceData()['hostname']);
        $this->assertSame(phpversion(), $this->config->getDeviceData()['runtimeVersions']['php']);
        $this->assertSame('value', $this->config->getDeviceData()['field1']);
        $this->assertSame(2, $this->config->getDeviceData()['field2']);
    }

    public function testMergeDeviceDataComplexValues()
    {
        $newData = ['array_field' => [0, 1, 2], 'assoc_array_field' => ['f1' => 1]];

        $this->config->mergeDeviceData($newData);

        $this->assertEquals(4, count($this->config->getDeviceData()));
        $this->assertSame(php_uname('n'), $this->config->getDeviceData()['hostname']);
        $this->assertSame(phpversion(), $this->config->getDeviceData()['runtimeVersions']['php']);
        $this->assertSame([0, 1, 2], $this->config->getDeviceData()['array_field']);
        $this->assertSame(['f1' => 1], $this->config->getDeviceData()['assoc_array_field']);
    }

    public function testSessionTrackingIsDisabledByDefault()
    {
        $this->assertFalse($this->config->shouldCaptureSessions());
    }

    public function testSessionTrackingCanBeEnabled()
    {
        $this->config->setAutoCaptureSessions(true);

        $this->assertTrue($this->config->shouldCaptureSessions());
    }

    public function testTheSessionEndpointHasASensibleDefault()
    {
        $expected = 'https://sessions.bugsnag.com';

        $this->assertSame($expected, $this->config->getSessionEndpoint());
    }

    public function testTheSessionEndpointCanBeSetIfNecessary()
    {
        $expected = 'https://example.com';

        $this->config->setSessionEndpoint($expected);

        $this->assertSame($expected, $this->config->getSessionEndpoint());
    }

    public function testMemoryLimitIncreaseDefault()
    {
        $this->assertSame(1024 * 1024 * 5, $this->config->getMemoryLimitIncrease());
    }

    public function testMemoryLimitIncreaseCanBeSet()
    {
        $this->config->setMemoryLimitIncrease(12345);

        $this->assertSame(12345, $this->config->getMemoryLimitIncrease());
    }

    public function testMemoryLimitIncreaseCanBeSetToNull()
    {
        $this->config->setMemoryLimitIncrease(null);

        $this->assertNull($this->config->getMemoryLimitIncrease());
    }

    public function testDiscardClassesDefault()
    {
        $this->assertSame([], $this->config->getDiscardClasses());
    }

    public function testDiscardClassesCanBeSet()
    {
        $discardClasses = [
            \RuntimeException::class,
            \LogicException::class,
            \TypeError::class,
            '/^(Under|Over)flowException$/',
        ];

        $this->config->setDiscardClasses($discardClasses);

        $this->assertSame($discardClasses, $this->config->getDiscardClasses());
    }

    public function testRedactedKeysDefault()
    {
        $this->assertSame([], $this->config->getRedactedKeys());
    }

    public function testRedactedKeysCanBeSet()
    {
        $redactedKeys = ['password', 'password_confirmation'];

        $this->config->setRedactedKeys($redactedKeys);

        $this->assertSame($redactedKeys, $this->config->getRedactedKeys());
    }

    public function testFeatureFlagsCanBeAddedToConfiguration()
    {
        $this->config->addFeatureFlag('a name');
        $this->config->addFeatureFlag('another name', 'with variant');

        $expected = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name', 'with variant'),
        ];

        $actual = $this->config->getFeatureFlagsCopy()->toArray();

        $this->assertEquals($expected, $actual);
    }

    public function testMultipleFeatureFlagsCanBeAddedToConfigurationAtOnce()
    {
        $this->config->addFeatureFlag('a name');
        $this->config->addFeatureFlags([
            new FeatureFlag('another name', 'with variant'),
            new FeatureFlag('name3'),
            new FeatureFlag('four', 'yes'),
        ]);

        $expected = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name', 'with variant'),
            new FeatureFlag('name3'),
            new FeatureFlag('four', 'yes'),
        ];

        $actual = $this->config->getFeatureFlagsCopy()->toArray();

        $this->assertEquals($expected, $actual);
    }

    public function testAFeatureFlagCanBeRemovedFromConfiguration()
    {
        $this->config->addFeatureFlag('a name');
        $this->config->addFeatureFlag('another name', 'with variant');

        $this->config->clearFeatureFlag('another name');

        $expected = [
            new FeatureFlag('a name'),
        ];

        $actual = $this->config->getFeatureFlagsCopy()->toArray();

        $this->assertEquals($expected, $actual);
    }

    public function testAllFeatureFlagsCanBeRemovedFromConfiguration()
    {
        $this->config->addFeatureFlag('a name');
        $this->config->addFeatureFlag('another name', 'with variant');

        $this->config->clearFeatureFlags();

        $actual = $this->config->getFeatureFlagsCopy()->toArray();

        $this->assertSame([], $actual);
    }
}
