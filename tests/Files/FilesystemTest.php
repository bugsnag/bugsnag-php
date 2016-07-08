<?php

namespace Bugsnag\Tests\Files;

use Bugsnag\Files\Filesystem;
use Bugsnag\Files\Inspector;
use Bugsnag\Files\Parser;
use ReflectionClass;
use PHPUnit_Framework_TestCase as TestCase;

class FilesystemTest extends TestCase
{
    public function testCanInspectSelf()
    {
        $filesystem = new Filesystem();

        $inspector = $filesystem->inspect(__FILE__);

        $this->assertInstanceOf(Inspector::class, $inspector);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCanNotInspect()
    {
        $filesystem = new Filesystem();

        $inspector = $filesystem->inspect('foo-bar-baz');
    }

    public function testCanTakeCustomParser()
    {
        $filesystem = new Filesystem($parser = new Parser());

        $prop = (new ReflectionClass($filesystem))->getProperty('parser');

        $prop->setAccessible(true);

        $this->assertSame($parser, $prop->getValue($filesystem));
    }
}
