<?php


namespace App\Migrations;


use Doctrine\Migrations\AbstractMigration;

abstract class Migration extends AbstractMigration
{
    protected function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (empty($value)) {
            return array();
        }

        $value = preg_replace_callback('/s:(\d+):"(.*?)";(?=\}|i|s|a)/s', function($match) {
            $length = strlen($match[2]);
            $data = $match[2];

            return "s:$length:\"$data\";";
        }, $value );

        $val = @unserialize($value);
        if ($val === false && $value != 'b:0;') {
            // TODO: this is temporary, we need to fix db entries
            return array();

            //throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }
}