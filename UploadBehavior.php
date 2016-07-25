<?php

namespace app\modules\administer\components;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Description of UploadBehavior
 *@todo  修改 多字段
 * @author Administrator
 */
class UploadBehavior extends Behavior{
    
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
