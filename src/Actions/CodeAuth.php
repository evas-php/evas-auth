<?php
/**
 * Авторизация по коду.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Actions;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;

class CodeAuth
{
    /**
     * Начало авторизации по отправленному на телефон/email коду.
     * @param string тип авторизации
     * @param array|null данные запроса
     * @return string код
     */
    public static function init(string $type, array $payload = null): string
    {
        // 0. Проверяем поддержку входа по коду
        Auth::throwIfNotSupportedSource('code');
        $userModel = Auth::userModel();
        // 1. Валидируем payload с email/телефоном (?)
        $data = $userModel::validateEmailOrPhone($payload);
        // 2. Ищем пользователя с таким email/телефоном
        $keys = array_fill_keys($userModel::uniqueKeys(), $data['login']);
        $user = $userModel::findByUniqueKeys($keys);
        if (!$user) {
            // - если пользователь не найден, создаём пользователя
            $user = $userModel::insertByCode($data);
        }
        // $grant = AuthGrant::createWithCode($user->id);
        // 3. Создаём AuthConfirm со сгенерированным кодом для пользователя
        $confirm = AuthConfirm::create(['user_id' => $user->id, 'to' => $to]);
        // 4. Возвращаем код подтверждения
        return (string) $confirm->code;
    }

    /**
     * Авторизаци по отправленному на телефон/email коду.
     * @param string тип
     * @param array|null данные запроса
     * @return LoginUserInterface|null
     */
    protected function codeAuth(string $type, array $payload = null): ?LoginUserInterface
    {
        // 0. Проверяем поддержку входа по коду
        Auth::throwIfNotSupportedSource('code');
        $userModel = Auth::userModel();
        // 1. Валидируем payload с кодом + email/телефон
        $data = $userModel::validateCode($payload);
        // 2. Ищем пользователя с таким email/телефоном
        $keys = array_fill_keys($userModel::uniqueKeys(), $data['login']);
        $user = $userModel::findByUniqueKeys($keys);
        if (!$user) {
            // - ошибка, если пользователь не найден
            throw AuthException::build('user_not_found');
        }
        // 3. Ищем AuthConfirm с таким кодом для этого пользователя
        // - ошибка, если AuthConfirm не найден
        // 4. Подтверждаем AuthConfirm
        if (!AuthConfirm::completeConfirm($data['type'], $user->id, $data['code'])) {
            return null;
        }
        // 5. Создаём или обновляем AuthSession пользователя
        $session = Auth::makeSession($grant);
        // 6. Возвращаем пользователя
        return $user;
    }
}
