<?php namespace App\Forms;

//use Laracasts\Validation\FormValidator;

class SignInForm
{
    protected $rules = ['email' => 'required','password' => 'required'];
}