<?php
/**
 * Трейт аутентификации через внешний ресурс.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Traits;

use Evas\Auth\Auth;
use Evas\Auth\Models\AuthGrant;

trait AuthForeignTrait
{
    /**
     * Получение ссылки для внешней аутентификации.
     * @param string источник
     * @return string ссылка
     * @throws AuthException
     */
    protected function getForeignLoginLink(string $source): string
    {
        return $this->getOauth($source)->getAuthLink();
    }

    /**
     * Аутентификация через внешний источник.
     * @param string источник
     * @param array параметры запроса
     * @return int id пользователя
     * @throws AuthException
     */
    protected function loginByForeign(string $source, array $payload): int
    {
        // 0. Проверяем поддержку входа через эту соц. сеть
        $oauth = $this->getOauth($source);
        // 1. По полученному коду запрашиваем $accessData, $userData
        $oauth->resolveLogin($payload);
        $sourceKey = $oauth->getSourceKey();
        // 2. Проверяем есть ли AuthGrant для такого $source + $sourceKey
        $grant = AuthGrant::findForeign($source, $sourceKey);
        if (!$grant) {
            // - если нет, то 1. добавляем пользователя $user с данными $data
            $data = $oauth->getData();
            $user = $this->userModel()::insertByForeign($source, $data);
            // - если нет, то 2. добавляем грант аутентификации для $user->id + $source + $sourceKey
            $grant = AuthGrant::makeForeign($user->id, $source, $sourceKey);
        }
        // 3. Создаём или обновляем AuthSession пользователя
        $session = $this->makeSession($grant, $oauth->getAccessToken());
        // 4. Возвращаем id пользователя
        return $grant->user_id;
    }
}
