<?php
/**
 * Обёртка тестов моделя evas-auth.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\tests\help;

use Codeception\Util\Autoload;
use Evas\Web\WebApp as App;
use Evas\Db\Interfaces\DatabaseInterface;
use Evas\Auth\tests\help\GlobalDb;

Autoload::addNamespace('Evas\\Db', 'vendor/evas-php/evas-db/src');
Autoload::addNamespace('Evas\\Auth', 'vendor/evas-php/evas-auth/src');

class AuthTestUnit extends \Codeception\Test\Unit
{
    protected function db(): DatabaseInterface
    {
        return GlobalDb::staticDb();
    }

    protected function _before()
    {
        static::db();
    }

    protected function _after()
    {
        // очищаем состояния IdentityMap
        App::db()->identityMapClear();
    }
}
