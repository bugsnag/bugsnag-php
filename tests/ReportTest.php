<?php

namespace Bugsnag\Tests;

use BadMethodCallException;
use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\Configuration;
use Bugsnag\FeatureFlag;
use Bugsnag\Report;
use Bugsnag\Stacktrace;
use Bugsnag\Tests\Fakes\SomeException;
use Bugsnag\Tests\Fakes\StringableObject;
use Exception;
use InvalidArgumentException;
use LogicException;
use ParseError;
use RuntimeException;
use stdClass;

class ReportTest extends TestCase
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var Report
     */
    protected $report;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->config = new Configuration('example-key');
        $this->report = Report::fromNamedError($this->config, 'Name', 'Message');
    }

    public function testDeviceData()
    {
        $data = $this->report->toArray();

        $this->assertCount(3, $data['device']);
        Assert::matchesBugsnagDateFormat($data['device']['time']);
        $this->assertSame(php_uname('n'), $data['device']['hostname']);
        $this->assertSame(phpversion(), $data['device']['runtimeVersions']['php']);
    }

    public function testMetaData()
    {
        $this->report->setMetaData(['Testing' => ['globalArray' => 'hi']]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi']], $this->report->toArray()['metaData']);
    }

    public function testMetaDataMerging()
    {
        $this->report->setMetaData(['Testing' => ['globalArray' => 'hi']]);
        $this->report->setMetaData(['Testing' => ['localArray' => 'yo']]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi', 'localArray' => 'yo']], $this->report->toArray()['metaData']);
    }

    public function testMetaDataObj()
    {
        $this->report->setMetaData(['Testing' => (object) ['globalArray' => 'hi']]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi']], $this->report->toArray()['metaData']);
    }

    public function testAddMetaDataCreate()
    {
        $this->report->addMetaData(['Testing' => ['globalArray' => 'hi']]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi']], $this->report->toArray()['metaData']);
    }

    public function testAddMetaDataDeletesIfNull()
    {
        $this->report->setMetaData(['Testing' => ['globalArray' => 'hi', 'Delete' => 'test'], 'Delete' => 'test']);

        $this->assertSame(['Testing' => ['globalArray' => 'hi', 'Delete' => 'test'], 'Delete' => 'test'], $this->report->toArray()['metaData']);

        $this->report->addMetaData(['Testing' => ['Delete' => null], 'Delete' => null]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi']], $this->report->toArray()['metaData']);
    }

    public function testAddMetaDataMerge()
    {
        $this->report->setMetaData(['Testing' => ['array' => 'hi'], 'Replace' => 'Scalar']);

        $this->assertSame(['Testing' => ['array' => 'hi'], 'Replace' => 'Scalar'], $this->report->toArray()['metaData']);

        $this->report->addMetaData(['Testing' => ['second' => 'array'], 'Replace' => ['array' => 'replacement']]);

        $this->assertSame(['Testing' => ['array' => 'hi', 'second' => 'array'], 'Replace' => ['array' => 'replacement']], $this->report->toArray()['metaData']);
    }

    public function testSingularMetaDataOverwritten()
    {
        $this->report->setMetaData(['Testing' => ['keep' => 'hi', 'replace' => 'bye']]);

        $this->assertSame(['Testing' => ['keep' => 'hi', 'replace' => 'bye']], $this->report->toArray()['metaData']);

        $this->report->addMetaData(['Testing' => ['replace' => false]]);

        $this->assertSame(['Testing' => ['keep' => 'hi', 'replace' => false]], $this->report->toArray()['metaData']);
    }

    public function testUser()
    {
        $this->report->setUser(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->report->toArray()['user']);
    }

    public function testUserDefaultFilters()
    {
        $this->report->setUser(['foo' => 'bar', 'password' => 'mypass', 'custom_field' => 'some data']);

        $this->assertSame(['foo' => 'bar', 'password' => '[FILTERED]', 'custom_field' => 'some data'], $this->report->toArray()['user']);
    }

    public function testUserCustomFilters()
    {
        $this->config->setFilters(['custom_field']);

        $this->report->setUser(['foo' => 'bar', 'password' => 'mypass', 'custom_field' => 'some data']);

        $this->assertSame(['foo' => 'bar', 'password' => 'mypass', 'custom_field' => '[FILTERED]'], $this->report->toArray()['user']);
    }

    public function testDefaultFilters()
    {
        $metadata = array_reduce(
            $this->config->getFilters(),
            function ($metadata, $filter) {
                $metadata[$filter] = "abc {$filter} xyz";

                return $metadata;
            },
            []
        );

        $this->report->setMetaData(['Testing' => $metadata]);

        $this->assertSame(
            [
                'password' => '[FILTERED]',
                'cookie' => '[FILTERED]',
                'authorization' => '[FILTERED]',
                'php-auth-user' => '[FILTERED]',
                'php-auth-pw' => '[FILTERED]',
                'php-auth-digest' => '[FILTERED]',
            ],
            $this->report->toArray()['metaData']['Testing']
        );
    }

    public function testExceptionsNotFiltered()
    {
        $this->config->setFilters(['code']);
        $this->report->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        // 'Code' should not be filtered so should remain still be an array
        Assert::isType('array', $event['exceptions'][0]['stacktrace'][0]['code']);
    }

    public function testFiltersAreCaseInsensitive()
    {
        $this->report->setMetaData([
            'Testing' => [
                'PASSWORD' => 'a',
                'Password' => 'b',
                'passworD' => 'c',
                'PaSsWoRd' => 'd',
                'password2' => 'e',
                '2PASSWORD2POROUS' => 'f',
            ],
        ]);

        $this->assertSame(
            [
                'PASSWORD' => '[FILTERED]',
                'Password' => '[FILTERED]',
                'passworD' => '[FILTERED]',
                'PaSsWoRd' => '[FILTERED]',
                'password2' => '[FILTERED]',
                '2PASSWORD2POROUS' => '[FILTERED]',
            ],
            $this->report->toArray()['metaData']['Testing']
        );
    }

    /**
     * @dataProvider redactedKeysProvider
     *
     * @param array $metadata
     * @param string[] $redactedKeys
     * @param array $expected
     *
     * @return void
     */
    public function testRedactedKeys(
        array $metadata,
        array $redactedKeys,
        array $expected
    ) {
        $this->config->setRedactedKeys($redactedKeys);
        $this->report->setMetaData(['Testing' => $metadata]);

        $actual = $this->report->toArray()['metaData']['Testing'];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider redactedKeysProvider
     *
     * @param array $metadata
     * @param string[] $redactedKeys
     * @param array $expected
     *
     * @return void
     */
    public function testRedactedKeysWithBreadcrumbMetadata(
        array $metadata,
        array $redactedKeys,
        array $expected
    ) {
        $this->config->setRedactedKeys($redactedKeys);

        $breadcrumb = new Breadcrumb('abc', Breadcrumb::LOG_TYPE, ['Testing' => $metadata]);
        $this->report->addBreadcrumb($breadcrumb);

        $actual = $this->report->toArray()['breadcrumbs'][0]['metaData']['Testing'];

        $this->assertEquals($expected, $actual);
    }

    public function redactedKeysProvider()
    {
        yield [
            ['abc' => 'xyz', 'a' => 1, 'b' => 2, 'c' => 3],
            ['a', 'c'],
            ['abc' => 'xyz', 'a' => '[FILTERED]', 'b' => 2, 'c' => '[FILTERED]'],
        ];

        yield [
            ['abc' => 'xyz', 'a' => 1, 'b' => 2, 'C' => 3],
            ['A', 'c'],
            ['abc' => 'xyz', 'a' => '[FILTERED]', 'b' => 2, 'C' => '[FILTERED]'],
        ];

        yield [
            ['â' => 1, 'b' => 2, 'ñ' => 3, 'n' => 4],
            ['â', 'ñ'],
            ['â' => '[FILTERED]', 'b' => 2, 'ñ' => '[FILTERED]', 'n' => 4],
        ];

        yield [
            ['â' => 1, 'b' => 2, 'Ñ' => 3],
            ['Â', 'ñ'],
            ['â' => '[FILTERED]', 'b' => 2, 'Ñ' => '[FILTERED]'],
        ];

        // 6e cc 83 is equivalent to "\u{006E}\u{0303}" but in a way PHP 5 can
        // understand. This is the character "ñ" built out of "n" and a
        // combining tilde
        yield [
            ["\x6e\xcc\x83" => 1, 'b' => 2, 'c' => 3, 'n' => 4],
            ["\x6e\xcc\x83", 'c'],
            ["\x6e\xcc\x83" => '[FILTERED]', 'b' => 2, 'c' => '[FILTERED]', 'n' => 4],
        ];

        // 4e cc 83 is equivalent to "\u{004E}\u{0303}", which is the capital
        // version of the above ("N" + a combining tilde)
        yield [
            ["\x6e\xcc\x83" => 1, 'b' => 2, 'c' => 3, 'n' => 4],
            ["\x4e\xcc\x83", 'c'],
            ["\x6e\xcc\x83" => '[FILTERED]', 'b' => 2, 'c' => '[FILTERED]', 'n' => 4],
        ];

        // This is "ñ" both as a single character and with the combining tilde
        yield [
            ["\x6e\xcc\x83" => 1, 'b' => 2, 'c' => 3, 'n' => 4],
            ["\xc3\xb1", 'c'],
            ["\x6e\xcc\x83" => '[FILTERED]', 'b' => 2, 'c' => '[FILTERED]', 'n' => 4],
        ];

        // This is "Ñ" as a single character and "ñ" with the combining tilde
        yield [
            ["\x6e\xcc\x83" => 1, 'b' => 2, 'c' => 3, 'n' => 4],
            ["\xc3\x91", 'c'],
            ["\x6e\xcc\x83" => '[FILTERED]', 'b' => 2, 'c' => '[FILTERED]', 'n' => 4],
        ];

        // This is "Ñ" as a single character and "ñ" with the combining tilde
        yield [
            ["\xc3\x91" => 1, 'b' => 2, 'c' => 3, 'n' => 4],
            ["\x6e\xcc\x83", 'c'],
            ["\xc3\x91" => '[FILTERED]', 'b' => 2, 'c' => '[FILTERED]', 'n' => 4],
        ];

        yield [
            ['abc' => 1, 'xyz' => 2],
            ['/^.b.$/'],
            ['abc' => '[FILTERED]', 'xyz' => 2],
        ];

        yield [
            ['abc' => 1, 'xyz' => 2, 'oOo' => 3],
            ['/^[a-z]{3}$/'],
            ['abc' => '[FILTERED]', 'xyz' => '[FILTERED]', 'oOo' => 3],
        ];

        yield [
            ['abc' => 1, 'xyz' => 2, 'oOo' => 3, 'oOoOo' => 4],
            ['/^[A-z]{3}$/'],
            ['abc' => '[FILTERED]', 'xyz' => '[FILTERED]', 'oOo' => '[FILTERED]', 'oOoOo' => 4],
        ];

        yield [
            ['abc' => 1, 'xyz' => 2, 'yyy' => 3],
            ['/(c|y)$/'],
            ['abc' => '[FILTERED]', 'xyz' => 2, 'yyy' => '[FILTERED]'],
        ];

        yield [
            ['abc' => 1, 'xyz' => 2, 'yyy' => 3],
            ['/c$/', '/y$/'],
            ['abc' => '[FILTERED]', 'xyz' => 2, 'yyy' => '[FILTERED]'],
        ];

        // This doesn't match the regex but does match as a string comparison
        yield [
            ['/^abc$/' => 1, 'xyz' => 2, 'oOo' => 3],
            ['/^abc$/'],
            ['/^abc$/' => '[FILTERED]', 'xyz' => 2, 'oOo' => 3],
        ];

        yield [
            ['/abc/' => 1, 'xyz' => 2, 'oOo' => 3],
            ['/abc/'],
            ['/abc/' => '[FILTERED]', 'xyz' => 2, 'oOo' => 3],
        ];
    }

    public function testCanGetStacktrace()
    {
        $beginningOfTest = __LINE__;

        // Generate a small backtrace to test with
        $this->generateBacktrace(4);

        $trace = $this->report->getStacktrace();
        $this->assertInstanceOf(Stacktrace::class, $trace);

        $trace = $trace->toArray();
        $this->assertGreaterThan(4, count($trace));

        // Strip out frames that weren't generated in this file, so that changes
        // in how PHPUnit executes tests don't cause this test to fail (some
        // versions of PHPUnit will generate more/less frames than others)
        $trace = array_filter($trace, function ($frame) {
            return $frame['file'] === __FILE__;
        });

        $this->assertNotEmpty($trace);

        $lineNumber = __LINE__;

        $generatedFrame = [
            'lineNumber' => $lineNumber + 61,
            'method' => __CLASS__.'::generateBacktrace',
            'code' => [
                $lineNumber + 58 => '    private function generateBacktrace($depth)',
                $lineNumber + 59 => '    {',
                $lineNumber + 60 => '        if ($depth > 0) {',
                $lineNumber + 61 => '            return $this->generateBacktrace($depth - 1);',
                $lineNumber + 62 => '        }',
                $lineNumber + 63 => '',
                $lineNumber + 64 => '        $this->report->setPHPError(E_NOTICE, \'Broken\', \'file\', 123);',
            ],
            'inProject' => false,
            'file' => __FILE__,
        ];

        $expected = [
            [
                'lineNumber' => $lineNumber + 64,
                'method' => __CLASS__.'::generateBacktrace',
                'code' => [
                    $lineNumber + 61 => '            return $this->generateBacktrace($depth - 1);',
                    $lineNumber + 62 => '        }',
                    $lineNumber + 63 => '',
                    $lineNumber + 64 => '        $this->report->setPHPError(E_NOTICE, \'Broken\', \'file\', 123);',
                    $lineNumber + 65 => '    }',
                    $lineNumber + 66 => '',
                    $lineNumber + 67 => '    public function testNoticeName()',
                ],
                'inProject' => false,
                'file' => __FILE__,
            ],
            $generatedFrame,
            $generatedFrame,
            $generatedFrame,
            $generatedFrame,
            [
                'lineNumber' => $beginningOfTest + 3,
                'method' => __METHOD__,
                'code' => [
                    $beginningOfTest + 0 => '        $beginningOfTest = __LINE__;',
                    $beginningOfTest + 1 => '',
                    $beginningOfTest + 2 => '        // Generate a small backtrace to test with',
                    $beginningOfTest + 3 => '        $this->generateBacktrace(4);',
                    $beginningOfTest + 4 => '',
                    $beginningOfTest + 5 => '        $trace = $this->report->getStacktrace();',
                    $beginningOfTest + 6 => '        $this->assertInstanceOf(Stacktrace::class, $trace);',
                ],
                'inProject' => false,
                'file' => __FILE__,
            ],
        ];

        $this->assertSame($expected, $trace);
    }

    private function generateBacktrace($depth)
    {
        if ($depth > 0) {
            return $this->generateBacktrace($depth - 1);
        }

        $this->report->setPHPError(E_NOTICE, 'Broken', 'file', 123);
    }

    public function testNoticeName()
    {
        $this->report->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        $this->assertSame($event['exceptions'][0]['errorClass'], 'PHP Notice');
    }

    public function testErrorName()
    {
        $this->report->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        $this->assertSame($event['exceptions'][0]['errorClass'], 'PHP Fatal Error');
    }

    public function testErrorPayloadVersion()
    {
        $this->report->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        $this->assertSame('4.0', $event['payloadVersion']);
    }

    public function testNoticeSeverity()
    {
        $this->report->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        $this->assertSame($event['severity'], 'info');
        $this->assertCount(1, $event['exceptions']);
    }

    public function testErrorSeverity()
    {
        $this->report->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        $this->assertSame($event['severity'], 'error');
        $this->assertCount(1, $event['exceptions']);
    }

    public function testRecoverableErrorSeverity()
    {
        $this->report->setPHPError(E_RECOVERABLE_ERROR, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        $this->assertSame($event['severity'], 'error');
        $this->assertCount(1, $event['exceptions']);
    }

    public function testFatalErrorSeverity()
    {
        $this->report->setPHPError(E_ERROR, 'Broken', 'file', 123, true);

        $event = $this->report->toArray();
        $this->assertSame($event['severity'], 'error');
        $this->assertCount(1, $event['exceptions']);
        $this->assertCount(1, $event['exceptions'][0]['stacktrace']);
    }

    public function testManualSeverity()
    {
        $this->report->setSeverity('error');

        $event = $this->report->toArray();
        $this->assertSame($event['severity'], 'error');
    }

    public function testInvalidSeverity()
    {
        $this->expectedException(InvalidArgumentException::class);
        $this->report->setSeverity('bunk');
    }

    public function testFromThrowable()
    {
        $exception = new Exception('foo');

        $report = Report::fromPHPThrowable($this->config, $exception);

        $event = $report->toArray();

        $this->assertCount(1, $event['exceptions']);
        $this->assertSame($event['exceptions'][0]['message'], 'foo');
        $this->assertSame('warning', $report->getSeverity());
        $this->assertSame($exception, $report->getOriginalError());
    }

    public function testPreviousExceptions()
    {
        $initialCause = new Exception('actual problem');
        $intermediateCause = new Exception('middle of the chain', 65533, $initialCause);
        $exception = new Exception('caught', 65533, $intermediateCause);

        $report = Report::fromPHPThrowable($this->config, $exception);

        $event = $report->toArray();

        $this->assertCount(3, $event['exceptions']);
        $this->assertSame($event['exceptions'][0]['message'], 'caught');
        $this->assertSame($event['exceptions'][1]['message'], 'middle of the chain');
        $this->assertSame($event['exceptions'][2]['message'], 'actual problem');
    }

    public function testErrorGroupingHash()
    {
        $this->report->setGroupingHash('herp#derp');

        $event = $this->report->toArray();
        $this->assertSame($event['groupingHash'], 'herp#derp');
    }

    public function testErrorGroupingHashNotSet()
    {
        $event = $this->report->toArray();
        $this->assertArrayNotHasKey('groupingHash', $event);
    }

    public function testSetPHPThrowable()
    {
        $this->assertSame($this->report, $this->report->setPHPThrowable(new Exception()));
    }

    public function testSetPHPAnotherThrowable()
    {
        $exception = class_exists(ParseReport::class) ? new ParseError() : new InvalidArgumentException();

        $this->assertSame($this->report, $this->report->setPHPThrowable($exception));
    }

    public function testSetNotThrowable()
    {
        $this->expectedException(InvalidArgumentException::class);
        $this->assertSame($this->report, $this->report->setPHPThrowable(new stdClass()));
    }

    public function testSetNotObject()
    {
        $this->expectedException(InvalidArgumentException::class);
        $this->assertSame($this->report, $this->report->setPHPThrowable('foo'));
    }

    public function testSetNameThrowsWhenPassedAnArray()
    {
        $this->expectedException(InvalidArgumentException::class);
        $this->report->setName([]);
    }

    public function testSetNameThrowsWhenPassedANonStringableObject()
    {
        $this->expectedException(InvalidArgumentException::class);
        $this->report->setName(new stdClass());
    }

    public function testSetMessageThrowsWhenPassedAnArray()
    {
        $this->expectedException(InvalidArgumentException::class);
        $this->report->setMessage([]);
    }

    public function testSetMessageThrowsWhenPassedANonStringableObject()
    {
        $this->expectedException(InvalidArgumentException::class);
        $this->report->setMessage(new stdClass());
    }

    public function testSetNameAcceptsIntegers()
    {
        $this->report->setName(123);

        $this->assertSame('123', $this->report->getName());
    }

    public function testSetNameAcceptsStrings()
    {
        $this->report->setName('hey');

        $this->assertSame('hey', $this->report->getName());
    }

    public function testSetNameAcceptsStringableObjects()
    {
        $this->report->setName(new StringableObject());

        $this->assertSame('2object2string', $this->report->getName());
    }

    public function testSetMessageAcceptsStrings()
    {
        $this->report->setMessage('foo bar baz');

        $this->assertSame('foo bar baz', $this->report->getMessage());
    }

    public function testSetMessageAcceptsEmptyStrings()
    {
        $this->report->setMessage('');

        $this->assertSame('', $this->report->getMessage());
    }

    public function testSetMessageAcceptsNull()
    {
        $this->report->setMessage(null);

        $this->assertNull($this->report->getMessage());
    }

    public function testSetMessageAcceptsStringableObjects()
    {
        $this->report->setMessage(new StringableObject());

        $this->assertSame('2object2string', $this->report->getMessage());
    }

    public function testGetSummaryFull()
    {
        $this->report->setName('foo');
        $this->report->setMessage('bar');
        $this->report->setSeverity('info');

        $this->assertSame(['name' => 'foo', 'message' => 'bar', 'severity' => 'info'], $this->report->getSummary());
    }

    public function testGetSummaryPartial()
    {
        $this->report->setName('foo');
        $this->report->setMessage(null);

        $this->assertSame(['name' => 'foo', 'severity' => 'warning'], $this->report->getSummary());
    }

    public function testGetSummaryEmpty()
    {
        $this->report->setName('foo');
        $this->report->setMessage('');

        $this->assertSame(['name' => 'foo', 'severity' => 'warning'], $this->report->getSummary());
    }

    public function testGetSummaryDuplicate()
    {
        $this->report->setName('bar');
        $this->report->setMessage('bar');

        $this->assertSame(['message' => 'bar', 'severity' => 'warning'], $this->report->getSummary());
    }

    public function testFromPhpError()
    {
        $report = Report::fromPHPError($this->config, E_WARNING, 'FOO', __FILE__, 5, false);

        $error = [
            'code' => E_WARNING,
            'message' => 'FOO',
            'file' => __FILE__,
            'line' => 5,
            'fatal' => false,
        ];

        $this->assertSame('warning', $report->getSeverity());
        $this->assertSame($error, $report->getOriginalError());
    }

    public function testFromNamedError()
    {
        $report = Report::fromNamedError($this->config, 'CRASH', 'Something went wrong!');

        $this->assertSame('warning', $report->getSeverity());
        $this->assertNull($report->getOriginalError());
    }

    /**
     * Testing handled/unhandled.
     */
    public function testCorrectDefaultReasons()
    {
        $exception = new Exception('exception');
        $throwableData = Report::fromPHPThrowable($this->config, $exception)->toArray();
        $namedErrorData = Report::fromNamedError($this->config, 'E_ERROR', null)->toArray();
        $phpErrorData = Report::fromPHPError($this->config, E_WARNING, null, 'file', 1, false)->toArray();
        $this->assertFalse($throwableData['unhandled']);
        $this->assertSame($throwableData['severity'], 'warning');
        $this->assertSame($throwableData['severityReason'], [
            'type' => 'handledException',
        ]);
        $this->assertFalse($namedErrorData['unhandled']);
        $this->assertSame($namedErrorData['severity'], 'warning');
        $this->assertSame($namedErrorData['severityReason'], [
            'type' => 'handledError',
        ]);
        $this->assertFalse($phpErrorData['unhandled']);
        $this->assertSame($phpErrorData['severity'], 'warning');
        $this->assertSame($phpErrorData['severityReason'], [
            'type' => 'handledError',
        ]);
    }

    public function testSettingSeverityReason()
    {
        $exception = new Exception('exception');
        $report = Report::fromPHPThrowable($this->config, $exception);
        $report->setUnhandled(true);
        $report->setSeverityReason(['type' => 'unhandledException']);
        $data = $report->toArray();
        $this->assertTrue($data['unhandled']);
        $this->assertSame($data['severityReason'], ['type' => 'unhandledException']);
    }

    public function testDefaultSeverityTypeSet()
    {
        $exception = new Exception('exception');
        $report = Report::fromPHPThrowable($this->config, $exception);
        $report->setSeverityReason(['foo' => 'bar']);
        $data = $report->toArray();
        $this->assertSame($data['severityReason'], ['foo' => 'bar', 'type' => 'userSpecifiedSeverity']);
    }

    public function testGetErrorsWithNoPreviousErrors()
    {
        $exception = new Exception('abc xyz');

        $report = Report::fromPHPThrowable($this->config, $exception);
        $actual = $report->getErrors();

        $expected = [
            ['errorClass' => 'Exception', 'errorMessage' => 'abc xyz', 'type' => 'php'],
        ];

        $this->assertSame($expected, $actual);
    }

    public function testGetErrorsWithPreviousErrors()
    {
        $exception5 = new SomeException('exception5');
        $exception4 = new BadMethodCallException('exception4', 0, $exception5);
        $exception3 = new LogicException('exception3', 0, $exception4);
        $exception2 = new RuntimeException('exception2', 0, $exception3);
        $exception1 = new Exception('exception1', 0, $exception2);

        $report = Report::fromPHPThrowable($this->config, $exception1);
        $actual = $report->getErrors();

        $expected = [
            ['errorClass' => 'Exception', 'errorMessage' => 'exception1', 'type' => 'php'],
            ['errorClass' => 'RuntimeException', 'errorMessage' => 'exception2', 'type' => 'php'],
            ['errorClass' => 'LogicException', 'errorMessage' => 'exception3', 'type' => 'php'],
            ['errorClass' => 'BadMethodCallException', 'errorMessage' => 'exception4', 'type' => 'php'],
            ['errorClass' => 'Bugsnag\Tests\Fakes\SomeException', 'errorMessage' => 'exception5', 'type' => 'php'],
        ];

        $this->assertSame($expected, $actual);
    }

    public function testGetErrorsWithPhpError()
    {
        $report = Report::fromPHPError($this->config, E_WARNING, 'bad stuff!', '/usr/src/stuff.php', 1234);
        $actual = $report->getErrors();

        $expected = [
            ['errorClass' => 'PHP Warning', 'errorMessage' => 'bad stuff!', 'type' => 'php'],
        ];

        $this->assertSame($expected, $actual);
    }

    public function testFeatureFlagsCanBeAddedToAReport()
    {
        $this->report->addFeatureFlag('a name');
        $this->report->addFeatureFlag('another name', 'with variant');

        $expectedEventApi = [
            ['featureFlag' => 'a name'],
            ['featureFlag' => 'another name', 'variant' => 'with variant'],
        ];

        $expectedGetter = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name', 'with variant'),
        ];

        $this->assertSame($expectedEventApi, $this->report->toArray()['featureFlags']);
        $this->assertEquals($expectedGetter, $this->report->getFeatureFlags());
    }

    public function testMultipleFeatureFlagsCanBeAddedToAReportAtOnce()
    {
        $this->report->addFeatureFlag('a name');
        $this->report->addFeatureFlags([
            new FeatureFlag('another name', 'with variant'),
            new FeatureFlag('name3'),
            new FeatureFlag('four', 'yes'),
        ]);

        $expectedEventApi = [
            ['featureFlag' => 'a name'],
            ['featureFlag' => 'another name', 'variant' => 'with variant'],
            ['featureFlag' => 'name3'],
            ['featureFlag' => 'four', 'variant' => 'yes'],
        ];

        $expectedGetter = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name', 'with variant'),
            new FeatureFlag('name3'),
            new FeatureFlag('four', 'yes'),
        ];

        $this->assertSame($expectedEventApi, $this->report->toArray()['featureFlags']);
        $this->assertEquals($expectedGetter, $this->report->getFeatureFlags());
    }

    public function testAFeatureFlagCanBeRemovedFromAReport()
    {
        $this->report->addFeatureFlag('a name');
        $this->report->addFeatureFlag('another name', 'with variant');

        $this->report->clearFeatureFlag('another name');

        $expectedEventApi = [['featureFlag' => 'a name']];
        $expectedGetter = [new FeatureFlag('a name')];

        $this->assertSame($expectedEventApi, $this->report->toArray()['featureFlags']);
        $this->assertEquals($expectedGetter, $this->report->getFeatureFlags());
    }

    public function testAllFeatureFlagsCanBeRemovedFromAReport()
    {
        $this->report->addFeatureFlag('a name');
        $this->report->addFeatureFlag('another name', 'with variant');

        $this->report->clearFeatureFlags();

        $this->assertSame([], $this->report->toArray()['featureFlags']);
        $this->assertSame([], $this->report->getFeatureFlags());
    }

    public function testReportFeatureFlagsAreInitialisedFromConfiguration()
    {
        $this->config->addFeatureFlag('a name');
        $this->config->addFeatureFlag('another name', 'with variant');

        $report = Report::fromNamedError($this->config, 'Name', 'Message');

        $report->addFeatureFlag('yet another feature flag');

        $expectedEventApi = [
            ['featureFlag' => 'a name'],
            ['featureFlag' => 'another name', 'variant' => 'with variant'],
            ['featureFlag' => 'yet another feature flag'],
        ];

        $expectedGetter = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name', 'with variant'),
            new FeatureFlag('yet another feature flag'),
        ];

        $this->assertSame($expectedEventApi, $report->toArray()['featureFlags']);
        $this->assertEquals($expectedGetter, $report->getFeatureFlags());
    }

    public function testMutatingReportFeatureFlagsDoesNotAffectConfiguration()
    {
        $this->config->addFeatureFlag('a name');

        $report = Report::fromNamedError($this->config, 'Name', 'Message');

        $report->addFeatureFlag('another name');

        $expectedEventApi = [
            ['featureFlag' => 'a name'],
            ['featureFlag' => 'another name'],
        ];

        $expectedGetter = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name'),
        ];

        $this->assertSame($expectedEventApi, $report->toArray()['featureFlags']);
        $this->assertEquals($expectedGetter, $report->getFeatureFlags());

        $expected = [
            new FeatureFlag('a name'),
        ];

        $this->assertEquals($expected, $this->config->getFeatureFlagsCopy()->toArray());
    }

    public function testFeatureFlagsCanBeAccessedFromAReport()
    {
        $this->config->addFeatureFlag('a name');
        $this->config->addFeatureFlag('another name', 'with variant');

        $report = Report::fromNamedError($this->config, 'Name', 'Message');

        $report->addFeatureFlag('yet another feature flag');

        $expected = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name', 'with variant'),
            new FeatureFlag('yet another feature flag'),
        ];

        $this->assertEquals($expected, $report->getFeatureFlags());
    }
}
