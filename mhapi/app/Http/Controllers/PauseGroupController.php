<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Logger;
use App\Models\PauseGroupEmailModel;
use Faker\Provider\DateTime;

class PauseGroupController extends ApiController
{

    function __construct()
    {

        $this->middleware('auth.basic');
    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        Logger::addProgress('(PauseGroup) Create '.print_r($data, true),
            '(PauseGroup) Create');

        Logger::addProgress('(GroupEmail) Server Info '.print_r($_SERVER, true),
            '(PauseGroup) Server Info');

        $expected_input = [
            'group_email_id',
            'customer_id',
            'pause_customer',
            'paused_by',
        ];

        $missing_fields = array();

        foreach ($expected_input AS $input)
        {
            if (!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if (!empty($missing_fields))
        {
            Logger::addProgress('(GroupEmail) Missing Fields '.print_r($missing_fields, true),
                '(GroupEmail) Missing Fields');
            return $this->respondWithError($missing_fields);
        }

        $Pause = PauseGroupEmailModel::find($data['group_email_id']);

        if (empty($Pause))
        {
            $Pause = new PauseGroupEmailModel();
            $Pause->group_email_id = $data['group_email_id'];
            $Pause->customer_id = $data['customer_id'];
            $Pause->pause_customer = $data['pause_customer'];
            $Pause->paused_by = $data['paused_by'];
            $Pause->created_at = new \DateTime();
            $Pause->updated_at = new \DateTime();
            $Pause->save();
        }

        if ($Pause)
        {
            return $this->respond(['paused']);
        }
        else
        {
            return $this->respondWithError('error');

        }

    }

    public function delete()
    {
        $data = json_decode(file_get_contents('php://input'), true);

               if (empty($data))
               {
                   return $this->respondWithError('No data found, please check your POST data and try again');
               }

               Logger::addProgress('(PauseGroup) Create '.print_r($data, true),
                   '(PauseGroup) Create');

               Logger::addProgress('(GroupEmail) Server Info '.print_r($_SERVER, true),
                   '(PauseGroup) Server Info');

               $expected_input = [
                   'group_email_id',
                   'customer_id',
                   'pause_customer',
                   'paused_by',
               ];

               $missing_fields = array();

               foreach ($expected_input AS $input)
               {
                   if (!isset($data[$input]))
                   {
                       $missing_fields[$input] = 'Input field not found.';
                   }

               }

               if (!empty($missing_fields))
               {
                   Logger::addProgress('(GroupEmail) Missing Fields '.print_r($missing_fields, true),
                       '(GroupEmail) Missing Fields');
                   return $this->respondWithError($missing_fields);
               }

        PauseGroupEmailModel::find($data['group_email_id'])->delete();

        return $this->respond(['deleted']);

    }

}