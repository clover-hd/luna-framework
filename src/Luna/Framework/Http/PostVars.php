<?php

namespace Luna\Framework\Http;

class PostVars extends HttpVars
{
    public function __construct()
    {
        parent::__construct($_POST);
    }
}