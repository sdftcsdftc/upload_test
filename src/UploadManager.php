<?php

namespace FceskyUpload;

use FceskyUpload\contract\FileInfo;
use FceskyUpload\exception\UploadException;
use FceskyUpload\UploadInterface;

class UploadManager{

    protected $diskInstance;

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->init();
    }

    protected function init(){
        $defaultDisk = $this->config['default']['disk'] ?: '';
        if($this->config['disks'][$defaultDisk]['driver'] && class_exists($this->config['disks'][$defaultDisk]['driver'])){
            $diskDriver = $this->config['disks'][$defaultDisk]['driver'];
            $this->diskInstance = new $diskDriver($this->config['disks'][$defaultDisk]);
            if(!$this->diskInstance instanceof UploadInterface){
                throw new UploadException('必须使用定义好的驱动类',1001);
            }
        }else{
            throw new UploadException('配置读取失败，请检查配置文件',1000);
        }
    }

    public function uploadFile(FileInfo $fileInfo)
    {
        return $this->diskInstance->uploadFile($fileInfo);
    }

    public function mergeFile(FileInfo $fileInfo){
        return $this->diskInstance->mergeFile($fileInfo);
    }

    public function checkFile(FileInfo $fileInfo){
        return $this->diskInstance->checkFile($fileInfo);
    }



}