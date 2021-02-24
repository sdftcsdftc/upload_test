<?php

namespace FceskyUpload;

use FceskyUpload\contract\FileInfo;

interface UploadInterface{
    //检测切片
    public function checkFile(FileInfo $f);
    //上传切片
    public function uploadFile(FileInfo $f);
    //合并切片
    public function mergeFile(FileInfo $f);
}