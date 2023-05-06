<?php declare(strict_types=1);

namespace IC\Html\Tests;

use IC\Html\ClassList;
use PHPUnit\Framework\TestCase;

class ClassListTest extends TestCase
{

    public function testSanitization(): void
    {
        $classes = new ClassList();

        $classes->add('😀', '-', '', '%f0');
        $this->assertSame([], $classes->toArray());

        $classes->add('---test-hyphens', 'test%f0-octets', 'test-😀emoji', '231test-numb3rs1');
        $this->assertSame(['-test-hyphens', 'test-octets', 'test-emoji', 'test-numb3rs1'], $classes->toArray());
    }

    public function testContains(): void
    {
        $classes = new ClassList('test-1', 'test_2');
        $this->assertTrue($classes->contains('test-1'));
        $this->assertTrue($classes->contains('test-1', 'test_2'));
        $this->assertFalse($classes->contains('test-1', 'test--4'));
    }

    public function testAdd(): void
    {
        $classes = new ClassList();

        $classes->add('foo bar');
        $this->assertSame(['foo', 'bar'], $classes->toArray());

        $classes->add('foo bar', 'test');
        $this->assertSame(['foo', 'bar', 'test'], $classes->toArray());
    }

    public function testRemove(): void
    {
        $classes = new ClassList('foo', 'bar', 'baz');

        $classes->remove('foo');
        $this->assertSame(['bar', 'baz'], $classes->toArray());

        $classes = new ClassList('foo', 'bar', 'baz', 'test');

        $classes->remove('foo', 'baz', 'null');
        $this->assertSame(['bar', 'test'], $classes->toArray());
    }

    public function testReplace(): void
    {
        $classes = new ClassList('foo', 'bar', 'baz');

        $classes->replace('baz', 'test');
        $this->assertSame(['foo', 'bar', 'test'], $classes->toArray());

        $classes->replace('b', '-test-', true);
        $this->assertSame(['foo', '-test-ar', 'test'], $classes->toArray());

        $classes = new ClassList('foo', 'bar', 'baz');
        $classes->replace('b', '-test-', true);
        $this->assertSame(['foo', '-test-ar', '-test-az'], $classes->toArray());

        $classes = new ClassList('foo', 'bor', 'baz');
        $classes->replace('o', '-test-', true);
        $this->assertSame(['f-test--test-', 'b-test-r', 'baz'], $classes->toArray());
    }

    public function testToggle(): void
    {
        $classes = new ClassList('foo', 'bar', 'baz');

        $this->assertTrue($classes->toggle('test'));
        $this->assertSame(['foo', 'bar', 'baz', 'test'], $classes->toArray());

        $this->assertFalse($classes->toggle('test'));
        $this->assertSame(['foo', 'bar', 'baz'], $classes->toArray());

        $this->assertFalse($classes->toggle('test', false));
        $this->assertSame(['foo', 'bar', 'baz'], $classes->toArray());

        $this->assertTrue($classes->toggle('foo', true));
        $this->assertSame(['foo', 'bar', 'baz'], $classes->toArray());

        $this->assertFalse($classes->toggle('__test', false));
    }

    public function testFilter(): void
    {
        $classes = new ClassList('foo', 'bar', 'baz');

        $classes->filter(fn($name) => str_starts_with($name, 'b'));
        $this->assertSame(['bar', 'baz'], $classes->toArray());
    }

    public function testMap(): void
    {
        $classes = new ClassList('foo', 'bar', 'baz');

        $classes->map(fn($name) => "test-$name");
        $this->assertSame(['test-foo', 'test-bar', 'test-baz'], $classes->toArray());
    }

}
