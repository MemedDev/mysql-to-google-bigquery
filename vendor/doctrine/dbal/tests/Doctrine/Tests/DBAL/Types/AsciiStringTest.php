<?php

declare(strict_types=1);

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\AsciiStringType;
use PHPUnit\Framework\TestCase;

class AsciiStringTest extends TestCase
{
    /** @var AsciiStringType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new AsciiStringType();
    }

    public function testReturnCorrectBindingType(): void
    {
        self::assertEquals($this->type->getBindingType(), ParameterType::ASCII);
    }

    public function testDelegateToPlatformForSqlDeclaration(): void
    {
        $columnDefinitions = [
            [['length' => 12, 'fixed' => true]],
            [['length' => 14]],
        ];

        foreach ($columnDefinitions as $column) {
            $platform = $this->createMock(AbstractPlatform::class);
            $platform->expects(self::once())
                ->method('getAsciiStringTypeDeclarationSQL')
                ->with($column);

            $this->type->getSQLDeclaration($column, $platform);
        }
    }
}
