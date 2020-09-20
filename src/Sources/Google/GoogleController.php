<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Sources\Google;

use Evas\Auth\Sources\Google\GoogleOauth;

/**
 * Контроллер авторизации через vk.com
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class GoogleController
{
    public function loginAction()
    {
        header('Refresh: 1; url='. GoogleOauth::getAuthLink()); 
        echo 'google login<br>';
        echo GoogleOauth::getAuthLink();
    }

    public function loggedAction(array $params = null)
    {
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Google Logged</title>
        </head>
        <body>';
        
        echo '<h3>1. google logged</h3>';
        var_dump($params);
        echo '<h3>2. get access token</h3>';
        $accessData = GoogleOauth::accessByParams($params);
        var_dump($accessData);
        echo '<h3>3. get user data</h3>';
        $userData = GoogleOauth::getUserData($accessData->accessToken, $accessData);
        var_dump($userData);
        echo '
        </body>
        </html>';
        // header('Refresh: 0; url='. App::getUri() . static::AFTER_LOGIN_PATH);
    }
}