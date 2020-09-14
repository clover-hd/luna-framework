<?php

namespace Luna\Framework\Database;

use Luna\Framework\Database\Exception\DatabaseException;
use Luna\Framework\Database\Exception\IntegrityConstraintViolationException;
use Luna\Framework\Database\ORM\PDOResultSet;

/**
 * mysql用プリコンパイルSQL文を表すクラスです。<br />
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package database
 */
class PDOStatement extends Statement
{

    public function __construct(Connection $conn, string $sql)
    {
        parent::__construct($conn, $sql);
	}

    public function execute()
    {
        $c = count($this->values);
        $exec_sql = $this->createExecSql();
        $this->logger->info($exec_sql, []);

        $dbh = $this->conn->getConnection();

        $stmt = $dbh->prepare($exec_sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
        if ($stmt !== false) {
            $this->result = $stmt->execute();

            if ($this->result === false) {
                $errorInfo = $stmt->errorInfo();
                $this->logger->error("{$errorInfo[0]}:{$errorInfo[1]}:{$errorInfo[2]}");
                if ($errorInfo[0] === '23000') {
                    throw new IntegrityConstraintViolationException($errorInfo[2], $errorInfo[0]);
                } else {
                    throw new DatabaseException("{$errorInfo[0]}:{$errorInfo[1]}:{$errorInfo[2]}");
                }
            }

            return new PDOResultSet($stmt);
        } else {
            $errorInfo = $dbh->errorInfo();
            $this->logger->error("{$errorInfo[0]}:{$errorInfo[1]}:{$errorInfo[2]}");
            throw new DatabaseException("{$errorInfo[0]}:{$errorInfo[1]}:{$errorInfo[2]}");
        }

    }

    protected function createExecSql(string $sql = null, array $values = null, array $types = null): string
    {
        if (is_null($sql)) {
            $sql = $this->sql;
        }
        if (is_null($values)) {
            $values = $this->values;
        }
        if (is_null($types)) {
            $types = $this->types;
        }
        $c = count($values);

        ksort($values);
        reset($values);
        $key_array = array();

        foreach ($values as $key => $value) {
            if (is_int($key)) {
                // 数字のキーを「:1 :2...」に置換する
                $pos = mb_strpos($sql, "?");
                if ($pos !== false) {
                    $head = mb_substr($sql, 0, $pos);
                    $foot = mb_substr($sql, $pos + 1);
                    $sql = $head . " :" . $key . " " . $foot;

                    if (isset($types[$key])) {
                        $key_array[":" . $key] = $this->getEscapedValue($types[$key], $value);
                    } else {
                        $key_array[":" . $key] = $this->getEscapedValue('string', $value);
                    }
                }
            } else {
                if (isset($types[$key])) {
                    $key_array[$key] = $this->getEscapedValue($this->types[$key], $value);
                } else {
                    $key_array[$key] = $this->getEscapedValue('string', $value);
                }
            }
        }
        $sql = strtr($sql, $key_array);

        return $sql;
    }

    protected function getEscapedValue(string $type, $value)
    {
        $escValue = "";
        switch ($type) {
            case "string":
                if (is_null($value)) {
                    $escValue = " null ";
                } else if (is_array($value)) {
                    $tmpValue = array();
                    foreach ($value as $v) {
                        if (is_null($v)) {
                            $tmpValue[] = " null ";
                        } else {
                            $tmpValue[] = "" . $this->conn->escapedString($v) . "";
                        }
                    }
                    $escValue = implode(",", $tmpValue);
                } else {
                    $escValue = "" . $this->conn->escapedString($value) . "";
                }
                break;
            case "array":
                $tmpValue = array();
                foreach ($value as $v) {
                    if (is_null($v)) {
                        $tmpValue[] = " null ";
                    } else {
                        $tmpValue[] = "" . $this->conn->escapedString($v) . "";
                    }
                }
                $escValue = implode(",", $tmpValue);
                break;
            case "int":
                if (is_null($value)) {
                    $escValue = " null ";
                } else if (is_array($value)) {
                    $tmpValue = array();
                    foreach ($value as $v) {
                        if (is_null($v)) {
                            $tmpValue[] = " null ";
                        } else {
                            $tmpValue[] = " " . intval($v) . " ";
                        }
                    }
                    $escValue = implode(",", $tmpValue);
                } else {
                    $escValue = intval($value);
                }
                break;
            case "raw":
                $escValue = $value;
                break;
        }
        return $escValue;
    }

}
