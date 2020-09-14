<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\TCPDFView;
use TCPDF;

trait RenderTCPDF
{
    public function renderTCPDF(TCPDF $tcpdf, string $filename, string $dest): Response
    {
        return (new Response())
            ->view(
                (new TCPDFView(
                    $this->application,
                    $this->request,
                    $tcpdf,
                    $filename,
                    $dest
                )
                )
            );
    }
}
