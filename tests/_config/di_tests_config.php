<?php
/**
 * Конфиг Di для тестов.
 */
use Evas\Di;

use Evas\Auth\Auth;
use Evas\Auth\tests\help\GlobalDb;
use Evas\Db\Database;

return [
    'db' => Di\createOnce(Database::class, [
        GlobalDb::config()
    ]),
    'auth' => Di\createOnce(Auth::class, [
        Di\includeFile(__DIR__ . '/auth_tests_config.php')
    ]),
];
