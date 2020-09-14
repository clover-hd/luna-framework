<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\FileDownloadView;

trait RenderFileDownload
{

    public function renderFileDownload(string $srcFilepath, $filename): Response
    {
        return (new Response())
            ->header('Content-type', 'application/force-download')
            ->header('Content-Disposition', 'attachment; filename="' . urldecode($filename) . '"')
            ->view(
                (new FileDownloadView(
                    $this->application,
                    $this->request,
                    $srcFilepath,
                    $filename
                )
                )
            );
    }
}
