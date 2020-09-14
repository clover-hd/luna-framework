<?php

namespace Luna\Framework\Http;

class UploadFile
{
    protected $file;

    public function __construct(array $file)
    {
        $this->file = $file;
    }

    public function getTmpFilePath()
    {
        return $this->file['tmp_name'];
    }

    public function getError()
    {
        return $this->file['error'];
    }

    public function getFileSize()
    {
        return $this->file['size'];
    }

    public function getMimeType()
    {
        return $this->file['type'];
    }

    public function getFilename()
    {
        return $this->file['name'];
    }

    public function getFileExtention()
    {
        $ext = pathinfo($this->file['name'], \PATHINFO_EXTENSION);
        return $ext;
    }
}