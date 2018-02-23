<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Stacktrace;
use Exception;
use InvalidArgumentException;
use ParseError;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class ReportTest extends TestCase
{
    protected $config;
    protected $report;

    protected function setUp()
    {
        $this->config = new Configuration('example-key');
        $this->report = Report::fromNamedError($this->config, 'Name', 'Message');
    }

    public function testDeviceData()
    {
        $data = $this->report->toArray();

        $this->assertCount(2, $data['device']);
        $this->assertInternalType('string', $data['device']['time']);
        $this->assertSame(php_uname('n'), $data['device']['hostname']);
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

    public function testUser()
    {
        $this->report->setUser(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->report->toArray()['user']);
    }

    public function testFiltering()
    {
        $this->report->setMetaData(['Testing' => ['password' => '123456']]);

        $this->assertSame(['password' => '[FILTERED]'], $this->report->toArray()['metaData']['Testing']);
    }

    public function testExceptionsNotFiltered()
    {
        $this->config->setFilters(['code']);
        $this->report->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        // 'Code' should not be filtered so should remain still be an array
        $this->assertInternalType('array', $event['exceptions'][0]['stacktrace'][0]['code']);
    }

    public function testCanGetStacktrace()
    {
        $this->report->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $trace = $this->report->getStacktrace();

        $this->assertInstanceOf(Stacktrace::class, $trace);
        $this->assertCount(8, $trace->toArray());
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidSeverity()
    {
        $this->report->setSeverity('bunk');
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetNotThrowable()
    {
        $this->assertSame($this->report, $this->report->setPHPThrowable(new stdClass()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetNotObject()
    {
        $this->assertSame($this->report, $this->report->setPHPThrowable('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadSetName()
    {
        $this->report->setName([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadSetMessage()
    {
        $this->report->setMessage(new stdClass());
    }

    public function testGoodSetName()
    {
        $this->report->setName(123);

        $this->assertSame('123', $this->report->getName());
    }

    public function testGoodSetMessage()
    {
        $this->report->setMessage('foo bar baz');

        $this->assertSame('foo bar baz', $this->report->getMessage());
    }

    public function testEmptySetMessage()
    {
        $this->report->setMessage('');

        $this->assertSame('', $this->report->getMessage());
    }

    public function testNullSetMessage()
    {
        $this->report->setMessage(null);

        $this->assertNull($this->report->getMessage());
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
