<?php
/**
 * Авторизация по паролю.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Actions;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;
use Evas\Auth\Interfaces\LoginUserInterface;
use Evas\Auth\Models\AuthGrant;

class PasswordAuth
{
    /**
     * Регистрация по паролю.
     * @param array|null параметры запроса
     * @return LoginUserInterface
     * @throws AuthException
     */
    public static function registration(array $payload = null): LoginUserInterface
    {
        // 0. Проверяем поддержку входа по паролю
        Auth::throwIfNotSupportedSource('password');
        $userModel = Auth::userModel();
        // 1. Валидируем данные (?)
        $data = $userModel::validateRegistration($payload); // ?
        // 2. Ищем пользователя по уникальным данным (email, телефон, логин)
        $user = $userModel::findByUniqueKeys($data);
        if ($user) foreach ($userModel::uniqueKeys() as &$key) {
            if (isset($data[$key]) && $user->$key === $data[$key]) {
                $label = $userModel::getUniqueKeyLabel($key);
                // - ошибка, если пользователь найден, говорим, что занято
                throw AuthException::build('user_already_exists', $label);
            }
        }
        // 3. Создаём пользователя по паролю, передаём данные
        $user = $userModel::insertByPassword($data);
        // 4. Создаём AuthGrant с паролем
        AuthGrant::createWithPassword($user->id, $data['password']);
        // 5. Возвращаем пользователя
        return $user;
    }

    /**
     * Авторизаци по паролю.
     * @param array|null параметры запроса
     * @return LoginUserInterface
     * @throws AuthException
     */
    public static function login(array $payload = null): LoginUserInterface
    {
        // 0. Проверяем поддержку входа по паролю
        Auth::throwIfNotSupportedSource('password');
        $userModel = Auth::userModel();
        // 1. Валидируем данные (?)
        $data = $userModel::validateLogin($payload); // ?
        // 2. Ищем пользователя по логину в email, телефоне или логине
        $keys = array_fill_keys($userModel::uniqueKeys(), $data['login']);
        $user = $userModel::findByUniqueKeys($keys);
        if (!$user) {
            // - ошибка, если пользователь не найден
            throw AuthException::build('user_not_found');
        }
        // 3. Ищем AuthGrant с паролем
        $grant = AuthGrant::findWithPasswordByUserId($user->id);
        if (!$grant) {
            // - ошибка, если AuthGrant не найден
            throw AuthException::build('password_grant_not_found');
        }
        // 4. Проверяем пароль
        if (!$grant->checkPassword($data['password'])) {
            // - ошибка, если пароль не подходит
            throw AuthException::build('user_fail_password');
        }
        // 5. Создаём или обновляем AuthSession пользователя
        $session = Auth::makeSession($grant);
        // 6. Возвращаем пользователя
        return $user;
    }

    /**
     * Смена пароля пользователя.
     * @param int id пользователя
     * @param string старый пароль
     * @param string новый пароль
     * @throws AuthException
     */
    public static function changePassword(int $user_id, string $password_old, string $password)
    {
        $grant = AuthGrant::findWithPasswordByUserId($user_id);
        if (!$grant) throw AuthException::build('password_grant_not_found');
        $grant->changePassword($password_old, $password);
    }

    /**
     * Установка пароля пользователя.
     * @param int id пользователя
     * @param string пароль
     * @throws AuthException
     */
    public static function setPassword(int $user_id, string $password)
    {
        AuthGrant::createWithPassword($user_id, $password);
    }
}
