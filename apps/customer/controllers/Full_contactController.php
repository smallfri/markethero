<?php defined('MW_PATH')||exit('No direct script access allowed');

class Full_contactController extends Controller
{

    public function init()
    {
//        parent::init();

    }

    /**
     * Define the filters for various controller actions
     * Merge the filters with the ones from parent implementation
     */
    public function getCurl($url)
    {

        $url .= '&apiKey=2d1998d507fffa2d';
        $params = [];
        $output = Yii::app()->curl->get($url, $params);

        return json_decode($output);
    }

    public function actionIndex()
    {

        echo "here";
    }

    public function actionCreate($list_uid, $sub_uid)
    {

        $list = $this->loadListModel($list_uid);

        $subscriber = $this->loadSubscriberModel($list->list_id, $sub_uid);

        //call full contact person json url
        $url = 'https://api.fullcontact.com/v2/person.json?email='.$subscriber->email;
        $output = $this->getCurl($url);
//        print_r($output);

        if($output->status==200)
        {
            $values = [];
            $output = (array)$output;
            foreach($output as $key => $value)
            {

                if(!is_array($value)&&!is_object($value))
                {
                    $values[$key] = $value;
                }
                else
                {
                    $level1 = (array)$value;

                    foreach($level1 as $key1 => $value1)
                    {
                        if(!is_array($value1)&&!is_object($value1))
                        {
                            $values[$key.'-'.$key1] = $value1;
                        }
                        else
                        {
                            $level2 = (array)$value1;

                            foreach($level2 as $key3 => $value3)
                            {
                                if(!is_array($value3)&&!is_object($value3))
                                {
                                    $values[$key.'-'.$key1.'-'.$key3] = $value3;
                                }
                                else
                                {
                                    $level4 = (array)$value3;

                                    foreach($level4 as $key4 => $value4)
                                    {
                                        if(!is_array($value4)&&!is_object($value4))
                                        {
                                            $values[$key.'-'.$key1.'-'.$key3.'-'.$key4] = $value4;
                                        }
                                        else
                                        {
                                            $level5 = (array)$value4;

                                            foreach($level5 as $key5 => $value6)
                                            {
                                                if(!is_array($value6)&&!is_object($value6))
                                                {
                                                    $values[$key.'-'.$key1.'-'.$key3.'-'.$key4.'-'.$key5] = $value6;
                                                }
                                                else
                                                {

                                                }
                                            }
                                        }
                                    }

                                }
                            }

                        }
                    }

                }

            }

            //add any new keys to the db
            foreach($values as $key => $value)
            {
                if($key!=='status'||strtolower($key)!='requestid')
                {


                    $count = null;
//                    $key = preg_replace('/(-[0-9]-)/', '_', $key, -1, $count);
                    $key = str_replace('-', '_', $key);


                    //check for existing tag
                    $model = ListField::model()->findByAttributes(array(
                        'list_id' => $list->list_id,
                        'tag' => strtoupper($key)
                    ));

                    if(empty($model))
                    {

                        //create tag for this list id
                        $model = new ListField();
                        $model->type_id = 1;
                        $model->list_id = $list->list_id;
                        $model->label = ucfirst($key);
                        $model->tag = strtoupper($key);
                        $model->sort_order = 99;
                        $model->save();

                    }
                    $listfieldmodel = ListFieldValue::model()->findByAttributes(array(
                        'field_id' => $model->field_id,
                        'subscriber_id' => $subscriber->subscriber_id
                    ));

                    if(empty($listfieldmodel))
                    {
                        $valueModel = new ListFieldValue();
                        $valueModel->field_id = $model->field_id;
                        $valueModel->subscriber_id = $subscriber->subscriber_id;
                        $valueModel->value = substr($value, 0, 255);
                        $valueModel->save();
                    }
                    else
                    {
                        $listfieldmodel->value = substr($value, 0, 255);
                        $listfieldmodel->save();
                    }

                }

            }
        }
        return;
    }

    /**
     * Helper method to load the list AR model
     */
    public
    function loadListModel($list_uid)
    {

        $model = Lists::model()->findByAttributes(array(
            'list_uid' => $list_uid,
        ));

        if($model===null)
        {
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist1.'));
        }

        return $model;
    }

    /**
     * Helper method to load the list subscriber AR model
     */
    public
    function loadSubscriberModel($list_id, $subscriber_uid)
    {

        $model = ListSubscriber::model()->findByAttributes(array(
            'subscriber_uid' => $subscriber_uid,
            'list_id' => (int)$list_id,
        ));

        if($model===null)
        {
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist2.'));
        }

        return $model;
    }

    /**
     * Helper method to load the list subscriber AR model
     */
    public
    function loadListSubscriberModel($field_id, $subscriber_id)
    {

        $model = ListFieldValue::model()->findByAttributes(array(
            'subscriber_id' => $subscriber_id,
            'field_id' => (int)$field_id,
        ));

        if($model===null)
        {
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist2.'));
        }

        return $model;
    }
}
