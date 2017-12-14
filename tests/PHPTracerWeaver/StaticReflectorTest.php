<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Reflector\StaticReflector;
use PHPUnit\Framework\TestCase;

class StaticReflectorTest extends TestCase
{
    /**
     * @return void
     */
    public function testCanScanSources(): void
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo implements Bar, Doink {}');
        $reflector->scanString('<?php class Zip implements Bar {}');
        $this->assertSame(['Bar', 'Doink'], $reflector->ancestors('Foo'));
        $this->assertSame(['Bar'], $reflector->ancestors('Zip'));
    }

    /**
     * @return void
     */
    public function testCanCollateSame(): void
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo extends Bar {}');
        $reflector->scanString('<?php class Zip extends Bar {}');
        $this->assertSame('Foo', $reflector->collate('Foo', 'Foo'));
    }

    /**
     * @return void
     */
    public function testCanCollateDirectInheritance(): void
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo extends Bar {}');
        $reflector->scanString('<?php class Zip extends Bar {}');
        $this->assertSame('Bar', $reflector->collate('Foo', 'Zip'));
    }

    /**
     * @return void
     */
    public function testCanCollateChildToParent(): void
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo {}');
        $reflector->scanString('<?php class Bar extends Foo {}');
        $this->assertSame('Foo', $reflector->collate('Foo', 'Bar'));
    }

    /**
     * @return void
     */
    public function testCanCollateParentToChild(): void
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo {}');
        $reflector->scanString('<?php class Bar extends Foo {}');
        $this->assertSame('Foo', $reflector->collate('Bar', 'Foo'));
    }
}
