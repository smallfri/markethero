<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 9/16/14
 * Time: 11:58 AM
 */
namespace App;

class GroupEmailModelRepository
{

    public function save(GroupEmailModel $groupEmailModel)
    {

        return $groupEmailModel->save();
    }

}