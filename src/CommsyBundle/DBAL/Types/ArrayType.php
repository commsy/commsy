<?php
namespace CommsyBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ArrayType as BaseType;

class ArrayType extends BaseType
{
    const MBARRAY = 'mb_array';

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        $value = preg_replace_callback('!s:(\d+):"(.*?)";!s', function($match) {
            return 's:' . strlen($match[2]) . ':\"' . $match[2] . '\";';
        }, $value);

        $val = unserialize($value);
        if ($val === false && $value != 'b:0;') {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::MBARRAY;
    }
}