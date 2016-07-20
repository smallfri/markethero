<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 9/16/14
 * Time: 11:58 AM
 */
namespace App\Models;

class ZoneRepository
{

    public function save(Lists $lists)
    {

        return $lists->save();
    }

}