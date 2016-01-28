<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/27/16
 * Time: 7:35 AM
 */

namespace App\Http\Controllers;

use App\User;

class UsersController extends ApiController
{
    function __construct()
       {

       }

    public function store()
    {

        return User::create([
            'first_name' => 'russell',
            'email' => 'russell@yahoo.com',
            'password' => bcrypt('jack1999'),
        ]);
    }
}