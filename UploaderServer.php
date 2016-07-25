<?php
namespace vcico\webuploader;


/**
 * 文件上传服务端
 */
use yii\base\Exception;
use Yii;

class UploaderServer{
    public $errors = [];
    public $file;
    public function upload($field,$cate){
        try{
            if(isset($_POST['chunks']) && $_POST['chunks'] > 1)
                $uploader = new chunks($field);
            else 
                $uploader = new complete($field);
            $uploader->upload($cate);
            $this->file = $uploader->_path.$uploader->_filename;
            return true;
        } catch (Exception $ex){
            $this->errors[]= $ex->getMessage();
            return false;
        }
    }
}

/**
 * 分片上传
 */
class chunks extends uploads
{
    private static $_tempPath;
    
    public function __construct($field) {
        parent::__construct($field);
         self::$_tempPath = Yii::getAlias('@webroot/temp');
    }
    
    protected function validate($cate) {
        parent::validate($cate);
        if(!self::createDir(self::$_tempPath)) throw new Exception('创建文件夹失败');
    }
    
    public function upload($cate){
        $this->validate($cate);
        if(function_exists('move_uploaded_file')){
            if(@move_uploaded_file($this->file['tmp_name'],self::$_tempPath.'/'.$this->file['name'].$_POST['chunk'].'.temp')){
               self::expired_temp(self::$_tempPath.'/'.$this->file['name'].$_POST['chunk'].'.temp');
                $this->merge(self::$_tempPath.'/'.$this->file['name'].$_POST['chunk'].'.temp');
                self::expired_upload($this->_path.$this->_filename);
                return true;
            }
            return false;
        }else{
                throw new Exception('move_uploaded_file函数不存在！');
        }
    }
    
    private static function expired_temp($temp_name){   // 缓存起来 如有遗漏 定期清除
        $data = Yii::$app->cache->get(Yii::t('cacheKey', 'webuploader_expired_temp'));
        if(!$data) $data = [];
        $data[$temp_name] = time();
        Yii::$app->cache->set(Yii::t('cacheKey', 'webuploader_expired_temp'),$data);
    }
    
    /**
     * 合并文件
     * @param type $filename
     * @throws Exception
     */
    private function merge($filename){
        if($this->all_temp()){
                $this->_filename = self::filename($this->file['name']);
                if (!$out = @fopen(self::$_uploadPath .$this->_path. $this->_filename, "wb")) {
                    throw new Exception('文件打开失败！');
                }
                if ( flock($out, LOCK_EX) ) {
                    for($i = 0;$i<$_POST['chunks'];$i++){
                            if (!$in = @fopen(self::$_tempPath.'/'.$this->file['name'].$i.'.temp', "rb")) {
                                    break;
                            }
                            while ($buff = fread($in, 4096)) {
                                    fwrite($out, $buff);
                            }
                            @fclose($in);
                            @unlink(self::$_tempPath.'/'.$this->file['name'].$i.'.temp');
                    }
                    flock($out, LOCK_UN);
                }
                @fclose($out);
                Yii::$app->cache->delete(Yii::t('cacheKey','webuploader_repeat_chunks').md5($this->file['name'],true));
        }
    }
    
    /**
     * 分片是否已经全部上传
     * @return boolean
     */
    private function all_temp(){
        $f = fopen('./lock.txt','a+');
        flock($f, LOCK_EX);
        $this->add_temp_cache();
        $result = true;
        if(Yii::$app->cache->get(Yii::t('cacheKey','webuploader_temp_num').md5($this->file['name'],true)) >= $_POST['chunks']){
            for($i = 0;$i<$_POST['chunks'];$i++){
                if(!is_file(self::$_tempPath.'/'.$this->file['name'].$i.'.temp')){
                        $result = false;
                        break;
                }
            }
        }else{
            $result = false;
        }
        flock($f, LOCK_UN);
        @fclose($f);
        if($result) $this->del_temp_cache();
        return $result;
    }
    
    private function del_temp_cache(){
        Yii::$app->cache->delete(Yii::t('cacheKey','webuploader_temp_num').md5($this->file['name'],true));
    }

    /**
     * 把已上传的分片记录在册 避免重复上传
     * 并把分片上传的数量增加1 用以判断是否上传完毕
     */
    private function add_temp_cache(){
        $exist_temp = Yii::$app->cache->get(Yii::t('cacheKey','webuploader_repeat_chunks').md5($this->file['name'],true));
        if(!$exist_temp)  $exist_temp = [];
        if(!in_array($_POST['chunk'], $exist_temp)){
            $num = intval(Yii::$app->cache->get(Yii::t('cacheKey','webuploader_temp_num').md5($this->file['name'],true)));
            Yii::$app->cache->set(Yii::t('cacheKey','webuploader_temp_num').md5($this->file['name'],true),$num+1,300);
            $exist_temp[]= $_POST['chunk'];
            Yii::$app->cache->set(Yii::t('cacheKey','webuploader_repeat_chunks').md5($this->file['name'],true),$exist_temp,300);
        }
    }
    
}

/**
 * 整个文件上传
 */
class complete extends uploads
{
    public function upload($cate){
        $this->validate($cate);
        $this->_filename = self::filename($this->file['name']);
        if(function_exists('move_uploaded_file')){
            if(@move_uploaded_file($this->file['tmp_name'],self::$_uploadPath.$this->_path.$this->_filename)){
                self::expired_upload($this->_path.$this->_filename);
                return true;
            }
            return false;
        }else{
            throw new Exception('move_uploaded_file函数不存在！');
        }
    }
}

abstract class uploads{
    protected $file;
    protected static $_type = ['jpg','jpeg','gif','png'];
    protected static $_maxSize = 2097152;
    public static $_uploadPath;
    public $_path;
    public $_filename;
    
    public function __construct($field){
        if(!isset($_FILES[$field]))  throw new Exception('没有找到上传文件');
        $this->file = $_FILES[$field];
        self::$_uploadPath = Yii::getAlias('@webroot/upload');
    }
    
    /**
     * 上传文件
     */
    public abstract function upload($cate);
    
    protected function validate($cate){
        if($err = self::success($this->file['error'])) throw new Exception($err);
        if(!self::type($this->file['name'])) throw new Exception('上传文件类型错误');
        if(!self::size($this->file['size'])) throw new Exception('上传的文件大小超出了限制');
        $this->_path = self::Path($cate);
        if(!self::createDir(self::$_uploadPath.$this->_path)) throw new Exception('创建文件夹失败');
    }
    
    private static function success($error){
        if($error != 0){
            switch($error){
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                            $err =  '上传的文件大小超出了限制';
                            break;
                    case UPLOAD_ERR_PARTIAL:
                            $err = '文件未完整上传';
                            break;
                    case UPLOAD_ERR_NO_FILE:
                            $err = '没有找到上传文件';
                            break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                            $err = "找不到临时文件夹"; 
                            break;
                    case UPLOAD_ERR_CANT_WRITE: 
                            $err = "文件写入失败"; 
                            break; 
                    case UPLOAD_ERR_EXTENSION: 
                            $err = "文件停止了上传"; 
                            break; 
                    default:
                            $err = '不明错误！';
            }
            return $err;
        }
        return false;
    }
    
    public static function expired_upload($upload){ // 吧已上传的文件路径缓存起来 若没有在 提交内容关联时清除缓存 （冗余） 过时后会被删除掉 
        $data = Yii::$app->cache->get(Yii::t('cacheKey', 'webuploader_expired_upload'));
        if(!$data) $data = [];
        $data[$upload] = time();
        Yii::$app->cache->set(Yii::t('cacheKey', 'webuploader_expired_upload'),$data);
    } 
    
    /**
     * 上传文件的大小是否超出限制
     * @param integer $size 上传文件的大小
     * @return boolean
     */
    private static function size($size){
            return ($size <= self::$_maxSize);
    }
    /**
     * 检查文件的类型
     * @param string $filename 文件名称
     * @return boolean
     */
    private static function type($filename){
        $arr = explode('.',$filename);
        if(count($arr) < 2) return false;
        return in_array(end($arr),self::$_type);
    }
    
    /**
     * 生成文件夹
     * @param string $dir 文件夹路径
     * @return boolean
     */
    public static function createDir($dir){
            if(!is_dir($dir))
                    return @mkdir($dir,777,true);
            return true;
    }
    /**
     * 获取随机生成的文件名
     * @param string $extension 上传文件的扩展名
     * @return string
     */
    public static function filename($filename){
        return date('Ymd-H-i-s').uniqid().'.'.self::extension($filename);
    }
    /**
     * 获取文件的扩展名
     * @return string
     */
    public static function extension($filename){
        $arr = explode('.', $filename);
        return end($arr);
    }
    /**
     * 在上传根目录$this->_uploadPath 下通过分类和日期生成的上传文件夹
     * @param string $cate 分类名称
     * @return string 
     */
    public static function Path($cate){
            return "/$cate".date('/Y/m/d/');
    }
    
}

