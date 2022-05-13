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
            ['featureFlag' => 'a name'],
            ['featureFlag' => 'another name', 'variant' => 'a variant'],
        ];

        $this->assertSame($expected, $delegate->toArray());
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
            ['featureFlag' => 'name'],
            ['featureFlag' => '2flag', 'variant' => '2variant'],
            ['featureFlag' => 'flag', 'variant' => 'abc'],
        ];

        $this->assertSame($expected, $delegate->toArray());

        $delegate->merge([
            // replace the 'name' flag with one that has a variant
            new FeatureFlag('name', 'with variant'),
            new FeatureFlag('final flag'),
        ]);

        $expected = [
            ['featureFlag' => '2flag', 'variant' => '2variant'],
            ['featureFlag' => 'flag', 'variant' => 'abc'],
            ['featureFlag' => 'name', 'variant' => 'with variant'],
            ['featureFlag' => 'final flag'],
        ];

        $this->assertSame($expected, $delegate->toArray());
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
            ['featureFlag' => '2flag', 'variant' => '2variant'],
            ['featureFlag' => '3flag', 'variant' => '3variant'],
        ];

        $this->assertSame($expected, $delegate->toArray());
    }

    public function testRemove()
    {
        $delegate = new FeatureFlagDelegate();

        $delegate->add('a name', null);
        $delegate->add('another name', 'a variant');

        $delegate->remove('a name');

        $expected = [
            ['featureFlag' => 'another name', 'variant' => 'a variant'],
        ];

        $this->assertSame($expected, $delegate->toArray());

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
