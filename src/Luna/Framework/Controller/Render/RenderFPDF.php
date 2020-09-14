<?php

namespace Luna\Framework\Controller\Render;

use FPDF;
use Luna\Framework\Http\Response;
use Luna\Framework\View\FPDFView;

trait RenderFPDF
{
    public function renderFPDF(FPDF $fpdf, string $filename, string $dest): Response
    {
        return (new Response())
            ->view(
                (new FPDFView(
                    $this->application,
                    $this->request,
                    $fpdf,
                    $filename,
                    $dest
                )
                )
            );
    }
}
