<?php

namespace Luna\Framework\Validator;

use Luna\Framework\Application\Application;

class ValidationError
{
    protected $errors = [];
    protected $application;

    public function __construct()
    {
        $this->errors = [];
        $this->application = Application::getInstance();
    }

    /**
     * エラーの配列を返す
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->errors;
    }

    /**
     * エラーを登録する
     *
     * @param string $modelName エラーが発生したモデル
     * @param string $column エラーが発生したカラム
     * @param string $errorType エラーのタイプ
     * @return ValidationError
     */
    public function put(string $modelName, string $column, string $errorType)
    {
        $config = $this->application->getLocaleConfig();
        if (isset($config['error'][$modelName][$column][$errorType]))
        {
            $this->errors[$column][$errorType] = $config['error'][$modelName][$column][$errorType];
        }
        else
        {
            $this->errors[$column][$errorType] = "error.{$modelName}.{$column}.{$errorType}";
        }
        return $this;
    }

    /**
     * 指定したエラーを持っているか返す
     *
     * @param string $column カラム名
     * @param string $errorType エラーのタイプ
     * @return boolean
     */
    public function hasError(string $column = null, string $errorType = null): bool
    {
        if (is_null($column)) {
            return count($this->errors) > 0;
        } else if (is_null($errorType)) {
            return isset($this->errors[$column]) && !empty($this->errors[$column]);
        } else {
            return isset($this->errors[$column][$errorType]) && !empty($this->errors[$column][$errorType]);
        }
    }

    /**
     * 指定したエラーの範囲のエラーメッセージを返す。
     * 複数ある場合は"\n"で接続して返します。
     *
     * @param string $column カラム名
     * @param string $errorType エラーのタイム
     * @return string
     */
    public function getErrorMsg(string $column = null, string $errorType = null): string
    {
        return $this->getErrorMessage($column, $errorType);
    }


    /**
     * 指定したエラーの範囲のエラーメッセージを返す。
     * 複数ある場合は"\n"で接続して返します。
     *
     * @param string $column カラム名
     * @param string $errorType エラーのタイム
     * @return string
     */
    public function getErrorMessage(string $column = null, string $errorType = null): string
    {
        if (is_null($column)) {
            $errorMsgs = [];
            foreach ($this->errors as $col => $val) {
                foreach ($val as $msg)
                {
                    $errorMsgs[] = $msg;
                }
            }
            return implode("\n", $errorMsgs);
        } else if (is_null($errorType)) {
            if (isset($this->errors[$column])) {
                $errorMsgs = [];
                foreach ($this->errors[$column] as $msg)
                {
                    $errorMsgs[] = $msg;
                }
                return implode("\n", $errorMsgs);
            } else {
                return "";
            }
        } else if (isset($this->errors[$column][$errorType])) {
            return $this->errors[$column][$errorType];
        }
        return "";

    }
}