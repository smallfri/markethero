<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 9/16/14
 * Time: 11:58 AM
 */
namespace App\Models;

class SegmentConditionRepository
{

    public function save(SegmentCondition $segmentcondition)
    {

        return $segmentcondition->save();
    }

}