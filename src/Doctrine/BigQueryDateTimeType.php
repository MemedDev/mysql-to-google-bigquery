<?php
namespace MysqlToGoogleBigQuery\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class BigQueryDateTimeType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'datetime';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === '0000-00-00 00:00:00') {
            return null;
        }

        return str_replace(' ', 'T', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return str_replace('T', ' ', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bigquerydatetime';
    }
}
