<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 9:01 AM
 */

namespace App\Models\Http\Controllers;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Field;
use App\Models\Lists;
use App\Models\ListsCompany;
use App\Models\ListsDefaults;
use App\Models\ListsCustomerNotification;
use App\Models\Segment;
use App\Models\SegmentCondition;
use App\Models\Zone;
use Illuminate\Support\Facades\URL;

class FieldsController extends ApiController
{

    public $endpoint;

    function __construct()
    {

        $this->middleware('auth.basic');

    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $expected_input = [
            'type_id',
            'list_id',
            'label',
            'tag',
            'default_value',
            'help_text',
            'required',
            'visibility',
            'sort_order'
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


        $data = json_decode(file_get_contents('php://input'), true);

        $Fields = new Field();

        $Fields->type_id = $data['type_id'];

        $Fields->list_id = $data['list_id'];

        $Fields->label = $data['label'];

        $Fields->tag = $data['tag'];

        $Fields->default_value = $data['default_value'];

        $Fields->help_text = $data['help_text'];

        $Fields->required = $data['required'];

        $Fields->visibility = $data['visibility'];

        $Fields->sort_order = $data['sort_order'];;

        $Fields->save();

        if($Fields->field_id<1)
        {
            return $this->respondWithError('There was an error, the field was not created.');
        }

        return $this->respond(['field_id' => $Fields->field_id]);
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