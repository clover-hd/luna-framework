<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\MP4View;

trait RenderMP4
{
    public function stream(string $filepath): Response
    {
        $response = new Response();
        $response->header('Content-type', 'video/mp4');

        $fullsize = filesize($filepath);
        $size = $fullsize;
        $stream = fopen($filepath, "r");
        $response->status(200);
        $range = $this->request->header('Range');
        if ($range != null) {
            $eqPos = strpos($range, "=");
            $toPos = strpos($range, "-");
            $unit = substr($range, 0, $eqPos);
            $start = intval(substr($range, $eqPos + 1, $toPos));
            $success = fseek($stream, $start);
            if ($success == 0) {
                $size = $fullsize - $start;
                $response->status(206);
                $response->header('Accept-Ranges', $unit);
                $response->header('Content-Range', $unit . " " . $start . "-" . ($fullsize - 1) . "/" . $fullsize);
            }
        }
        $response->header('Content-Length', $size);

        return $response
            ->view(
                (new MP4View(
                        $this->application,
                        $this->request,
                        $stream
                    )
                )
            );
    }
}
