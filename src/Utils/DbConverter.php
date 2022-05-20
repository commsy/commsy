<?php


namespace App\Utils;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

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

    /**
     * @param Connection $connection
     * @param string $tableName
     * @param string $idColumnIdentifier
     * @param array $remove
     * @return void
     * @throws Exception
     */
    public static function removeExtra(
        Connection $connection,
        string $tableName,
        string $idColumnIdentifier,
        array $remove
    ) {
        $queryBuilder = $connection->createQueryBuilder();

        foreach ($remove as $extraToRemove) {
            $qb = $queryBuilder
                ->select('t.' . $idColumnIdentifier, 't.extras')
                ->from($tableName, 't')
                ->where('t.extras LIKE "%' . $extraToRemove . '%"');
            $entries = $qb->executeQuery()->fetchAllAssociative();

            foreach ($entries as $entry) {
                $extras = DbConverter::convertToPHPValue($entry['extras']);

                if (isset($extras[$extraToRemove])) {
                    unset($extras[$extraToRemove]);
                }

                $connection->update($tableName, [
                    'extras' => serialize($extras),
                ], [
                    $idColumnIdentifier => $entry[$idColumnIdentifier],
                ]);
            }
        }
    }
}