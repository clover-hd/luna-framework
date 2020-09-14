<?php

namespace Luna\Framework\Fixture;

use Luna\Framework\Console\Command;
use Luna\Framework\Http\Request;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\Yaml\Yaml;

class FixtureCommand extends Command
{
    private $mode;

    public function parseOptions()
    {
        $opts = getopt("m");
        foreach ($opts as $opt => $value) {
            switch ($opt) {
                case 'm':
                    $this->mode = $value;
                    break;
            }
        }
        // オプション未設定の場合のデフォルト値
        if (empty($this->mode)) {
            $configParams = $this->application->getConfig()->getConfigParams();
            $this->mode = $configParams['system']['environment'];
        }
    }

    public function handle(Request $request)
    {
        $this->parseOptions();
        $projectPath = $this->application->getProjectPath();
        $fixturePath = "{$projectPath}/db/fixtures/{$this->mode}/";
        if (is_dir($fixturePath) === false) {
            echo "Fixture not found ({$fixturePath}).\n";
            exit(1);
        }

        $fixtureDir = dir($fixturePath);

        try {
            while (false !== ($entry = $fixtureDir->read())) {
                $fixtureFile = "{$fixturePath}/{$entry}";
                if (is_file($fixtureFile)) {
                    $fixtureData = Yaml::parseFile("{$fixtureFile}");
                    $this->insertTable($fixtureData);
                }
            }
            echo "Success fixtures.\n";
        } catch (Exception $ex) {
            echo "Error: " . $ex->getMessage() . "\n";
        }
        $fixtureDir->close();
    }

    private function insertTable(array $fixtureData)
    {
        $this->beginTransaction();

        $model = FixtureModel::instance();
        foreach ($fixtureData as $tablename => $records) {
            $model->setTablename($tablename);
            foreach ($records as $record) {
                foreach ($record as $column => $value) {
                    $model->$column = $value;
                }
                $model->save();
            }
        }
        $this->commit();
    }
}