<?php

namespace App\Http\Controllers;

use App\EmailOne\Transformers\CustomerTransformer;

use App\Http\Requests;
use App\Models\GroupEmailModel;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use DB;
use Illuminate\Http\Response;

/**
 * Class CustomerController
 * @package App\Http\Controllers
 */
class XMLController extends ApiController
{

    /**
     * CustomerController constructor.
     * @param CustomerTransformer $customerTransformer
     */
    function __construct(CustomerTransformer $customerTransformer)
    {

        $this->customerTransformer = $customerTransformer;

    }

    public function get100GroupForXML()
    {

        $sql
            = 'SELECT e.group_email_id,  e.e.group_email_id,  e.from_email, e.subject, e.body, count(e.email_id) as count, e.subject, e.body, count(e.email_id) as count FROM mw_group_email AS e GROUP BY e.group_email_id ORDER BY e.group_email_id DESC LIMIT 20';

        $data
            = GroupEmailModel::select(DB::raw('group_email_id, from_email, subject, body, count(email_id) as count, subject, body, count(email_id) as count'))
            ->limit(20)
            ->groupBy('group_email_id')
            ->orderBy('group_email_id', 'DESC')
            ->get()
            ->toArray();

//        dd($data);

        $xml = '<?xml version="1.0" ?><root>';
        $i = 0;
        foreach($data AS $row)
        {

            foreach($row AS $key=>$value)
            {
                $xml .= '<item'.$i.'>';
                if($key == 'body' || $key == 'from_email')
                {
                    $value = '<![CDATA['.$value.']]>';
                }
                            $xml .= '<'.$key.'>'.$value.'</'.$key.'>';
                            $xml .= '</item'.$i.'>';
                $i++;

            }

        }

        $xml .= '</root>';
        header("Content-type: text/xml");

        print $xml;
    }


function array2XML($obj, $array)
   {
       foreach ($array as $key => $value)
       {
           if(is_numeric($key))
               $key = 'item' . $key;

           if (is_array($value))
           {
               $node = $obj->addChild($key);
               $this->array2XML($node, $value);
           }
           else
           {
               $obj->addChild($key, htmlspecialchars($value));
           }
       }
   }

}
