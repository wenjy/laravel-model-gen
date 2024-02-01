<?php

namespace WenGen\Generators\Model;

use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use WenGen\CodeFile;
use WenGen\Generator as BaseGenerator;

/**
 * @date: 14:59 2024/1/29
 */
class Generator extends BaseGenerator
{

    /**
     * 数据库连接名称
     * @var string|null
     */
    public $db = null;

    /**
     * @var string
     */
    public $ns = 'App\Models';

    /**
     * @var string
     */
    public $tableName;

    /**
     * @var string[]|null
     */
    protected $tableNames;

    /**
     * @var array
     */
    protected array $tableSchemas = [];

    /**
     * @var array
     */
    protected array $tableColumns = [];

    /**
     * @var string
     */
    public $modelClass;

    public $useSchemaName = true;

    /**
     * @var string[]
     */
    protected $classNames = [];

    public function getName()
    {
        return 'Model Generator';
    }

    public function __construct()
    {
        parent::__construct();
        Schema::connection($this->db);
    }

    /**
     * @return array
     */
    public function generate()
    {
        $files = [];
        foreach ($this->getTableNames() as $tableName) {
            // model:
            $modelClassName = $this->generateClassName($tableName);
            $tableSchema = $this->getTableSchema($tableName);
            $tableColumn = $this->getTableColumn($tableName);
            $params = [
                'tableName' => $tableName,
                'modelClassName' => $modelClassName,
                'tableSchema' => $tableSchema,
                'properties' => $this->generateProperties($tableColumn),
            ];
            $path = app_path('Models') . DIRECTORY_SEPARATOR . $modelClassName . '.php';
            if (app()->environment('testing')) {
                $path = __DIR__ . '/../../../tests/test_models/' . $modelClassName . '.php';
            }
            $files[] = new CodeFile(
                $path,
                $this->render('model', $params)
            );
        }

        return $files;
    }

    /**
     * @param string $template
     * @param array $params
     * @return string
     */
    protected function render(string $template, array $params = []): string
    {
        $params['generator'] = $this;

        $path = $this->getTemplatePath() . DIRECTORY_SEPARATOR . $template . '.blade.php';
        return View::file($path, $params)->render();
    }

    /**
     * @return Connection
     */
    protected function getDbConnection()
    {
        return DB::connection($this->db);
    }

    /**
     * Generates the properties for the specified table.
     * @param array $columns the table columns
     * @return array the generated properties (property => type)
     */
    protected function generateProperties(array $columns)
    {
        $properties = [];
        foreach ($columns as $column) {
            if (str_contains($column['type_name'], 'int')) {
                $type = 'int';
            } elseif ($column['name'] == 'created_at' || $column['name'] == 'updated_at') {
                $type = '\Illuminate\Support\Carbon';
            } else {
                $type = 'string';
            }

            if ($column['nullable']){
                $type .= '|null';
            }

            $properties[$column['name']] = [
                'type' => $type,
                'name' => $column['name'],
                'comment' => $column['comment'],
                'auto_increment' => $column['auto_increment'],
            ];
        }
        return $properties;
    }

    /**
     * @return array
     */
    protected function getTableSchemas(): array
    {
        if (empty($this->tableSchemas)) {
            $this->tableSchemas = Arr::pluck($this->getTables(), null, 'name');
        }

        return $this->tableSchemas;
    }

    /**
     * @return array
     */
    protected function getTables(): array
    {
        if (version_compare('10.0.0', app()->version()) === 1) {
            $sql = sprintf(
                'select table_name as `name`, (data_length + index_length) as `size`, '
                .'table_comment as `comment`, engine as `engine`, table_collation as `collation` '
                ."from information_schema.tables where table_schema = '%s' and table_type in ('BASE TABLE', 'SYSTEM VERSIONED') "
                .'order by table_name', $this->getDbConnection()->getDatabaseName());
            $tables = DB::select($sql);
            foreach ($tables as &$table) {
                $table = (array)$table;
            }
            return $tables;
        }
        return Schema::getTables();
    }

    /**
     * @param string $table
     * @return array
     */
    protected function getTableSchema(string $table): array
    {
        return $this->getTableSchemas()[$table] ?? [];
    }

    /**
     * @param string $table
     * @return array
     */
    protected function getTableColumn(string $table): array
    {
        if (!empty($this->tableColumns[$table])) {
            return $this->tableColumns[$table];
        }
        $this->tableColumns[$table] = Arr::pluck($this->getColumns($table), null, 'name');
        return $this->tableColumns[$table];
    }

    /**
     * @param string $table
     * @return array
     */
    protected function getColumns(string $table): array
    {
        if (version_compare('10.0.0', app()->version()) === 1) {
            $sql = sprintf(
                'select column_name as `name`, data_type as `type_name`, column_type as `type`, '
                .'collation_name as `collation`, is_nullable as `nullable`, '
                .'column_default as `default`, column_comment as `comment`, extra as `extra` '
                ."from information_schema.columns where table_schema = '%s' and table_name = '%s' "
                .'order by ordinal_position asc', DB::connection()->getDatabaseName(), $table);

            $columns = DB::select($sql);
            foreach ($columns as &$column) {
                $column = (array)$column;
                $column['nullable'] = $column['nullable'] == 'YES';
                $column['auto_increment'] = $column['extra'] == 'auto_increment';
                unset($column['extra']);
            }
            return $columns;
        }
        return Schema::getColumns($table);
    }

    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     */
    protected function getTableNames()
    {
        if ($this->tableNames !== null) {
            return $this->tableNames;
        }
        $tableNames = [];
        if (strpos($this->tableName, '*') !== false) {
            if (($pos = strrpos($this->tableName, '.')) !== false) {
                $schema = substr($this->tableName, 0, $pos);
                $pattern = '/^' . str_replace('*', '\w+', substr($this->tableName, $pos + 1)) . '$/';
            } else {
                $schema = '';
                $pattern = '/^' . str_replace('*', '\w+', $this->tableName) . '$/';
            }

            foreach ($this->getTableSchemas() as $table) {
                if (preg_match($pattern, $table['name'])) {
                    $tableNames[] = $schema === '' ? $table['name'] : ($schema . '.' . $table['name']);
                }
            }
        } elseif (!empty($this->getTableSchema($this->tableName))) {
            $tableNames[] = $this->tableName;
            $this->classNames[$this->tableName] = $this->modelClass;
        }

        return $this->tableNames = $tableNames;
    }

    /**
     * Generates a class name from the specified table name.
     * @param string $tableName the table name (which may contain schema prefix)
     * @param bool $useSchemaName should schema name be included in the class name, if present
     * @return string the generated class name
     */
    protected function generateClassName($tableName, $useSchemaName = null)
    {
        if (isset($this->classNames[$tableName])) {
            return $this->classNames[$tableName];
        }

        $schemaName = '';
        $fullTableName = $tableName;
        if (($pos = strrpos($tableName, '.')) !== false) {
            if (($useSchemaName === null && $this->useSchemaName) || $useSchemaName) {
                $schemaName = substr($tableName, 0, $pos) . '_';
            }
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$db->getTablePrefix()}(.*?)$/";
        $patterns[] = "/^(.*?){$db->getTablePrefix()}$/";
        if (strpos($this->tableName, '*') !== false) {
            $pattern = $this->tableName;
            if (($pos = strrpos($pattern, '.')) !== false) {
                $pattern = substr($pattern, $pos + 1);
            }
            $patterns[] = '/^' . str_replace('*', '(\w+)', $pattern) . '$/';
        }
        $className = $tableName;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $className = $matches[1];
                break;
            }
        }
        $schemaName = ctype_upper(preg_replace('/[_-]/', '', $schemaName)) ? strtolower($schemaName) : $schemaName;
        $className = ctype_upper(preg_replace('/[_-]/', '', $className)) ? strtolower($className) : $className;
        $className = Pluralizer::singular($className);
        $this->classNames[$fullTableName] = Str::studly($schemaName . $className);


        return $this->classNames[$fullTableName];
    }
}
