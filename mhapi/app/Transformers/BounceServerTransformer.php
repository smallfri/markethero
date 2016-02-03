<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/27/16
 * Time: 10:43 AM
 */

namespace App\Transformers;


class BounceServerTransformer extends Transformer
{
    public function transform($bounce_server)
          {

              return ['bounce_servers' => $bounce_server];

          }

}