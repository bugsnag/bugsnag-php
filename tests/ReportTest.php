<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Report;
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
        $this->assertSame('3', $event['payloadVersion']);
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

    public function testNoTokensAvailable()
    {
        $this->report->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $event = $this->report->toArray();
        $this->assertArrayNotHasKey('tokens', $event);
    }

    public function testTokensArePresent()
    {
        $this->report->setPHPError(E_ERROR, 'Broken', __FILE__, 123);

        $event = $this->report->toArray();
        $this->assertArrayHasKey('tokens', $event);
        $this->assertInternalType('array', $event['tokens']);
        $this->assertCount(101, $event['tokens']);
        $this->assertSame(['token' => 'T_CONSTANT_ENCAPSED_STRING', 'content' => '\'file\'', 'line' => 114], $event['tokens'][0]);
        $this->assertSame(['token' => 'T_VARIABLE', 'content' => '$this', 'line' => 123], $event['tokens'][60]);
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

    public function testPreviousException()
    {
        $exception = new Exception('secondly', 65533, new Exception('firstly'));

        $report = Report::fromPHPThrowable($this->config, $exception);

        $event = $report->toArray();

        $this->assertCount(2, $event['exceptions']);
        $this->assertSame($event['exceptions'][0]['message'], 'firstly');
        $this->assertSame($event['exceptions'][1]['message'], 'secondly');
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

        $this->assertSame(null, $this->report->getMessage());
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

    public function testSetTokens()
    {
        $this->report->setTokens(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->report->getTokens());
    }

    public function testSetTokensLarge()
    {
        $this->report->setTokens(['foo' => str_repeat('bar', 20000), 'bar' => str_repeat('baz', 20000)]);

        $this->assertSame(null, $this->report->getTokens());
    }
}
