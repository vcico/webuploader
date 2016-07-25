<?php

namespace app\modules\administer\components;
 
use yii\base\Widget;
use yii\helpers\Html;
use app\modules\administer\assets\UploaderAsset;

class Uploader extends Widget{
	
    public $images='';
    public $formname;
    public function init(){
        parent::init();
    }

    public function run(){
        $view = $this->getView();
        UploaderAsset::register($view);//将用到的脚本资源输出到视图
        $static_path = \Yii::$app->assetManager->getPublishedUrl('@app/modules/administer/static');
        $url = \yii\helpers\Url::toRoute(['product/upload']);
        return $this->render('webuploader',['static_path'=>$static_path,'url'=>$url,
                                            'csrf' =>\Yii::$app->request->getCsrfToken(),
                                            'fieldname'=>'images' ,'formname'=>$this->formname,
                                            'images'=>$this->images?:'']);
    }
	
	
}