<?php

/**
 * webuploader的前端资源
 */
namespace vcico\webuploader;

use yii\web\AssetBundle;

class UploaderAsset extends AssetBundle
{
    // public $basePath = '@webroot';
    // public $baseUrl = '@web/assets/common/ueditor';
    public $sourcePath = '@vendor/vcico/webuploader/static';
    public $js = [
        'js/webuploader/webuploader.nolog.min.js',
        'js/webuploader/zhongda.js',
    ];
    public $css = [
        'js/webuploader/webuploader.css',
        'js/webuploader/zhongda.css',
    ];
}

