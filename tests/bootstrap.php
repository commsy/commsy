<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// The test environment redirects files_directory to var/test-files (see
// config/services.yaml) so that test runs cannot touch uploaded development
// files. Foundry resets the database by replaying all migrations, and
// Version20230301172618 scans that directory with Finder, which throws when it
// does not exist - so make sure it is there before the first test starts.
$testFilesDirectory = dirname(__DIR__).'/var/test-files';
if (!is_dir($testFilesDirectory)) {
    mkdir($testFilesDirectory, 0777, true);
}
