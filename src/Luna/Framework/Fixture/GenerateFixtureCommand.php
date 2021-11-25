<?php

namespace Luna\Framework\Fixture;

use Console_CommandLine;
use Luna\Framework\Console\Command;
use Luna\Framework\Http\Request;
use PHPMailer\PHPMailer\Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;

class GenerateFixtureCommand extends Command
{
    private $mode;
    private $model;

    public function parseOptions()
    {

        $parser = new Console_CommandLine(
            [
                'description' => 'generate fixture command',
                'version' => '1.0.0',
            ]
        );

        $generateFixtureParser = $parser->addCommand(
            'generate_fixture',
            [
                'description' => 'generate fixture command',
                'version' => '1.0.0',
            ]
        );

        $generateFixtureParser->addOption(
            'mode',
            [
                'short_name' => '-m',
                'long_name' => '--mode',
                'description' => 'environment mode',
                'action' => 'StoreString',
            ]
        );

        $generateFixtureParser->addArgument(
            'models',
            [
                'multiple' => true
            ]
        );

        try {
            $result = $parser->parse();
            $this->mode = $result->command->options['mode'];
            // オプション未設定の場合のデフォルト値
            if (empty($this->mode)) {
                $configParams = $this->application->getConfig()->getConfigParams();
                $this->mode = $configParams['system']['environment'];
            }
            $this->models = $result->command->args['models'];
        } catch (Exception $ex) {
            $parser->displayError($ex->getMessage());
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

        foreach ($this->models as $modelName) {
            // 引数で指定されたモデル名から対象のクラスを探す
            // namespaceに"Model"が含まれていて指定されたモデル名と同じクラスファイル名
            $class = $this->getClassName($projectPath . '/app', $modelName);
            if (empty($class) === false) {
                
                // モデルをインスタンス化
                $model = new $class();

                // テーブル名
                $tablename = $model->getTableName();
                
                try {
        
                    $data = [];
        
                    $result = $model->all()->getRecordSet();
                    foreach ($result as $record) {
                        $data[$tablename][] = $record->toArray();
                    }
                    // Yamlテキスト作成
                    $yaml = Yaml::dump($data, 10, 2);
                    // ファイルに保存
                    file_put_contents($fixturePath . $tablename . '.yml', $yaml);
        
                    echo "generate {$tablename} fixture. \n";
                } catch (Exception $ex) {
                    echo "Error: " . $ex->getMessage() . "\n";
                }

            } else {
                // モデルがない
                echo "Not found model {$this->model}\n";
            }
        }
    }

    protected function getClassName($dir, $modelName)
    {
        $iterator = new RecursiveDirectoryIterator($dir);
        $iterator = new RecursiveIteratorIterator($iterator);
        foreach ($iterator as $fileinfo) {
            $path = $fileinfo->getPath();
            $filename = $fileinfo->getBasename('.php');
            if (strpos($filename, $modelName) !== false) {
                if (strpos($path, 'Model') !== false) {
                    $namespace = str_replace($dir, '', $path);
                    $namespace = str_replace("/", "\\", $namespace);
                    $class = $namespace . '\\' . $filename;
                    return "App{$class}";
                }
            }
        }

        return null;
    }
}