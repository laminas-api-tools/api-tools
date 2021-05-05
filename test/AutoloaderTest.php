<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools;

use Laminas\ApiTools\Autoloader;
use LaminasTest\ApiTools\TestAsset\Foo_Bar;
use LaminasTest\ApiTools\TestAsset\Foo_Bar\Baz_Bat;
use PHPUnit\Framework\TestCase;

use function class_exists;

class AutoloaderTest extends TestCase
{
    /** @psalm-return array<string, array{0: class-string}> */
    public function classesToAutoload(): array
    {
        return [
            'Foo_Bar'         => [Foo_Bar::class],
            'Foo_Bar\Baz_Bat' => [Baz_Bat::class],
        ];
    }

    /**
     * @dataProvider classesToAutoload
     */
    public function testAutoloaderDoesNotTransformUnderscoresToDirectorySeparators(string $className)
    {
        $autoloader = new Autoloader([
            'namespaces' => [
                'LaminasTest\ApiTools\TestAsset' => __DIR__ . '/TestAsset',
            ],
        ]);
        $result     = $autoloader->autoload($className);
        $this->assertFalse(false === $result);
        $this->assertTrue(class_exists($className, false));
    }
}
