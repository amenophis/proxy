<?php

namespace Tests\Amenophis\Proxy\Pki;

use Amenophis\Proxy\Exception\PlatformNotSupported;
use Amenophis\Proxy\Pki\Darwin;
use Amenophis\Proxy\Pki\Linux;
use Amenophis\Proxy\Pki\Pki;
use Amenophis\Proxy\Pki\PkiFactory;
use PHPUnit\Framework\TestCase;

class PkiFactoryTest extends TestCase
{
    /**
     * @dataProvider getSupportedPlatforms
     */
    public function testSupportedPlatforms(string $platformName, string $class)
    {
        $platform = PkiFactory::create($platformName);

        $this->assertInstanceOf($class, $platform);
    }

    public function getSupportedPlatforms()
    {
        yield ['Darwin', Darwin::class];
        yield ['Linux', Linux::class];
    }

    /**
     * @dataProvider getUnsupportedPlatforms
     */
    public function testUnsupportedPlatforms(string $platformName)
    {
        $this->expectException(PlatformNotSupported::class);
        PkiFactory::create($platformName);
    }

    public function getUnsupportedPlatforms()
    {
        yield ['Windows'];
        yield ['BSD'];
        yield ['Solaris'];
        yield ['Unknown'];
    }
}