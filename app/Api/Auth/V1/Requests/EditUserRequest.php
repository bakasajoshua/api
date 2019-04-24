<?php

namespace App\Api\Auth\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;

class EditUserRequest extends FormRequest
{
    public function rules()
    {
        // return Config::get('boilerplate.reset_password.validation_rules');

        return [
        	'email' => 'email',
        	'password' => 'required|confirmed'
        ];
    }

    public function authorize()
    {
        return true;
    }
}
