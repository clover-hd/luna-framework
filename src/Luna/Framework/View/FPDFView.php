<?php

namespace Luna\Framework\View;

use FPDF;
use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

class FPDFView extends View
{
    /**
     * FPDF
     *
     * @var FPDF
     */
    protected $fpdf;

    /**
     * ファイル名
     *
     * @var string
     */
    protected $filename;

    /**
     * inline出力するか
     *
     * @var string
     */
    protected $dest;

    public function __construct(Application $application, Request $request, FPDF $fpdf, string $filename, string $dest)
    {
        parent::__construct($application, $request);
        $this->fpdf = $fpdf;
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
