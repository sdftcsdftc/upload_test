<?php

namespace FceskyUpload\contract;

class FileInfo
{
    public $identifier;//文件唯一标识
    public $chunkNumber = 1;//当前文件分片编号
    // public $chunkSize;//分片大小
    // public $currentChunkSize;//当前分片大小
    public $totalChunks = 1;//总分片数
    public $filename;//文件名
    // public $relativePath;//文件本地相对路径
    public $totalSize = 0;//文件大小
    public $ext;//文件后缀
}