<?php

namespace Luna\Framework\Database\Migration\Console;

use Console_CommandLine;
use Exception;
use Luna\Framework\Console\Command;
use Luna\Framework\Database\DataSource;
use Luna\Framework\Http\Request;

class MigrationCommand extends Command
{
    /**
     * DB接続オブジェクト
     *
     * @var Luna\Framework\Database\Connection
     */
    private $connection;

    public function handle(Request $request)
    {
        $parser = new Console_CommandLine(
            [
                'description' => 'migration command',
                'version' => '1.0.0',
            ]
        );
        $parser->addOption(
            'down',
            [
                'short_name' => '-d',
                'long_name' => '--down',
                'description' => 'migration rollbacks',
                'action' => 'StoreInt',
            ]
        );
        $parser->addOption(
            'up',
            [
                'short_name' => '-u',
                'long_name' => '--up',
                'description' => 'run migration',
                'action' => 'StoreInt',
            ]
        );
        $result = $parser->parse();
        if (isset($result->options['down'])) {
            $mode = 'down';
            $step = $result->options['down'];
        } else if (isset($result->options['up'])) {
            $mode = 'up';
            $step = $result->options['up'];
        } else {
            $mode = 'up';
            $step = PHP_INT_MAX;
        }
        $this->connection = DataSource::getDataSource();
        $migration = $this->connection->getMigration();
        try {
            $migration->migrations($this->application, $this->connection, $mode, $step);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}
