<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Sources\Fb;

use Evas\Auth\AuthException;
use Evas\Auth\Sources\Fb\FbOauth;

/**
 * Контроллер авторизации через facebook.com
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class FbController
{
    /**
     * @deprecated перенесено в Evas\Auth\Helpers\BaseApi
     */
    // const ERROR_OAUTH_CODE_EMPTY = 'Код доступа Facebook не получен';
    // const ERROR_OAUTH_STATE_EMPTY = 'State доступа Facebook не получен';

    public function loginAction()
    {
        header('Refresh: 0; url='. FbOauth::getAuthLink()); 
        echo 'fb login<br>';
        // echo FbOauth::getAuthLink();
    }

    public function loggedAction($params)
    {
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>FB Logged</title>
        </head>
        <body>';
        
        echo '<h3>1. fb logged</h3>';
        var_dump($params);
        echo '<h3>2. get access token</h3>';
        $accessData = FbOauth::accessByParams($params);
        var_dump($accessData);
        echo '<h3>2. get user data</h3>';
        $userData = FbOauth::getUserData($accessData['access_token']);
        var_dump($userData);
        echo '
        </body>
        </html>';
        // header('Refresh: 0; url='. App::getUri() . static::AFTER_LOGIN_PATH);
    }
}
