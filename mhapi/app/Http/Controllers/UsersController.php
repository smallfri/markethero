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
//        echo "user created";
//
//        $user = User::find(7);
//
//        $user->password = bcrypt('KjV9g2JcyFGAHng');
//
//        $user->save();
//
//        echo "updated"; exit;


//
        return User::create([
            'user_uid' => uniqid(),
            'first_name' => 'DEV',
            'email' => 'noreply@markethero.io',
            'password' => bcrypt('KjV9g2JcyFGAHng'),
        ]);
    }
}