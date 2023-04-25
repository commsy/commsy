<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

class FixExtras extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $conn = $this->entityManager->getConnection();
        $schema = $conn->createSchemaManager();

        foreach ($schema->listTables() as $table) {
            if ($table->hasColumn('extras')) {
                if ($table->hasColumn('files_id')) {
                    $identifier = 'files_id';
                } else if ($table->hasColumn('id')) {
                    $identifier = 'id';
                } else {
                    $identifier = 'item_id';
                }

                // empty extras or "b:0;" to null
                $this->executeSQL("UPDATE {$table->getName()} SET extras = null WHERE extras = '' OR extras = '0' OR extras = 'b:0;'", $io);

                $stmt = $this->executeSQL("SELECT $identifier, extras FROM {$table->getName()} WHERE extras IS NOT NULL", $io);
                $extras = $stmt->fetchAllKeyValue();
                foreach ($extras as $itemId => $extra) {
                    $unserialized = @unserialize($extra);

                    if ($unserialized === false) {

                        $repaired = $this->repairSerializedArray($extra);
                        $io->info("Fixing to: $repaired");

                        if (!@unserialize($repaired)) {
                            $io->error("Error fixing {$table->getName()}($itemId): '$extra'");
                            return false;
                        }

                        $sql = "UPDATE {$table->getName()} SET extras = :extras WHERE $identifier = :itemId";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindValue('extras', $repaired);
                        $stmt->bindValue('itemId', $itemId);

                        $stmt->executeQuery();
                    }
                }
            }
        }

        return true;
    }

    /**
     * Extract what remains from an unintentionally truncated serialized string
     *
     * @param string $serialized The serialized array
     */
    private function repairSerializedArray(string $serialized): string
    {
        $tmp = preg_replace('/^a:\d+:\{/', '', $serialized);
        return serialize($this->repairSerializedArray_R($tmp));
    }

    /**
     * The recursive function that does all of the heavy lifing. Do not call directly.
     * @param string $broken The broken serialzized array
     * @return array Returns the repaired string
     */
    private function repairSerializedArray_R(string &$broken): array
    {
        // array and string length can be ignored
        // sample serialized data
        // a:0:{}
        // s:4:"four";
        // i:1;
        // b:0;
        // N;
        $data = [];
        $index = null;
        $len = strlen($broken);
        $i = 0;

        while (strlen((string) $broken)) {
            $i++;
            if ($i > $len) {
                break;
            }

            if (str_starts_with((string) $broken, '}')) // end of array
            {
                $broken = substr((string) $broken, 1);
                return $data;
            } else {
                $bite = substr((string) $broken, 0, 2);
                switch ($bite) {
                    case 's:': // key or value
                        $re = '/^s:\d+:"([^\"]*)";/';
                        if (preg_match($re, (string) $broken, $m)) {
                            if ($index === null) {
                                $index = $m[1];
                            } else {
                                $data[$index] = $m[1];
                                $index = null;
                            }
                            $broken = preg_replace($re, '', (string) $broken);
                        }
                        break;

                    case 'i:': // key or value
                        $re = '/^i:(\d+);/';
                        if (preg_match($re, (string) $broken, $m)) {
                            if ($index === null) {
                                $index = (int)$m[1];
                            } else {
                                $data[$index] = (int)$m[1];
                                $index = null;
                            }
                            $broken = preg_replace($re, '', (string) $broken);
                        }
                        break;

                    case 'b:': // value only
                        $re = '/^b:[01];/';
                        if (preg_match($re, (string) $broken, $m)) {
                            $data[$index] = (bool)$m[1];
                            $index = null;
                            $broken = preg_replace($re, '', (string) $broken);
                        }
                        break;

                    case 'a:': // value only
                        $re = '/^a:\d+:\{/';
                        if (preg_match($re, (string) $broken, $m)) {
                            $broken = preg_replace('/^a:\d+:\{/', '', (string) $broken);
                            $data[$index] = $this->repairSerializedArray_R($broken);
                            $index = null;
                        }
                        break;

                    case 'N;': // value only
                        $broken = substr((string) $broken, 2);
                        $data[$index] = null;
                        $index = null;
                        break;
                }
            }
        }

        return $data;
    }
}
