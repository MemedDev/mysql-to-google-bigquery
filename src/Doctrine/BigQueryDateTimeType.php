<?php
namespace MysqlToGoogleBigQuery\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class BigQueryDateTimeType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'datetime';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return str_replace(' ', 'T', $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return str_replace('T', ' ', $value);
    }

    public function getName()
    {
        return 'bigquerydatetime';
    }
}
