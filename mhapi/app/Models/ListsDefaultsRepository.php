<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 9/16/14
 * Time: 11:58 AM
 */
namespace App\Models;

class ListsDefaultsRepository
{

    public function save(User $user)
    {

        return $user->save();
    }

}