<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/12/15
 * Time: 3:21 PM
 */

namespace App\Transformers;


abstract class Transformer
{


    public function transformCollection(array $items)
    {

        return array_map([$this,'transform'],$items);
    }

    public abstract function transform($item);
}