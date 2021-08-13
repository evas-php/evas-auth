<?php
/**
 * Авторизация через внешний источник.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Actions;

use Evas\Auth\Auth;
use Evas\Auth\Models\AuthGrant;

class ForeignAuth
{
    /**
     * Получение ссылки для внешней авторизации.
     * @param string источник
     * @return string ссылка
     * @throws AuthException
     */
    public static function getLink(string $source): string
    {
        return Auth::getOauth($source)->getAuthLink();
    }

    /**
     * Авторизация через внешний источник.
     * @param string источник
     * @param array параметры запроса
     * @return int id пользователя
     * @throws AuthException
     */
    public static function login(string $source, array $payload): int
    {
        // 0. Проверяем поддержку входа через эту соц. сеть
        $oauth = Auth::getOauth($source);
        // 1. По полученному коду запрашиваем $accessData, $userData
        $oauth->resolveLogin($payload);
        $sourceKey = $oauth->getSourceKey();
        // 2. Проверяем есть ли AuthGrant для такого $source + $sourceKey
        $grant = AuthGrant::findForeign($source, $sourceKey);
        if (!$grant) {
            // - если нет, то 1. добавляем пользователя $user с данными $data
            $data = $oauth->getData();
            $user = Auth::userModel()::insertByForeign($source, $data);
            // - если нет, то 2. добавляем грант авторизации для $user->id + $source + $sourceKey
            $grant = AuthGrant::makeForeign($user->id, $source, $sourceKey);
        }
        // 3. Создаём или обновляем AuthSession пользователя
        $session = Auth::makeSession($grant, $oauth->getAccessToken());
        // 4. Возвращаем id пользователя
        return $grant->user_id;
    }
}
