<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;
use TCPDF;

class TCPDFView extends View
{
    /**
     * TCPDF
     *
     * @var TCPDF
     */
    protected $tcpdf;

    /**
     * ファイル名
     *
     * @var string
     */
    protected $filename;

    /**
     * 出力先
     *
     * @var string
     */
    protected $dest;

    public function __construct(Application $application, Request $request, TCPDF $tcpdf, string $filename, string $dest)
    {
        parent::__construct($application, $request);
        $this->tcpdf = $tcpdf;
        $this->filename = $filename;
        $this->dest = $dest;
    }

    public function init(Routes $routes, Route $route)
    {
    }

    public function render()
    {
        $this->tcpdf->Output($this->filename, $this->dest);
    }

    public function fetch()
    {
        //return $this->fpdi->Output($this->filename, 'S');
        return '';
    }

}
