<?php
/**
 * Oauth api аутентификации через google.com
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 * @link google oauth doc https://developers.google.com/identity/protocols/oauth2/web-server
 */
namespace Evas\Auth\Sources;

use Evas\Auth\AuthException;
use Evas\Auth\Help\BaseOauth;

/**
 */
class GoogleOauth extends BaseOauth
{
    /** @static string имя источника аутентификации */
    const SOURCE = 'google';
    /** @static string полное имя источника аутентификации */
    const SOURCE_NAME = 'google.com';

    /** @static string домен oauth */
    const OAUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    /** @static string uri получения токена доступа */
    const ACCESS_URL = 'https://oauth2.googleapis.com/token';
    /** @static string домен api */
    const API_URL = 'https://oauth2.googleapis.com/';

    /** @static array маппинг замены ключей пользовательских данных */
    const USER_DATA_KEYS_REPLACES = [
        'given_name' => 'first_name',
        'family_name' => 'last_name',
    ];

    /** 
     * Получение данных конфига по умолчанию.
     * @return array|null
     */
    public function configDefault(): ?array
    {
        return [
            'response_type' => 'code',
            'scope' => 'profile email openid',
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * Получение oauth ссылки на аутентификацию.
     * @return string ссылка для перехода на аутентификацию
     */
    public function getAuthLink(): string
    {
        return static::buildLink(static::OAUTH_URL, [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => $this->config['scope'],
            'response_type' => $this->config['response_type'],
            'access_type' => $this->config['access_type'],
            'include_granted_scopes' => $this->config['include_granted_scopes'],
        ]);
    }

    /**
     * Запрос oauth на доступ.
     * @param string код доступа
     * @return array данные доступа
     * @throws AuthException
     */
    public function fetchAccess(string $code): array
    {
        $uri = static::buildLink(static::ACCESS_URL, [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => $this->config['grant_type'],
            'code' => $code,
        ]);
        $response = static::execCurl('POST', $uri);
        if (empty($response)) {
            throw AuthException::build('oauth_empty_access', static::SOURCE_NAME);
        }
        try {
            // $data = json_decode($response, true);
            $data = $response->getParsedBody();
        } catch (\Exception $e) {
            throw AuthException::build('oauth_error_response', static::SOURCE_NAME, 
                // 'Can\'t decoded Google API response: ' . $response
                $e->getMessage()
            );
        }
        if (empty($data)) {
            throw AuthException::build('oauth_empty_access', static::SOURCE_NAME);
        }
        return (array) $data;
    }

    /**
     * Запрос данных пользователя по данным доступа.
     * @param array данные доступа
     * @return array данные пользователя
     */
    public function fetchUserDataByAccess(array $accessData): array
    {
        extract($accessData);
        $data = static::queryApi('tokeninfo', compact(
            'access_token', 'id_token', 'token_type', 'expires_in'
        ));
        if (empty($data)) {
            throw AuthException::build('oauth_empty_user_data', static::SOURCE_NAME);
        }
        // static::renameDataKeys($data);
        return (array) $data;
        // return
        // // These six fields are included in all Google ID Tokens.
        // "iss": "https://accounts.google.com",
        // "sub": "110169484474386276334",
        // "azp": "1008719970978-hb24n2dstb40o45d4feuo2ukqmcc6381.apps.googleusercontent.com",
        // "aud": "1008719970978-hb24n2dstb40o45d4feuo2ukqmcc6381.apps.googleusercontent.com",
        // "iat": "1433978353",
        // "exp": "1433981953",

        // // These seven fields are only included when the user has granted the "profile" and
        // // "email" OAuth scopes to the application.
        // "email": "testuser@gmail.com",
        // "email_verified": "true",
        // "name" : "Test User",
        // "picture": "https://lh4.googleusercontent.com/-kYgzyAWpZzJ/ABCDEFGHI/AAAJKLMNOP/tIXL9Ir44LE/s99-c/photo.jpg",
        // "given_name": "Test",
        // "family_name": "User",
        // "locale": "en"
    }


    /**
     * Получение ключа пользователя в источнике.
     * @return string|null
     */
    public function getSourceKey(): ?string
    {
        return $this->userData['email'];
    }

    /**
     * Подготовка пользовательских данных.
     * @return array
     */
    protected function prepareData(): array
    {
        $data = $this->getUserData();
        static::renameDataKeys($data);
        return $data;
    }
}
