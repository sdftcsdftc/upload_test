<?php

namespace FceskyUpload\drivers;

use FceskyUpload\contract\FileInfo;
use FceskyUpload\UploadInterface;
use FceskyUpload\exception\UploadException;

class LocalDriver implements UploadInterface
{

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    //检测切片
    public function checkFile(FileInfo $f){
        $identifier = $f->identifier;
        $filePath = $this->config['tmp_dir'] . $identifier; //临时分片文件路径
        $totalChunks = $f->totalChunks;
        //检查分片是否存在
        $chunkExists = [];
        for ($index = 1; $index <= $totalChunks; $index++ ) {
            if (file_exists("{$filePath}_{$index}")) {
                array_push($chunkExists, $index);
            }
        }
        if (count($chunkExists) == $totalChunks) { 
            //全部分片存在，则直接合成
            return $this->mergeFile($f);
        } else {
            //分片有缺失，返回已存在的分片
            $res['uploaded'] = $chunkExists;
            return $res;
        }
    }
    //上传切片
    public function uploadFile(FileInfo $f){
        if (!empty($_FILES)) {
            if (!$in = fopen($_FILES["file"]["tmp_name"], "rb")) {
                 throw new UploadException('打开临时文件失败');
            }
        } else {
            if (!$in = fopen("php://input", "rb")) {
                throw new UploadException('打开文件流失败');
            }
        }
        if ($f->totalChunks === 1) {
            //如果总共只有1片，则不需要合并，直接将临时文件转存到保存目录下
            $saveDir = rtrim($this->config['save_dir'],'/')   .DIRECTORY_SEPARATOR .'resources'.DIRECTORY_SEPARATOR. date('Ymd');
            if (!is_dir($saveDir)) {
                mkdir($saveDir,0777,true);
            }
            $random = lcg_value();
            $uploadPath = $saveDir . DIRECTORY_SEPARATOR .$f->identifier.$random.'.'.$f->ext;
            // $res['filepath'] = '/vod/resources/'.date('Ymd') . '/' . $f->identifier.$random.'.'.$f->ext;
            $res['savepath'] = $uploadPath;
            $res['merge'] = false;
        } else { //需要合并
            $filePath = $this->config['tmp_dir'] . $f->identifier; //临时分片文件路径
            $uploadPath = $filePath . '_' . $f->chunkNumber; //临时分片文件名
            $res['merge'] = true;
        }
        $baseDir = dirname($uploadPath);
        if (!is_dir($baseDir)) {
            mkdir($baseDir,0777,true);
        }
        if (!$out = fopen($uploadPath, "wb")) {
            throw new UploadException('上传的路径没有写入权限');
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        fclose($in);
        fclose($out);

        $res['code'] = 0;
        return $res;
    }
    //合并切片
    public function mergeFile(FileInfo $f){
        $filePath = $this->config['tmp_dir'] . $f->identifier;
        $totalChunks = $f->totalChunks; //总分片数
        $done = true;
        //检查所有分片是否都存在
        for ($index = 1; $index <= $totalChunks; $index++) {
            if (!file_exists("{$filePath}_{$index}")) {
                $done = false;
                break;
            }
        }
        if ($done === false) {
            throw new UploadException(1007,'分片缺失,无法合并.总分片数:'.$totalChunks.'找到分片数:'.$index);
        }
        //如果所有文件分片都上传完毕，开始合并
        $saveDir = rtrim($this->config['save_dir'],'/')  .DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR. date('Ymd');
        if (!is_dir($saveDir)) {
            mkdir($saveDir,0777,true);
        }
        $random = lcg_value();
        $uploadPath = $saveDir . DIRECTORY_SEPARATOR .$f->identifier.$random.'.'.$f->ext;

        if (!$out = fopen($uploadPath, "wb")) {
            throw new UploadException(1006,'upload path is not writable');
        }
        if (flock($out, LOCK_EX) ) { // 进行排他型锁定
            for($index = 1; $index <= $totalChunks; $index++ ) {
                if (!$in = fopen("{$filePath}_{$index}", "rb")) {
                    break;
                }
                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }
                fclose($in);
                unlink("{$filePath}_{$index}"); //删除分片
            }

            flock($out, LOCK_UN); // 释放锁定
        }
        fclose($out);

        $res['code'] = 0;
        // $res['filepath'] = '/vod/resources/'.date('Ymd') . '/' . $f->identifier.$random.'.'.$f->ext;
        $res['savepath'] = $uploadPath;


        return $res;
    }
}
