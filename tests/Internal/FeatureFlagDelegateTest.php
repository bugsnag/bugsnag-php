<?php

namespace Bugsnag\Tests;

use Bugsnag\FeatureFlag;
use Bugsnag\Internal\FeatureFlagDelegate;

class FeatureFlagDelegateTest extends TestCase
{
    public function testAdd()
    {
        $delegate = new FeatureFlagDelegate();

        $delegate->add('a name', null);
        $delegate->add('another name', 'a variant');

        $expected = [
            new FeatureFlag('a name'),
            new FeatureFlag('another name', 'a variant'),
        ];

        $this->assertEquals($expected, $delegate->toArray());
    }

    public function testMerge()
    {
        $delegate = new FeatureFlagDelegate();

        $delegate->add('name', null);
        $delegate->merge([
            new FeatureFlag('2flag', '2variant'),
            new FeatureFlag('flag', 'abc'),
        ]);

        $expected = [
            new FeatureFlag('name'),
            new FeatureFlag('2flag', '2variant'),
            new FeatureFlag('flag', 'abc'),
        ];

        $this->assertEquals($expected, $delegate->toArray());

        $delegate->merge([
            // replace the 'name' flag with one that has a variant
            new FeatureFlag('name', 'with variant'),
            new FeatureFlag('final flag'),
        ]);

        $expected = [
            new FeatureFlag('2flag', '2variant'),
            new FeatureFlag('flag', 'abc'),
            new FeatureFlag('name', 'with variant'),
            new FeatureFlag('final flag'),
        ];

        $this->assertEquals($expected, $delegate->toArray());
    }

    public function testMergeIgnoresIncorrectTypes()
    {
        $delegate = new FeatureFlagDelegate();

        $delegate->merge([
            new FeatureFlag('2flag', '2variant'),
            null,
            'hello',
            1234,
            new FeatureFlag('3flag', '3variant'),
        ]);

        $expected = [
            new FeatureFlag('2flag', '2variant'),
            new FeatureFlag('3flag', '3variant'),
        ];

        $this->assertEquals($expected, $delegate->toArray());
    }

    public function testRemove()
    {
        $delegate = new FeatureFlagDelegate();

        $delegate->add('a name', null);
        $delegate->add('another name', 'a variant');

        $delegate->remove('a name');

        $expected = [
            new FeatureFlag('another name', 'a variant'),
        ];

        $this->assertEquals($expected, $delegate->toArray());

        $delegate->remove('another name');

        $this->assertSame([], $delegate->toArray());
    }

    public function testClear()
    {
        $delegate = new FeatureFlagDelegate();

        $delegate->add('a name', null);
        $delegate->add('another name', 'a variant');

        $delegate->clear();

        $this->assertSame([], $delegate->toArray());
    }
}
