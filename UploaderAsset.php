<?php


namespace app\modules\administer\assets;
use yii\web\AssetBundle;

class UploaderAsset extends AssetBundle
{
    // public $basePath = '@webroot';
    // public $baseUrl = '@web/assets/common/ueditor';
    public $sourcePath = '@app/modules/administer/static';
    public $js = [
        'js/webuploader/webuploader.nolog.min.js',
        'js/webuploader/zhongda.js',
    ];
    public $css = [
        'js/webuploader/webuploader.css',
        'js/webuploader/zhongda.css',
    ];
}

