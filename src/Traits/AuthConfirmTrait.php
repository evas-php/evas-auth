<?php
/**
 * Трейт подтверждений.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Traits;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;
use Evas\Auth\Models\AuthConfirm;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Models\AuthRecovery;
use Evas\Auth\Validators\PasswordResetFieldset;

trait AuthConfirmTrait
{
    /**
     * Создаение подтверждения с получение кода.
     * @param array|null данные запроса
     * @param bool нужно ли сохранить как подтверждение восстановления
     * @return string код подтверждения
     * @throws AuthException
     */
    protected function getConfirmCode(array $payload = null, bool $recovery = false): string
    {
        // 1. Валидируем payload с email/телефоном (?)
        list($data, $to, $type) = static::validateGetCodeFieldset($payload);

        // 2. Ищем пользователя с таким email/телефоном
        $user = $this->userModel()::findByUniqueKeysFilled($to, $type);
        if (!$user) {
            // - ошибка, если пользователь не найден
            throw AuthException::build('user_not_found');
        }
        // 3. Создаём AuthConfirm
        $confirmClass = $recovery ? AuthRecovery::class : AuthConfirm::class;
        $confirm = $confirmClass::make($user->id, $to, $type);
        // 4. Возвращаем код подтверждения
        return (string) $confirm->code;
    }

    /**
     * Проверка подтверждения.
     * @param array|null данные запроса
     * @param bool нужно ли искать как подтверждение восстановления
     * @return AuthConfirm|AuthRecovery
     * @throws AuthException
     */
    protected function confirmCheck(array $payload = null, bool $recovery = false): AuthConfirm
    {
        // 1. Валидируем payload с кодом + email/телефон
        list($data, $to, $type, $code) = static::validateCheckCodeFieldset($payload);

        // 2. Ищем пользователя с таким email/телефоном
        $user = $this->userModel()::findByUniqueKeysFilled($to, $type);
        if (!$user) {
            // - ошибка, если пользователь не найден
            throw AuthException::build('user_not_found');
        }
        // 3. Ищем AuthConfirm с таким кодом для этого пользователя
        $confirmClass = $recovery ? AuthRecovery::class : AuthConfirm::class;
        $confirm = $confirmClass::findByUserIdAndCode($user->id, $code);
        if (!$confirm) {
            // - ошибка, если AuthConfirm не найден
            throw AuthException::build('code_is_not_active');
        }
        // 4. Подтверждаем AuthConfirm
        $confirm->complete();
        return $confirm;
    }

    /**
     * Создаение подтверждения восстановления с получение кода.
     * @param array|null данные запроса
     * @return string код подтверждения восстановления
     * @throws AuthException
     */
    protected function getRecoveryCode(array $payload = null): string
    {
        return static::getConfirmCode($payload, true);
    }

    /**
     * Проверка подтверждения и восстановление.
     * @param array|null данные запроса
     * @return AuthGrant
     * @throws AuthException
     */
    protected function recoveryCheck(array $payload = null): AuthGrant
    {
        // 0. Проверяем подтверждение восстановление
        $confirm = static::confirmCheck($payload, true);
        $user_id = $confirm->user_id;

        // 1. Валидируем пароль
        $fieldset = new PasswordResetFieldset;
        $fieldset->throwIfNotValid($payload);
        $password = $fieldset->values['password'];

        // 2. Обновляем пароль
        $grant = AuthGrant::findWithPasswordByUserId($user_id);
        if ($grant) {
            $grant->setPasswordHash($password);
        } else {
            $grant = AuthGrant::makeWithPassword($user_id, $password);
        }
        // 3. Возвращаем AuthGrant пароля
        return $grant;
    }


    // Получение для переотправки.

    /**
     * Получение кода подтверждения для переотправки.
     * @param array|null данные запроса
     * @param bool нужно ли искать как подтверждение восстановления
     * @return string код подтверждения
     */
    protected function getCodeForResend(array $payload = null, bool $recovery = false): string
    {
        // 0. Проверяем поддержку входа по коду
        $this->throwIfNotSupportedSource('code');
        // 1. Валидируем payload с email/телефоном (?)
        list($data, $to, $type) = static::validateGetCodeFieldset($payload);

        // 2. Ищем пользователя с таким email/телефоном
        $userModel = $this->userModel();
        $user = $userModel::findByUniqueKeysFilled($to, $type);
        if (!$user) {
            // - если пользователь не найден, создаём пользователя
            $user = $userModel::insert($data);
        }
        // 3. Ищем AuthConfirm
        $confirmClass = $recovery ? AuthRecovery::class : AuthConfirm::class;
        $confirm = $confirmClass::findByUserIdAndTo($user->id, $to);
        if (!$confirm) {
            // - создаем AuthConfirm, если не найден
            $confirm = $confirmClass::make($user->id, $to, $type);
        }
        if ($confirm->isCompleted()) {
            // - ошибка, если уже подтверждено
            throw AuthException::build('code_is_not_active');
        }
        // 4. Возвращаем код подтверждения
        return (string) $confirm->code;
    }

    /**
     * Получение кода подтверждения восстановления для переотправки.
     * @param array|null данные запроса
     * @return string код подтверждения
     */
    protected function getRecoveryCodeForResend(array $payload = null): string
    {
        return $this->getCodeForResend($payload, true);
    }
}
