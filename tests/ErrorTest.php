<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Exception;
use InvalidArgumentException;
use ParseError;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class ErrorTest extends TestCase
{
    protected $config;
    protected $error;

    protected function setUp()
    {
        $this->config = new Configuration('example-key');
        $this->error = Error::fromNamedError($this->config, 'Name', 'Message');
    }

    public function testMetaData()
    {
        $this->error->setMetaData(['Testing' => ['globalArray' => 'hi']]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi']], $this->error->toArray()['metaData']);
    }

    public function testMetaDataMerging()
    {
        $this->error->setMetaData(['Testing' => ['globalArray' => 'hi']]);
        $this->error->setMetaData(['Testing' => ['localArray' => 'yo']]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi', 'localArray' => 'yo']], $this->error->toArray()['metaData']);
    }

    public function testMetaDataObj()
    {
        $this->error->setMetaData(['Testing' => (object) ['globalArray' => 'hi']]);

        $this->assertSame(['Testing' => ['globalArray' => 'hi']], $this->error->toArray()['metaData']);
    }

    public function testUser()
    {
        $this->error->setUser(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->error->toArray()['user']);
    }

    public function testFiltering()
    {
        $this->error->setMetaData(['Testing' => ['password' => '123456']]);

        $this->assertSame(['password' => '[FILTERED]'], $this->error->toArray()['metaData']['Testing']);
    }

    public function testExceptionsNotFiltered()
    {
        $this->config->setFilters(['code']);
        $this->error->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        // 'Code' should not be filtered so should remain still be an array
        $this->assertInternalType('array', $errorArray['exceptions'][0]['stacktrace'][0]['code']);
    }

    public function testNoticeName()
    {
        $this->error->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['exceptions'][0]['errorClass'], 'PHP Notice');
    }

    public function testErrorName()
    {
        $this->error->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['exceptions'][0]['errorClass'], 'PHP Fatal Error');
    }

    public function testErrorPayloadVersion()
    {
        $this->error->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['payloadVersion'], '2');
    }

    public function testNoticeSeverity()
    {
        $this->error->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['severity'], 'info');
        $this->assertCount(1, $errorArray['exceptions']);
    }

    public function testErrorSeverity()
    {
        $this->error->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['severity'], 'error');
        $this->assertCount(1, $errorArray['exceptions']);
    }

    public function testRecoverableErrorSeverity()
    {
        $this->error->setPHPError(E_RECOVERABLE_ERROR, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['severity'], 'error');
        $this->assertCount(1, $errorArray['exceptions']);
    }

    public function testFatalErrorSeverity()
    {
        $this->error->setPHPError(E_ERROR, 'Broken', 'file', 123, true);

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['severity'], 'error');
        $this->assertCount(1, $errorArray['exceptions']);
        $this->assertCount(1, $errorArray['exceptions'][0]['stacktrace']);
    }

    public function testManualSeverity()
    {
        $this->error->setSeverity('error');

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['severity'], 'error');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidSeverity()
    {
        $this->error->setSeverity('bunk');
    }

    public function testPreviousException()
    {
        $exception = new Exception('secondly', 65533, new Exception('firstly'));

        $error = Error::fromPHPThrowable($this->config, $exception);

        $errorArray = $error->toArray();

        $this->assertCount(2, $errorArray['exceptions']);
        $this->assertSame($errorArray['exceptions'][0]['message'], 'firstly');
        $this->assertSame($errorArray['exceptions'][1]['message'], 'secondly');
    }

    public function testErrorGroupingHash()
    {
        $this->error->setGroupingHash('herp#derp');

        $errorArray = $this->error->toArray();
        $this->assertSame($errorArray['groupingHash'], 'herp#derp');
    }

    public function testErrorGroupingHashNotSet()
    {
        $errorArray = $this->error->toArray();
        $this->assertArrayNotHasKey('groupingHash', $errorArray);
    }

    public function testSetPHPThrowable()
    {
        $this->assertSame($this->error, $this->error->setPHPThrowable(new Exception()));
    }

    public function testSetPHPAnotherThrowable()
    {
        $exception = class_exists(ParseError::class) ? new ParseError() : new InvalidArgumentException();

        $this->assertSame($this->error, $this->error->setPHPThrowable($exception));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetNotThrowable()
    {
        $this->assertSame($this->error, $this->error->setPHPThrowable(new stdClass()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetNotObject()
    {
        $this->assertSame($this->error, $this->error->setPHPThrowable('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadSetName()
    {
        $this->error->setName([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadSetMessage()
    {
        $this->error->setMessage(new stdClass());
    }

    public function testGoodSetName()
    {
        $this->error->setName(123);

        $this->assertSame('123', $this->error->name);
    }

    public function testGoodSetMessage()
    {
        $this->error->setMessage('foo bar baz');

        $this->assertSame('foo bar baz', $this->error->message);
    }

    public function testEmptySetMessage()
    {
        $this->error->setMessage('');

        $this->assertSame('', $this->error->message);
    }

    public function testNullSetMessage()
    {
        $this->error->setMessage(null);

        $this->assertSame(null, $this->error->message);
    }
}
