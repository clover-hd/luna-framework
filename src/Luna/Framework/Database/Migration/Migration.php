<?php

namespace Luna\Framework\Database\Migration;

use Luna\Framework\Application\Application;
use Luna\Framework\Database\Connection;
use Luna\Framework\Database\Migration\Model\DBMigration;
use Luna\Framework\Database\ORM\Schema;
use Symfony\Component\Yaml\Yaml;

/**
 * DBマイグレーションを実行するクラス
 */
class Migration
{

    /**
     * DBマイグレーション用のテーブルを作成する
     *
     * @param Application $application
     * @param Connection $connection
     * @return void
     */
    public function createMigrationTable(Application $application, Connection $connection)
    {
        $migrationDir = __DIR__ . '/migrations/';

        $schema = Schema::getInstance($connection->getDatasourceName());
        if ($schema->hasTable('db_migrations')) {
            // マイグレーションレコード取得、なければ作成
            $migration = DBMigration::instance()
                ->alive()
                ->findOrCreate(
                    'application_name = :application_name',
                    [
                        'application_name' => 'migration',
                    ]
                );
        } else {
            $migration = DBMigration::instance();
        }
        $migrationList = $this->getUpMigrationFiles($migrationDir, 'migration', $connection, \PHP_INT_MAX, $migration->migration_code);

        // マイグレーション実行
        $migrationCode = $this->migrationByFiles($migrationList, 'migration', $connection);
    }

    /**
     * アプリケーション用のDBをマイグレーションする
     * マイグレーション用のテーブルが無い場合は作成する
     *
     * @param Application $application
     * @param Connection $connection
     * @return void
     */
    public function migrations(Application $application, Connection $connection, string $mode = 'up', int $step = \PHP_INT_MAX)
    {
        $schema = Schema::getInstance($connection->getDatasourceName());
        if ($schema->hasTable('db_migrations') === false) {
            $this->createMigrationTable($application, $connection);
        }

        // マイグレーションレコード取得、なければ作成
        $migration = DBMigration::instance()
            ->alive()
            ->findOrCreate(
                'application_name = :application_name',
                [
                    'application_name' => 'application',
                ]
            );

        // マイグレーションを実行するファイルリストを取得する
        $projectPath = $application->getProjectPath();
        $migrationDir = "{$projectPath}/db/migrations/";
        if ($mode == 'up') {
            $migrationList = $this->getUpMigrationFiles($migrationDir, 'application', $connection, $step, $migration->migration_code);
        } else if ($mode == 'down') {
            $migrationList = $this->getDownMigrationFiles($migrationDir, 'application', $connection, $step, $migration->migration_code);
        }

        // マイグレーション実行
        $migrationCode = $this->migrationByFiles($migrationList, 'application', $connection, $mode);
    }

    /**
     * 実行するマイグレーションファイルのリストを取得する
     *
     * @param string $migrationDir マイグレーションディレクトリ
     * @param string $applicationName アプリケーション名
     * @param Connection $connection DB接続
     * @param int $step 実行するステップ数
     * @param string $latestMigrationCode 過去に実行した最新のマイグレーションコード(これよりも大きいマイグレーションファイルが処理される)
     * @return array
     */
    public function getUpMigrationFiles(string $migrationDir, string $applicationName, Connection $connection, int $step = \PHP_INT_MAX, string $latestMigrationCode = null)
    {
        $currentMigrationCode = '';
        // migrationファイルリストをファイル名でソートして取得
        $files = scandir($migrationDir, \SCANDIR_SORT_ASCENDING);
        $migrationList = [];
        $currentStep = 0;
        // migrationファイルを順番に処理する
        foreach ($files as $filename) {
            // migrationファイルのフルパス
            $fullPath = "{$migrationDir}/$filename";
            // 「ファイル」か
            if (\is_file($fullPath)) {
                // パスの情報を取得し拡張子チェックを行う
                $pathInfo = pathinfo($fullPath);
                if ($pathInfo['extension'] == 'yml' || $pathInfo['extension'] == 'yaml') {
                    // YAMLファイルであればファイル読み込み
                    echo "Read migration file {$filename}\n";
                    $yaml = Yaml::parseFile($fullPath);
                    // migration番号を取得する
                    list($migrationCode) = \preg_split('/[\_\.]/', $filename);
                    if ((empty($latestMigrationCode) || $latestMigrationCode < $migrationCode) && $currentStep < $step) {
                        $migrationList[] = [
                            'migration_code' => $migrationCode,
                            'migration_file' => $filename,
                            'migration_path' => $fullPath,
                        ];
                        $currentStep++;
                    } else {
                        echo "skip migration {$migrationCode}\n";
                    }
                } else {
                    // ファイルがYAML形式ではない
                    echo "Not yaml or yml file {$filename}\n";
                }
            } else {
                // ファイルではない
                echo "Not file {$filename}\n";
            }
        }
        return $migrationList;
    }

    /**
     * 実行するマイグレーションファイルのリストを取得する
     *
     * @param string $migrationDir マイグレーションディレクトリ
     * @param string $applicationName アプリケーション名
     * @param Connection $connection DB接続
     * @param int $step 実行するステップ数
     * @param string $latestMigrationCode 過去に実行した最新のマイグレーションコード(これよりも大きいマイグレーションファイルが処理される)
     * @return array
     */
    public function getDownMigrationFiles(string $migrationDir, string $applicationName, Connection $connection, int $step = \PHP_INT_MAX, string $latestMigrationCode = null)
    {
        $currentMigrationCode = '';
        // migrationファイルリストをファイル名でソートして取得
        $files = scandir($migrationDir, \SCANDIR_SORT_DESCENDING);
        $migrations = [];
        $currentStep = 0;
        // migrationファイルを順番に処理する
        foreach ($files as $filename) {
            // migrationファイルのフルパス
            $fullPath = "{$migrationDir}/$filename";
            // 「ファイル」か
            if (\is_file($fullPath)) {
                // パスの情報を取得し拡張子チェックを行う
                $pathInfo = pathinfo($fullPath);
                if ($pathInfo['extension'] == 'yml' || $pathInfo['extension'] == 'yaml') {
                    // YAMLファイルであればファイル読み込み
                    echo "Read migration file {$filename}\n";
                    $yaml = Yaml::parseFile($fullPath);
                    // migration番号を取得する
                    list($migrationCode) = \preg_split('/[\_\.]/', $filename);
                    $migrations[] = [
                        'migration_code' => $migrationCode,
                        'migration_file' => $filename,
                        'migration_path' => $fullPath,
                    ];
                } else {
                    // ファイルがYAML形式ではない
                    echo "Not yaml or yml file {$filename}\n";
                }
            } else {
                // ファイルではない
                echo "Not file {$filename}\n";
            }
        }
        $migrationList = [];
        foreach ($migrations as $idx => $migration) {
            $migrationCode = $migration['migration_code'];
            if ((empty($latestMigrationCode) || $latestMigrationCode >= $migrationCode) && $currentStep < $step) {
                if (count($migrations) > $idx + 1 ) {
                    $migrationList[] = [
                        'migration_code' => $migrations[$idx + 1]['migration_code'],
                        'migration_file' => $migration['migration_file'],
                        'migration_path' => $migration['migration_path'],
                    ];
                } else {
                    $migrationList[] = [
                        'migration_code' => '',
                        'migration_file' => $migration['migration_file'],
                        'migration_path' => $migration['migration_path'],
                    ];
                }
                $currentStep++;
            }
        }
        return $migrationList;
    }

    public function migrationByFiles(array $migrationList, string $applicationName, Connection $connection, string $mode = 'up')
    {
        $currentMigrationCode = '';

        foreach ($migrationList as $idx => $migration) {
            $connection->beginTransaction();
            $migrationCode = $migration['migration_code'];
            $migrationFile = $migration['migration_file'];
            $migrationPath = $migration['migration_path'];

            // YAMLファイルであればファイル読み込み
            echo "Read migration file {$migrationFile}\n";
            $yaml = Yaml::parseFile($migrationPath);
            if (isset($yaml[$mode])) {
                $migrationYaml = $yaml[$mode];

                if (isset($migrationYaml['create'])) {
                    // 作成
                    echo "migration {$migrationCode} --- create\n";
                    $this->create($migrationYaml, $connection);
                    echo "migration {$migrationCode} --- complete\n";
                    $currentMigrationCode = $migrationCode;

                } else if (isset($migrationYaml['change'])) {
                    // 変更
                    echo "migration {$migrationCode} --- change\n";
                    $this->change($migrationYaml, $connection);
                    echo "migration {$migrationCode} --- complete\n";
                    $currentMigrationCode = $migrationCode;
                } else if (isset($migrationYaml['add'])) {
                    // 追加
                    echo "migration {$migrationCode} --- add\n";
                    $this->add($migrationYaml, $connection);
                    echo "migration {$migrationCode} --- complete\n";
                    $currentMigrationCode = $migrationCode;
                } else if (isset($migrationYaml['drop'])) {
                    // 削除
                    echo "migration {$migrationCode} --- dtop table\n";
                    $this->drop($migrationYaml, $connection);
                    echo "migration {$migrationCode} --- complete\n";
                    $currentMigrationCode = $migrationCode;
                }
            } else {
                // YAMLファイル内にupまたはdownが無い場合は何もせずに正常終了
                $currentMigrationCode = $migrationCode;
            }

            // ファイルあたりのマイグレーションが正常終了したらDBにマイグレーションコードを保存
            $migration = DBMigration::instance()
                ->alive()
                ->findOrCreate(
                    'application_name = :application_name',
                    [
                        'application_name' => 'application',
                    ]
                );
            $migration->application_name = $applicationName;
            $migration->migration_code = $currentMigrationCode;
            $migration->save();

            $connection->commit();
        }

        return $currentMigrationCode;
    }

    protected function create(array $createYaml, Connection $connection)
    {
        if (isset($createYaml['create']['table'])) {
            $this->createTable($createYaml['create']['table'], $connection);
        }
        if (isset($createYaml['create']['view'])) {
            $this->createView($createYaml['create']['view'], $connection);
        }
    }

    protected function change(array $changeYaml, Connection $connection)
    {
        if (isset($changeYaml['change']['table'])) {
            $this->changeTable($changeYaml['change']['table'], $connection);
        }
        if (isset($changeYaml['change']['view'])) {
            $this->changeView($changeYaml['change']['view'], $connection);
        }
    }

    protected function add(array $addYaml, Connection $connection)
    {
        if (isset($addYaml['add']['column'])) {
            $this->addColumn($addYaml['add']['column'], $connection);
        }
    }

    protected function drop(array $dropYaml, Connection $connection)
    {
        if (isset($dropYaml['drop']['table'])) {
            $this->dropTable($dropYaml['drop']['table'], $connection);
        }
        if (isset($dropYaml['drop']['column'])) {
            $this->dropColumn($dropYaml['drop']['column'], $connection);
        }
        if (isset($dropYaml['drop']['view'])) {
            $this->dropView($dropYaml['drop']['view'], $connection);
        }
    }

    /**
     * テーブル作成
     *
     * @param array $createYaml
     * @return void
     */
    protected function createTable(array $createYaml, Connection $connection)
    {
    }

    /**
     * ビュー作成
     *
     * @param array $createYaml
     * @return void
     */
    protected function createView(array $createYaml, Connection $connection)
    {
    }

    /**
     * テーブルの変更
     *
     * @param array $changeYaml
     * @return void
     */
    protected function changeTable(array $changeYaml, Connection $connection)
    {
    }

    /**
     * ビューの変更
     *
     * @param array $createYaml
     * @return void
     */
    protected function changeView(array $createYaml, Connection $connection)
    {
    }

    /**
     * カラムの追加
     *
     * @param array $addYaml
     * @param Connection $connection
     * @return void
     */
    protected function addColumn(array $addYaml, Connection $connection)
    {
    }

    /**
     * テーブルの削除
     *
     * @param array $dropTableYaml
     * @param Connection $connection
     * @return void
     */
    protected function dropTable(array $dropTableYaml, Connection $connection)
    {
    }

    /**
     * ビューの削除
     *
     * @param array $dropViewYaml
     * @return void
     */
    protected function dropView(array $dropViewYaml, Connection $connection)
    {
    }

    /**
     * カラムの削除
     *
     * @param array $dropColumnYaml
     * @param Connection $connection
     * @return void
     */
    protected function dropColumn(array $dropColumnYaml, Connection $connection)
    {
    }

    /**
     * カラム情報からDB無いてぃぶの型定義文字列を返す
     *
     * @param array $column
     * @return string
     */
    protected function getDBNativeType($column)
    {
        return '';
    }
}
