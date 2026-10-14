<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

class FixExtras extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $conn = $this->entityManager->getConnection();
        $schema = $conn->createSchemaManager();

        foreach ($schema->introspectTables() as $table) {
            $tableName = $table->getObjectName()->getUnqualifiedName()->getValue();

            if ($table->hasColumn('extras')) {
                if ($table->hasColumn('files_id')) {
                    $identifier = 'files_id';
                } else if ($table->hasColumn('id')) {
                    $identifier = 'id';
                } else {
                    $identifier = 'item_id';
                }

                // empty extras or "b:0;" or 's:0:"";' to null
                $this->executeSQL("UPDATE {$tableName} SET extras = null WHERE extras = '' OR extras = '0' OR extras = 'b:0;' OR extras = 's:0:\"\";'", $io);

                $stmt = $this->executeSQL("SELECT $identifier, extras FROM {$tableName} WHERE extras IS NOT NULL", $io);
                $extras = $stmt->fetchAllKeyValue();
                foreach ($extras as $itemId => $extra) {
                    $unserialized = @unserialize($extra);

                    if ($unserialized === false) {
                        $io->info("Invalid entry found: $extra");
                        $repaired = $this->repairStringLength($extra);
                        $io->info("Fixing to: $repaired");

                        if (!@unserialize($repaired)) {
                            $io->error("Error fixing {$tableName}($itemId): '$extra'");
                            return false;
                        }

                        $sql = "UPDATE {$tableName} SET extras = :extras WHERE $identifier = :itemId";
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

    private function repairStringLength(string $serialized): string
    {
        // single string
        $fixed = preg_replace_callback(
            '/^s:(\d+)(?=:"(.*?)";$)/s',
            fn (array $match) => 's:' . strlen($match[2]),
            $serialized
        );

        if ($fixed === $serialized) {
            // nested strings
            $fixed = preg_replace_callback(
                '/(?<=^|;)s:(\d+)(?=:"(.*?)";(?:}|a:|s:|b:|d:|i:|o:|N;))/s',
                fn (array $match) => 's:' . strlen($match[2]),
                $serialized
            );
        }

        return $fixed;
    }
}
