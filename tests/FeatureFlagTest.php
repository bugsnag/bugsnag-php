<?php

namespace Bugsnag\Tests;

use Bugsnag\FeatureFlag;

class FeatureFlagTest extends TestCase
{
    /**
     * @dataProvider variantDataProvider
     *
     * @param mixed $variant
     * @param string|null $expected
     *
     * @return void
     */
    public function testVariantIsCoercedToStringOrNull($variant, $expected)
    {
        $flag = new FeatureFlag('name', $variant);

        $this->assertSame($expected, $flag->getVariant());
    }

    public function variantDataProvider()
    {
        return [
            ['a variant', 'a variant'],
            [null, null],
            [1234, '1234'],
            [12.34, '12.34'],
            [true, 'true'],
            [false, 'false'],
            ['', ''],
            [(object) ['a' => 'hello', 'b' => 'hi'], '{"a":"hello","b":"hi"}'],
            [new FeatureFlag('x'), '{}'],
            [['a', 'b', 'c'], '["a","b","c"]'],
        ];
    }

    public function testToArrayWithOnlyName()
    {
        $flag = new FeatureFlag('this is the name');

        $expected = ['featureFlag' => 'this is the name'];

        $this->assertSame($expected, $flag->toArray());
    }

    public function testToArrayWithNameAndNullVariant()
    {
        $flag = new FeatureFlag('this is the name', null);

        $expected = ['featureFlag' => 'this is the name'];

        $this->assertSame($expected, $flag->toArray());
    }

    public function testToArrayWithNameAndVariant()
    {
        $flag = new FeatureFlag('this is the name', 'and this is the variant');

        $expected = [
            'featureFlag' => 'this is the name',
            'variant' => 'and this is the variant',
        ];

        $this->assertSame($expected, $flag->toArray());
    }
}
