<?php

namespace vcico\webuploader;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * 上传行为 ： 检查数据前数组转换为字符串、上传后清除缓存、修改时对比 新增添加 删除则清除文件等 
 * @todo  修改 多字段
 * @author cgjcgs
 */
class UploaderBehavior extends Behavior{
	
	public $fields; // 多字段
    
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
//            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
        ];
    }
    
    public function afterUpdate($event){
        print_r($event->sender->oldAttributes);
//        $diff = array_diff($a,$b); // 删除的
//        $diff = array_diff($b,$a); // 新增的
    }

    public function afterInsert($event){
        if($event->sender->images){
            $array = \Yii::$app->cache->get(\Yii::t('cacheKey','webuploader_expired_upload'));
            $imgs = explode(',', $event->sender->images);
            foreach($imgs as $key => $img){
                if(isset($array[$img])) unset($array[$img]);
            }
            \Yii::$app->cache->set(\Yii::t('cacheKey','webuploader_expired_upload'),$array);
        }
    }

    public function beforeValidate($event)
    {
        if(\Yii::$app->request->isPost && $event->sender->images && is_array($event->sender->images))
            $event->sender->setAttribute('images',  implode('|',$event->sender->images));
    }
}
