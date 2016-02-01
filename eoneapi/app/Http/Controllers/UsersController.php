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

    public function index()
    {
        echo "index";
    }



    public function store()
    {
        echo "user created";

        $user = User::find(3);

        $user->password = bcrypt('jack1999');

        $user->save();

        echo "updated"; exit;

        return User::create([
            'first_name' => 'russell',
            'email' => 'russell@smallfri.com',
            'password' => bcrypt('jack1999'),
        ]);
    }
}