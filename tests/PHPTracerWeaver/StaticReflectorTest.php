<?php

use PHPUnit\Framework\TestCase;

class TestOfStaticReflector extends TestCase
{
    public function testCanScanSources()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo implements Bar, Doink {}');
        $reflector->scanString('<?php class Zip implements Bar {}');
        $this->assertSame(['Bar', 'Doink'], $reflector->ancestors('Foo'));
        $this->assertSame(['Bar'], $reflector->ancestors('Zip'));
    }

    public function testCanCollateSame()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo extends Bar {}');
        $reflector->scanString('<?php class Zip extends Bar {}');
        $this->assertSame('Foo', $reflector->collate('Foo', 'Foo'));
    }

    public function testCanCollateDirectInheritance()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo extends Bar {}');
        $reflector->scanString('<?php class Zip extends Bar {}');
        $this->assertSame('Bar', $reflector->collate('Foo', 'Zip'));
    }

    public function testCanCollateChildToParent()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo {}');
        $reflector->scanString('<?php class Bar extends Foo {}');
        $this->assertSame('Foo', $reflector->collate('Foo', 'Bar'));
    }

    public function testCanCollateParentToChild()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<?php class Foo {}');
        $reflector->scanString('<?php class Bar extends Foo {}');
        $this->assertSame('Foo', $reflector->collate('Bar', 'Foo'));
    }
}
