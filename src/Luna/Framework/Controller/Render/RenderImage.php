<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\MimeTypes;
use Luna\Framework\Http\Response;
use Luna\Framework\View\ImageView;

trait RenderImage
{

    public function renderImage(string $filepath, string $mimeType = ''): Response
    {
        // MIMEタイプが指定されていないときはファイル名の拡張子から取得
        if ($mimeType == '') {
            $ext = pathinfo($filepath, \PATHINFO_EXTENSION);
            $mimeType = MimeTypes::getMimeTypeByExtension($ext);
            if (!$mimeType) {
                $mimeType = 'application/octet-stream';
            }
        }
        return (new Response())
            ->header('Content-type', $mimeType)
            ->view(
                (new ImageView(
                    $this->application,
                    $this->request,
                    $filepath
                )
                )
            );
    }
}
