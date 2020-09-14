<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Controller\Controller;
use Luna\Framework\Database\ORM\Model;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

class CSVView extends View
{
    protected $csvHead;
    protected $columns;
    protected $values;
    protected $charset;
    protected $csvFilename;

    public function __construct(Application $application, Request $request, array $head, array $columns, array $values, string $csvFilename, string $charset = 'utf-8')
    {
        parent::__construct($application, $request);
        $this->head = $head;
        $this->columns = $columns;
        $this->values = $values;
        $this->csvFilename = $csvFilename;
        $this->charset = $charset;
    }

    public function init(Routes $routes, Route $route)
    {
    }

    public function render()
    {
        $csvData = $this->buildCsv();
        echo mb_convert_encoding($csvData, $this->charset);
    }

    public function fetch()
    {
        $csvData = $this->buildCsv();
        return mb_convert_encoding($csvData, $this->charset);
    }

    protected function buildCsv()
    {
        $tmpDir = $this->application->getProjectPath() . '/tmp/';
        $filename = tempnam($tmpDir, 'csv-download');
        $handle = fopen($filename, 'w');
        fputcsv($handle, $this->head);
        foreach ($this->values as $val)
        {
            $line = array();
            reset($this->columns);
            foreach ($this->columns as $col)
            {
                if (empty($col)) {
                    $line[] = '';
                } else if ($val instanceof Model) {
                    $line[] = $val->$col;
                } else if (is_array($val)) {
                    $line[] = $val[$col];
                }
            }
            fputcsv($handle, $line);
        }
        fclose($handle);
        $csvData = file_get_contents($filename);
        unlink($filename);

        return $csvData;
    }
}
