<?php
/**
 * Валидатор сброса пароля.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Validators;

use Evas\Validate\Field;
use Evas\Validate\Fieldset;

class PasswordResetFieldset extends Fieldset
{
    /**
     * Предустанавливаем поле пароля с проверкой повтора пароля.
     * @return array|null предустановленный набор полей
     */
    public function presetFields(): ?array
    {
        $fieldClass = Auth::config()['new_password_field'];
        $props = $fieldClass === Field::class ? [
            'min' => 6, 'max' => 30, 'label' => Auth::config()['new_password_label'],
        ] : [];
        return [
            'password' => new $fieldClass(array_merge($props, [
                'same' => 'password_repeat',
                'sameLabel' => Auth::config()['new_password_repeat_label'],
            ])),
        ];
    }
}
