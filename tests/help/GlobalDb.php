<?php
/**
 * Хелпер тестов модуля evas-auth.
 * @package evas-php/evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\tests\help;

use Evas\Db\Database;
use Evas\Web\WebApp as App;

class GlobalDb
{
    const CONFIG_PATH = __DIR__ . '/../../../evas-db/tests/_config/db_tests_config.php';

    public static function config()
    {
        static $config = null;
        if (null === $config) {
            $config = include static::CONFIG_PATH;
            $config['dbname'] = 'evas-tests_auth';
        }
        return $config;
    }

    public static function staticDb(): Database
    {
        static $db = null;
        if (null === $db) {
            codecept_debug('Make staticDb for Auth tests');
            $config = static::config();
            $db = new Database($config);
            $sql = file_get_contents(__DIR__ . '/../../schema.sql');
            $db->batchQuery($sql);

            App::di()->loadDefinitions(
                __DIR__ . '/../_config/di_tests_config.php'
            );
            $_SERVER['REQUEST_URI'] = '/';
            $_SERVER['DOCUMENT_ROOT'] = '/';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $_SERVER['SERVER_NAME'] = 'localhost';
            $_SERVER['SERVER_PORT'] = 80;
            App::request();
        }
        return $db;
    }
}
