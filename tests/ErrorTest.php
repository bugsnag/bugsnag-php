<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Error;

class ErrorTest extends AbstractTestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;
    /** @var \Bugsnag\Error */
    protected $error;

    protected function setUp()
    {
        $this->config = new Configuration();
        $this->diagnostics = new Diagnostics($this->config);
        $this->error = $this->getError();
    }

    public function testMetaData()
    {
        $this->error->setMetaData(['Testing' => ['globalArray' => 'hi']]);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']['Testing']['globalArray'], 'hi');
    }

    public function testMetaDataMerging()
    {
        $this->error->setMetaData(['Testing' => ['globalArray' => 'hi']]);
        $this->error->setMetaData(['Testing' => ['localArray' => 'yo']]);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']['Testing']['globalArray'], 'hi');
        $this->assertEquals($errorArray['metaData']['Testing']['localArray'], 'yo');
    }

    public function testFiltering()
    {
        $this->error->setMetaData(['Testing' => ['password' => '123456']]);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']['Testing']['password'], '[FILTERED]');
    }

    public function testExceptionsNotFiltered()
    {
        $this->config->filters = ['code'];
        $this->error->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        // 'Code' should not be filtered so should remain still be an array
        $this->assertInternalType('array', $errorArray['exceptions'][0]['stacktrace'][0]['code']);
    }

    public function testNoticeName()
    {
        $this->error->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['exceptions'][0]['errorClass'], 'PHP Notice');
    }

    public function testErrorName()
    {
        $this->error->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['exceptions'][0]['errorClass'], 'PHP Fatal Error');
    }

    public function testErrorPayloadVersion()
    {
        $this->error->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['payloadVersion'], '2');
    }

    public function testNoticeSeverity()
    {
        $this->error->setPHPError(E_NOTICE, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['severity'], 'info');
    }

    public function testErrorSeverity()
    {
        $this->error->setPHPError(E_ERROR, 'Broken', 'file', 123);

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['severity'], 'error');
    }

    public function testManualSeverity()
    {
        $this->error->setSeverity('error');

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['severity'], 'error');
    }

    public function testInvalidSeverity()
    {
        $this->error->setSeverity('bunk');

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['severity'], 'warning');
    }

    public function testPreviousException()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $exception = new Exception('secondly', 65533, new Exception('firstly'));

            $error = Error::fromPHPThrowable($this->config, $this->diagnostics, $exception);

            $errorArray = $error->toArray();

            $this->assertEquals(count($errorArray['exceptions']), 2);
            $this->assertEquals($errorArray['exceptions'][0]['message'], 'firstly');
            $this->assertEquals($errorArray['exceptions'][1]['message'], 'secondly');
        }
    }

    public function testErrorGroupingHash()
    {
        $this->error->setGroupingHash('herp#derp');

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['groupingHash'], 'herp#derp');
    }

    public function testErrorGroupingHashNotSet()
    {
        $errorArray = $this->error->toArray();
        $this->assertArrayNotHasKey('groupingHash', $errorArray);
    }

    public function testSetPHPException()
    {
        $exception = version_compare(PHP_VERSION, '7.0.0', '>=') ? new \Error() : new Exception();
        $this->error->setPHPException($exception);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBadSetName()
    {
        $this->error->setName([]);
    }

    /**
     * @expectedException InvalidArgumentException
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
}
