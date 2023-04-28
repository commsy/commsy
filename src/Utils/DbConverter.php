<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class DbConverter
{
    public static function convertToPHPValue($value)
    {
        if (null === $value) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (empty($value)) {
            return [];
        }

        $value = preg_replace_callback('/s\:(\d+)\:\"(.*?)\";/s', function ($match) {
            $length = strlen($match[2]);
            $data = $match[2];

            return "s:$length:\"$data\";";
        }, (string) $value);

        $val = @unserialize($value);
        if (false === $val && 'b:0;' != $value) {
            // TODO: this is temporary, we need to fix db entries
            return [];

            // throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }

    public static function getFilePath(int $portalId, int $fileContextId)
    {
        $secondContext = (string) $fileContextId;
        $secondContextLength = strlen($secondContext);
        $secondFolder = '';
        for ($i = 0; $i < $secondContextLength; ++$i) {
            if ($i > 0 && 0 == $i % 4) {
                $secondFolder .= '/';
            }

            $secondFolder .= $secondContext[$i];
        }
        $secondFolder .= '_';

        return '../files/'.$portalId.'/'.$secondFolder.'/';
    }

    /**
     * @return void
     *
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
                ->select('t.'.$idColumnIdentifier, 't.extras')
                ->from($tableName, 't')
                ->where('t.extras LIKE "%'.$extraToRemove.'%"');
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
