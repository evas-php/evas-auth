<?php
/**
 * Интерфейс внешней аутентификации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Interfaces;

interface OauthInterface
{
    /**
     * Выполнение входа.
     * @param array данные запроса
     * @return self
     */
    public function resolveLogin(array $payload);

    /**
     * Получение данных доступа.
     * @return array
     */
    public function getAccessData(): ?array;

    /**
     * Получение запрошенных данных о пользователе.
     * @return array|null
     */
    public function getUserData(): ?array;

    /**
     * Получение обработанных данных пользователя.
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * Получение ключа пользователя в источнике.
     * @return string|null
     */
    public function getSourceKey(): ?string;

    /**
     * Получение oauth ссылки на аутентификацию.
     * @return string ссылка для перехода на аутентификацию
     */
    public function getAuthLink(): string;

    /**
     * Запрос oauth на доступ.
     * @param string код доступа
     * @return array данные доступа
     * @throws AuthException
     */
    public function fetchAccess(string $code): array;

    /**
     * Запрос данных пользователя по данным доступа.
     * @param array данные доступа
     * @return array данные пользователя
     */
    public function fetchUserDataByAccess(array $accessData): array;
}
