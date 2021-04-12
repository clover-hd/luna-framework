<?php

namespace Luna\Framework\Validator;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\HttpVars;
use Symfony\Component\Yaml\Yaml;

/**
 *
 * @method bool validate(array|HttpVars)
 */
class Validator
{
    protected $validationRules;
    /**
     * エラー
     *
     * @var ValidationError
     */
    protected $validationError;
    protected $application;
    protected $modelName;

    public function __construct(string $modelName, array $validationRules)
    {
        $this->modelName = $modelName;
        $this->validationRules = $validationRules;
        $this->application = Application::getInstance();
    }

    public function validate($checkParams)
    {
        // 引数の変数がHttpVarsであれば生のHTTP変数を使用
        if ($checkParams instanceof HttpVars) {
            $params = $checkParams->getRawVars();
        } else {
            $params = $checkParams;
        }
        $this->validationError = new ValidationError();
        foreach ($this->validationRules as $column => $rule) {
            if (is_array($rule)) {
                $rules = $rule;
            } else {
                $rules = [];
                $tmpRules = explode("|", $rule);
                foreach ($tmpRules as $tmp) {
                    if (preg_match('/=/', $tmp) === 1)
                    {
                        list($ruleType, $ruleValue) = explode('=', $tmp, 2);
                        $rules[$ruleType] = $ruleValue;
                    } else {
                        $rules[$tmp] = '';
                    }
                }
            }

            foreach ($rules as $ruleType => $ruleValue) {
                if ($ruleType == "required") {
                    // 必須チェック
                    $this->validateRequired($params, $column);
                } else if ($ruleType == "requiredWith") {
                    // 必須チェック
                    $this->validateRequiredWith($params, $column, $ruleValue);
                } else if ($ruleType == "numeric") {
                    // 数値チェック
                    $this->validateNumeric($params, $column);
                } else if ($ruleType == 'lengthMin') {
                    // 文字用最小桁数チェック
                    $this->validateLengthMin($params, $column, $ruleValue);
                } else if ($ruleType == 'lengthMax') {
                    // 文字用最大桁数チェック
                    $this->validateLengthMax($params, $column, $ruleValue);
                } else if ($ruleType == 'length') {
                    // 文字用桁数チェック
                    $this->validateLength($params, $column, $ruleValue);
                } else if ($ruleType == 'byteLengthMin') {
                    // 最小桁数チェック
                    $this->validateByteLengthMin($params, $column, $ruleValue);
                } else if ($ruleType == 'byteLengthMax') {
                    // 最大桁数チェック
                    $this->validateByteLengthMax($params, $column, $ruleValue);
                } else if ($ruleType == 'min') {
                    // 最小数チェック
                    $this->validateMin($params, $column, $ruleValue);
                } else if ($ruleType == 'max') {
                    // 最大数チェック
                    $this->validateMax($params, $column, $ruleValue);
                } else if ($ruleType == "email") {
                    // メールアドレス形式チェック
                    $this->validateEmail($params, $column);
                } else if ($ruleType == "date") {
                    // 日付形式チェック(yyyy-mm-dd, yyyy/mm/dd)
                    $this->validateDate($params, $column);
                } else if ($ruleType == "date_with") {
                    // 日付形式チェック(複数フィールド)
                    $this->validateDateWith($params, $column, $ruleValue);
                } else if ($ruleType == "datetime") {
                    // 日付形式チェック(yyyy-mm-dd H:i:s, yyyy/mm/dd H:i:s)
                    $this->validateDatetime($params, $column);
                } else if ($ruleType == "time") {
                    // 時刻形式チェック
                    $this->validateTime($params, $column);
                } else if ($ruleType == "regex") {
                    // 正規表現チェック
                    $this->validateRegex($params, $column, $ruleValue);
                } else if ($ruleType == "tel") {
                    // 電話番号形式チェック
                    $this->validateTel($params, $column);
                } else if ($ruleType == "zip") {
                    // 郵便番号形式チェック
                    $this->validateZip($params, $column);
                } else if ($ruleType == "hankaku") {
                    // 半角チェック
                    $this->validateHankaku($params, $column);
                }
            }
        }

        return $this->validationError->hasError() === false;
    }

    /**
     * バリデーションエラーの配列を返します。
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->validationError->toArray();
    }

    /**
     * バリデーションのエラーを返します。
     * 
     * @return ValidationError
     */
    public function getValidationError()
    {
        return $this->validationError;
    }

    protected function storeError($column, $errorType, $errorName = null)
    {
        $this->validationError->put($this->modelName, $column, is_null($errorName) ? $errorType : $errorName);
        // $config = $this->application->getLocaleConfig();
        // if (isset($config['error'][$this->modelName][$column][$errorType]))
        // {
        //     $this->errors[$column][$errorType] = $config['error'][$this->modelName][$column][$errorType];
        // }
        // else
        // {
        //     $this->errors[$column][$errorType] = "error.{$this->modelName}.{$column}.{$errorType}";
        // }
    }

    /**
     * 必須入力チェック
     *
     * @param   $values 値が格納された連想配列
     * @param   $name   チェックする要素名
     * @param   $rule   チェック条件
     */
    protected function validateRequired(array $params, string $column)
    {
        if (!isset($params[$column]) || $params[$column] === '') {
            $this->storeError($column, 'required');
        }
    }

    /**
     * 必須入力チェック
     *
     * @param   $values 値が格納された連想配列
     * @param   $name   チェックする要素名
     * @param   $rule   チェック条件
     */
    protected function validateRequiredWith(array $params, string $column, string $ruleValue)
    {
        $withCols = explode(',', $ruleValue);
        if (isset($params[$column]) && $params[$column] !== '') {
            foreach ($withCols as $col) {
                if (!isset($params[$col]) || $params[$col] === '') {
                    $this->storeError($col, 'requiredWith');
                }
            }
        }
    }

    /**
     * 数値型チェック
     *
     * @param   $values 値が格納された連想配列
     * @param   $name   チェックする要素名
     * @param   $rule   チェック条件
     */
    protected function validateNumeric(array $params, string $column)
    {
        if (isset($params[$column]) && $params[$column] !== '' && !is_numeric($params[$column])) {
            $this->storeError($column, 'numeric');
        }
    }

    protected function validateLengthMin(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && $params[$column] !== '' && mb_strlen($params[$column]) < $size) {
            $this->storeError($column, 'lengthMin');
        }
    }

    protected function validateLengthMax(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && $params[$column] !== '' && mb_strlen($params[$column]) > $size) {
            $this->storeError($column, 'lengthMax');
        }
    }

    protected function validateLength(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && $params[$column] !== '' && mb_strlen($params[$column]) != $size) {
            $this->storeError($column, 'length');
        }
    }

    protected function validateByteLengthMin(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && !empty($params[$column]) && strlen($params[$column]) < $size) {
            $this->storeError($column, 'byteLengthMin');
        }
    }

    protected function validateByteLengthMax(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && !empty($params[$column]) && strlen($params[$column]) > $size) {
            $this->storeError($column, 'byteLengthMax');
        }
    }

    protected function validateByteLength(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && !empty($params[$column]) && strlen($params[$column]) == $size) {
            $this->storeError($column, 'byteLength');
        }
    }

    protected function validateMin(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && $params[$column] !== '' && intval($params[$column]) < $size) {
            $this->storeError($column, 'min');
        }
    }

    protected function validateMax(array $params, string $column, string $ruleValue)
    {
        $size = intval($ruleValue);
        if (isset($params[$column]) && $params[$column] !== '' && intval($params[$column]) > $size) {
            $this->storeError($column, 'max');
        }
    }

    protected function validateEmail(array $params, string $column)
    {
        $patarn = "/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/";
        if (isset($params[$column]) && $params[$column] !== '') {
            if (strlen($params[$column]) != mb_strlen($params[$column])) {
                // メールアドレスは半角英数字で入力してください。
                $this->storeError($column, 'email');
            } elseif (!preg_match($patarn, $params[$column])) {
                // メールアドレスの正しい書式ではありません。
                $this->storeError($column, 'email');
            }
        }
    }

    protected function validateDate(array $params, string $column)
    {
        if (isset($params[$column]) && $params[$column] !== '') {
            if (preg_match('/^([1-9][0-9]{3})[-\/](0[1-9]{1}|1[0-2]{1})[-\/](0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $params[$column]) === 1)
            {
                list($year, $month, $date) = preg_split('/[-\/]/', $params[$column]);
                $checkDate = sprintf('%04d-%02d-%02d', $year, $month, $date);
                $formattedDate = date('Y-m-d', mktime(0, 0, 0, $month, $date, $year));
                if ($checkDate != $formattedDate) {
                    // 日付が正しくありません
                    $this->storeError($column, 'date');
                }
            }
            else
            {
                // 日付が正しくありません
                $this->storeError($column, 'date');
            }
        }
    }

    protected function validateDateWith(array $params, string $column, string $ruleValue)
    {
        list($year_column, $month_column, $date_column) = explode(',', $ruleValue);
        if (empty($year_column) === false && empty($month_column) === false && empty($date_column) === false) {
            $year = $params[$year_column];
            $month = $params[$month_column];
            $date = $params[$date_column];
            // 年・月・日、いずれかが入力されていればチェック
            if (empty($year) === false || empty($month) === false || empty($date) === false) {
                if (empty($year) === false && empty($month) === false && empty($date) === false) {
                    $checkDate = sprintf('%04d-%02d-%02d', $year, $month, $date);
                    $formattedDate = date('Y-m-d', mktime(0, 0, 0, $month, $date, $year));
                    if ($checkDate != $formattedDate) {
                        // 日付が正しくありません
                        $this->storeError($column, 'date_with');
                    }
                } else {
                    // 一部しか入力されていない
                    $this->storeError($column, 'date_with');
                }
            }
        }
    }

    protected function validateDatetime(array $params, string $column)
    {
        if (isset($params[$column]) && $params[$column] !== '') {
            list($paramDate, $paramTime) = explode(' ', $params[$column]);
            if (preg_match('/^([1-9][0-9]{3})[-\/](0[1-9]{1}|1[0-2]{1})[-\/](0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $paramDate) === 1)
            {
                list($year, $month, $date) = preg_split('/[-\/]/', $paramDate);
                $checkDate = sprintf('%04d-%02d-%02d', $year, $month, $date);
                $formattedDate = date('Y-m-d', mktime(0, 0, 0, $month, $date, $year));
                if ($checkDate != $formattedDate) {
                    // 日時が正しくありません
                    $this->storeError($column, 'datetime');
                } else {
                    if (preg_match('/^[0-9]{2}:[0-9]{2}((:[0-9]{2})*)$/u', $paramTime) === 1)
                    {
                        list($hour, $minute, $second) = preg_split('/[:\/]/', $paramTime);
                        $second = $second ? $second : 0;
                        $time = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
                        $formattedTime = date('H:i:s', mktime($hour, $minute, $second));
                        if ($time != $formattedTime) {
                            // 日時が正しくありません
                            $this->storeError($column, 'datetime');
                        }
                    }
                    else
                    {
                        // 日時が正しくありません
                        $this->storeError($column, 'datetime');
                    }
                }
            }
            else
            {
                // 日時が正しくありません
                $this->storeError($column, 'datetime');
            }
        }
    }

    protected function validateTime(array $params, string $column)
    {
        if (isset($params[$column]) && $params[$column] !== '') {
            if (preg_match('/^[0-9]{2}:[0-9]{2}((:[0-9]{2})*)$/u', $params[$column]) === 1)
            {
                list($hour, $minute, $second) = preg_split('/[:\/]/', $params[$column]);
                $second = $second ? $second : 0;
                $time = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
                $formattedTime = date('H:i:s', mktime($hour, $minute, $second));
                if ($time != $formattedTime) {
                    // 時刻が正しくありません
                    $this->storeError($column, 'time');
                }
            }
            else
            {
                // 時刻が正しくありません
                $this->storeError($column, 'time');
            }
        }
    }

    protected function validateRegex(array $params, string $column, string $ruleValue)
    {
        if (isset($params[$column]) && $params[$column] !== '') {
            if (preg_match($ruleValue, $params[$column]) === 0)
            {
                // 形式が正しくありません
                $this->storeError($column, 'regex');
            }
        }
    }

    protected function validateTel(array $params, string $column)
    {
        $pattern = '/\A(((0(\d{1}[-(]?\d{4}|\d{2}[-(]?\d{3}|\d{3}[-(]?\d{2}|\d{4}[-(]?\d{1}|[5789]0[-(]?\d{4})[-)]?)|\d{1,4}\-?)\d{4}|0120[-(]?\d{3}[-)]?\d{3})\z/';
        if (isset($params[$column]) && !empty($params[$column])) {
            if (strlen($params[$column]) != mb_strlen($params[$column])) {
                // 電話番号は半角数字で入力してください。
                $this->storeError($params, 'tel');
            } elseif (!preg_match($pattern, $params[$column])) {
                // 電話番号の正しい書式ではありません。
                $this->storeError($column, 'tel');
            }
        }
    }

    protected function validateZip(array $params, string $column)
    {
        $pattern = '/^\d{3}[-]\d{4}$|^\d{3}[-]\d{2}$|^\d{3}$|^\d{5}$|^\d{7}$/';
        if (isset($params[$column]) && !empty($params[$column])) {
            if (strlen($params[$column]) != mb_strlen($params[$column])) {
                // 郵便番号は半角で入力してください。
                $this->storeError($column, 'zip');
            } elseif (!preg_match($pattern, $params[$column])) {
                // 郵便番号の正しい書式ではありません。
                $this->storeError($column, 'zip');
            }
        }
    }

    protected function validateHankaku(array $params, string $column)
    {
        $pattern = '/^[^ -~｡-ﾟ\x00-\x1f\t]+$/u';
        if (isset($params[$column]) && !empty($params[$column])) {
            if (strlen($params[$column]) != mb_strlen($params[$column])) {
                // 半角で入力してください。
                $this->storeError($column, 'hankaku');
            }
        }
    }
}
