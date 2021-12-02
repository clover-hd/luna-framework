<?php

namespace Luna\Framework\Database\Migration\Console;

use Console_CommandLine;
use Console_CommandLine_Result;
use Exception;
use Luna\Framework\Application\Application;
use Luna\Framework\Console\Command;
use Luna\Framework\Database\DataSource;
use Luna\Framework\Http\Request;
use Symfony\Component\Yaml\Yaml;

class MakeMigrationCommand extends Command
{
    /**
     * DB接続オブジェクト
     *
     * @var Luna\Framework\Database\Connection
     */
    private $connection;

    /**
     * コマンド名
     *
     * @var string
     */
    private $commandName = '';
    /**
     * 引数パース結果
     *
     * @var Console_CommandLine_Result
     */
    private $command = null;

    protected function parseOptions()
    {
        $parser = new Console_CommandLine(
            [
                'description' => 'make_migration command',
                'version' => '1.0.0',
            ]
        );

        // make migration
        $makeMigrationParser = $parser->addCommand(
            'make_migration',
            [
                'description' => 'make_migration command',
                'version' => '1.0.0',
            ]
        );


        // create table
        $createTableParser = $makeMigrationParser->addCommand(
            'create_table',
            [
                'description' => 'create table command',
                'version' => '1.0.0',
            ]
        );

        $createTableParser->addArgument(
            'tablename',
            [
                'multiple' => false
            ]
        );

        // add column
        $addColumnParser = $makeMigrationParser->addCommand(
            'add_column',
            [
                'description' => 'add column command',
                'version' => '1.0.0',
            ]
        );

        // $addColumnParser->addOption(
        //     'tablename',
        //     [
        //         'short_name' => '-t',
        //         'long_name' => '--tablename',
        //         'description' => 'tablename',
        //         'action' => 'StoreString',
        //     ]
        // );

        $addColumnParser->addArgument(
            'tablename',
            [
                'multiple' => false
            ]
        );
        $addColumnParser->addArgument(
            'columnname',
            [
                'multiple' => false
            ]
        );
        $addColumnParser->addArgument(
            'type',
            [
                'multiple' => false
            ]
        );

        $result = $parser->parse();
        $this->commandName = $result->command->command_name;
        $this->command = $result->command->command;
    }

    public function handle(Request $request)
    {
        $this->parseOptions();
        
        switch ($this->commandName) {
            case 'create_table':
                $this->createTable();
                break;
            case 'add_column':
                $this->addColumn();
                break;
        }
        

        // $result = $parser->parse();
        // if (isset($result->options['down'])) {
        //     $mode = 'down';
        //     $step = $result->options['down'];
        // } else if (isset($result->options['up'])) {
        //     $mode = 'up';
        //     $step = $result->options['up'];
        // } else {
        //     $mode = 'up';
        //     $step = PHP_INT_MAX;
        // }
        // $this->connection = DataSource::getDataSource();
        // $migration = $this->connection->getMigration();
        // try {
        //     $migration->migrations($this->application, $this->connection, $mode, $step);
        // } catch (Exception $ex) {
        //     echo $ex->getMessage();
        // }
    }

    /**
     * 出力するマイグレーションファイルのprefixシリアル値を返す
     *
     * @return string
     */
    protected function getMigrationSerial()
    {
        $ymd = date('Ymd');

        $projectPath = Application::getInstance()->getProjectPath();
        $migrationPath = "{$projectPath}/db/migrations/";

        $files = glob("{$migrationPath}{$ymd}*.yml");

        $max = 0;

        foreach ($files as $file) {
            echo $file . "\n";
            if (preg_match('/([0-9]{8})([0-9]{6})\_(.*)\.(yml|yaml)/', $file, $match) === 1) {
                $max = max($max, intval($match[2]));
            }
        }
        
        return sprintf("%s%06d", $ymd, $max + 1);
    }

    /**
     * CREATE TABLEのyamlファイルを作成する
     *
     * @return void
     */
    protected function createTable()
    {
        $tablename = "{$this->command->args['tablename']}";

        $data = [
            'version' => '1.0',
            'up' => [
                'create' => [
                    'table' => [
                        $tablename => [
                            'engine' => 'InnoDB',
                            'primary_key' => [
                                'id'
                            ],
                            'index' => [
                                [
                                    'name' => 'idx_xxxx',
                                    'type' => 'UNIQUE',
                                    'columns' => [
                                        'id',
                                        'name'
                                    ]
                                ]
                            ],
                            'columns' => [
                                [
                                    'name' => 'id',
                                    'type' => 'bigint',
                                    'increment' => true,
                                    'comment' => 'ID'
                                ],
                                [
                                    'name' => 'name',
                                    'type' => 'varchar',
                                    'size' => '50',
                                    'not_null' => true
                                ],
                                [
                                    'name' => 'created_at',
                                    'type' => 'datetime',
                                    'default' => 'current_timestamp',
                                    'comment' => 'regist datetime'
                                ],
                                [
                                    'name' => 'updated_at',
                                    'type' => 'datetime',
                                    'default' => 'current_timestamp',
                                    'on_update' => 'current_timestamp',
                                    'comment' => 'update datetime'
                                ],
                                [
                                    'name' => 'deleted_at',
                                    'type' => 'datetime',
                                    'default' => null,
                                    'not_null' => true,
                                    'comment' => 'delete datetime'
                                ],
                                [
                                    'name' => 'delete_flag',
                                    'type' => 'char',
                                    'size' => '1',
                                    'default' => '0',
                                    'comment' => 'delete flag'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'down' => [
                'drop' => [
                    'table' => [
                        $tablename
                    ]
                ]
            ]
        ];

        
        $yaml = Yaml::dump($data, 10, 2);

        $prefixSerial = $this->getMigrationSerial();
        $projectPath = Application::getInstance()->getProjectPath();
        $migrationPath = "{$projectPath}/db/migrations/";
        $yamlFilename = "{$prefixSerial}_{$tablename}.yml";
        $yamlPath = "{$migrationPath}{$yamlFilename}";
        file_put_contents($yamlPath, $yaml);

        echo "Make migration file {$yamlFilename}\n";
    }

    protected function addColumn() {
        
        $tablename = "{$this->command->args['tablename']}";
        $columnname = "{$this->command->args['columnname']}";
        $type = "{$this->command->args['type']}";

        $data = [
            'up' => [
                'add' => [
                    'column' => [
                        $tablename => [
                            [
                                'name' => $columnname,
                                'type' => $type
                            ]
                        ]
                    ]
                ],
            'down' => [
                    'drop' => [
                        $tablename => [
                            $columnname
                        ]
                    ]
                ]
            ]
        ];
        
        $yaml = Yaml::dump($data, 10, 2);

        $prefixSerial = $this->getMigrationSerial();
        $projectPath = Application::getInstance()->getProjectPath();
        $migrationPath = "{$projectPath}/db/migrations/";
        $yamlFilename = "{$prefixSerial}_{$tablename}.yml";
        $yamlPath = "{$migrationPath}{$yamlFilename}";
        file_put_contents($yamlPath, $yaml);

        echo "Make migration file {$yamlFilename}\n";
    }
}
