<?php

/**
 * webuploader的上传部件
 */
namespace vcico\webuploader;
 
use yii\base\Widget;
use yii\helpers\Html;


class Uploader extends Widget{
	
    public $images='';
    public $formname;
    public $serverUrl;
             
    public function init(){
        parent::init();
        if(!$this->formname || !$this->serverUrl){
            throw new \Exception('缺少必要的属性配置');
        }
    }

    public function run(){
        $view = $this->getView();
        UploaderAsset::register($view);//将用到的脚本资源输出到视图
        $static_path = \Yii::$app->assetManager->getPublishedUrl('@app/modules/administer/static');
//        $serverUrl = \yii\helpers\Url::toRoute(['product/upload']);
        return $this->render('webuploader',['static_path'=>$static_path,'url'=>$this->serverUrl,
                                            'csrf' =>\Yii::$app->request->getCsrfToken(),
                                            'fieldname'=>'images' ,'formname'=>$this->formname,
                                            'images'=>$this->images?:'']);
    }
	
	
}