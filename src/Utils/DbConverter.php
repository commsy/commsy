<?php


namespace App\Utils;


class DbConverter
{
    public static function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (empty($value)) {
            return array();
        }

        $value = preg_replace_callback('/s\:(\d+)\:\"(.*?)\";/s', function($match) {
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

    public static function getFilePath(int $portalId, int $fileContextId)
    {
        $secondContext = (string) $fileContextId;
        $secondContextLength = strlen($secondContext);
        $secondFolder = '';
        for ($i = 0; $i < $secondContextLength; $i++) {
            if ($i > 0 && $i % 4 == 0) {
                $secondFolder .= '/';
            }

            $secondFolder .= $secondContext[$i];
        }
        $secondFolder .= '_';

        return '../files/' . $portalId . '/' . $secondFolder . '/';
    }
}