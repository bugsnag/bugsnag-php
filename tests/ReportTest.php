<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Stacktrace;
use Bugsnag\Tests\Fakes\StringableObject;
use Exception;
use InvalidArgumentException;
use ParseError;
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
        Assert::matchesDateFormat('Y-m-d\TH:i:s\Z', $data['device']['time']);
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
}
