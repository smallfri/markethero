<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 9:01 AM
 */

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Models\SegmentCondition;

class SegmentController extends ApiController
{

    public $endpoint;

    function __construct()
    {

        $this->middleware('auth.basic');

    }

    public function store()
    {


        $data = json_decode(file_get_contents('php://input'), true);

        if(empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'list_id',
            'name',
            'operator_match',
            'operator_id',
            'field_id',
            'value'
        ];

        $missing_fields = array();

        foreach($expected_input AS $input)
        {
            if(!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if(!empty($missing_fields))
        {
            return $this->respondWithError($missing_fields);
        }

        $Segment = new Segment();

        $Segment->segment_uid = uniqid();

        $Segment->list_id = $data['list_id'];

        $Segment->name = $data['name'];

        $Segment->operator_match = $data['operator_match'];

        $Segment->save();

        $SegmentCondition = new SegmentCondition();

        $SegmentCondition->segment_id = $Segment->segment_id;

        $SegmentCondition->operator_id = $data['operator_id'];

        $SegmentCondition->field_id = $data['field_id'];

        $SegmentCondition->value = $data['value'];

        $SegmentCondition->save();

        if($SegmentCondition->condition_id<1 OR $Segment->segment_id<1)
        {
            return $this->respondWithError('There was an error, the segment was not created.');
        }

        $segment = Segment::find($Segment->segment_id);


        return $this->respond(['segment_uid' => $segment->segment_uid, 'segment_id' => $segment->segment_id]);
    }

    public function index($customer_id, $page, $perPage)
    {


        return $this->respond(['lists' => $Lists, 'next' => $nextUrl, 'previous' => $previousUrl]);

    }

    public function save($list_uid)
    {

        $data = json_decode(file_get_contents('php://input'), true);


        return $this->respond(['success' => 'list updated.']);
    }

    public function destroy($list_uid)
    {


        return $this->respondWithError('list '.$list_uid.' not found.');

    }

    public function show($email)
    {


        return $this->respond(['lists' => $Lists]);

    }

}

//curl -u russell@smallfri.com:KjV9g2JcyFGAHng -i -X POST -H "Content-Type:application/json" http://m-staging.markethero.io/mhapi/v1/customer -d '{"customer": {"first_name":"sample name","last_name":"sample last name","email":"email@domain.com","confirm_email":"email@domain.com","confirm_password":"password","fake_password":"password","group_id":1}}'