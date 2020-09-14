<?php

namespace Luna\Framework\Contrib\Csv;

use Luna\Framework\Validator\Validator;

class CsvData
{
    protected $csvData = [];
    protected $columns = [];
    protected $dictionaryData = [];

    public function __construct(array $csvData, array $columns = [])
    {
        $this->csvData = $csvData;
        $this->columns = $columns;

        if (count($this->columns) > 0) {
            $this->createDictionary();
        }
    }

    protected function createDictionary()
    {
        // CSV配列をカラム名付連想配列に変換する
        $this->dictionaryData = [];
        foreach ($this->csvData as $rowNo => $rowData) {
            $row = [];
            foreach ($rowData as $colNo => $colData) {
                $row[$this->columns[$colNo]] = $colData;
            }
            $this->dictionaryData[$rowNo] = $row;
        }
    }

    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        $this->createDictionary();
    }

    public function getDictionaryData()
    {
        return $this->dictionaryData;
    }

    public function checkHeader(array $headerColumns): bool
    {
        foreach ($headerColumns as $idx => $col) {
            if ($col != $this->csvData[0][$idx]) {
                return false;
            }
        }

        return count($headerColumns) == count($this->csvData[0]);
    }

    public function validate(Validator $validator): array
    {
        $errors = [];
        foreach ($this->dictionaryData as $rowNo => $data) {
            if ($rowNo > 0 && $validator->validate($data) === false) {
                $errors[$rowNo] = $validator->getValidationError();
            }
        }

        return $errors;

    }

}
