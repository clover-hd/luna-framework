<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\CSVView;

trait RenderCsv
{

    public function renderCsv(array $head, array $columns, array $values, string $csvFilename, string $charset = 'utf-8'): Response
    {
        return (new Response())
            ->header('Content-type', 'text/csv; charset=' . $charset)
            ->header('Content-Disposition', 'attachment; filename="' . $csvFilename . '"')
            ->view(new CSVView(
                $this->application,
                $this->request,
                $head,
                $columns,
                $values,
                $csvFilename,
                $charset
            )
            );
    }
}
