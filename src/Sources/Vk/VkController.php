<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Sources\Vk;

use Evas\Auth\Sources\Vk\VkOauth;

/**
 * Контроллер авторизации через vk.com
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class VkController
{
    public function loginAction()
    {
        header('Refresh: 0; url='. VkOauth::getAuthLink()); 
        echo 'vk login<br>';
        // echo VkOauth::getAuthLink();
    }

    public function loggedAction($params)
    {
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>VK Logged</title>
        </head>
        <body>';
        
        echo '<h3>1. vk logged</h3>';
        var_dump($params);
        echo '<h3>2. get access token</h3>';
        $accessData = VkOauth::accessByParams($params);
        var_dump($accessData);
        echo '<h3>2. get user data</h3>';
        $userData = VkOauth::getUserData($accessData['access_token']);
        var_dump($userData);
        echo '
        </body>
        </html>';
        // header('Refresh: 0; url='. App::getUri() . static::AFTER_LOGIN_PATH);
    }
}
