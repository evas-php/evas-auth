<?php
namespace Evas\Auth\Sources\Email;

class EmailController
{
    public function loginAction(array $params = null)
    {
        // валидация данных
        $email = $params['email'];
        $password = $params['password'];
        // 
    }

    public function confirmSendAction(array $params = null)
    {
        // 
    }

    public function confirmCheckAction(array $params = null)
    {
        // 
    }

    public function recoverySendAction(array $params = null)
    {
        // 
    }

    public function resetAction(array $params = null)
    {
        // 
    }
}
