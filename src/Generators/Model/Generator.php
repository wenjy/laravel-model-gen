<?php

namespace WenGen\Generators\Model;

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

    public $db = null;

    public $ns = 'App\Models';

    /**
     * @var string
     */
    public $tableName;

    /**
     * @var string[]|null
     */
    protected $tableNames;

    protected array $tableSchemas = [];
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

    protected function render($template, $params = [])
    {
        $params['generator'] = $this;

        $path = $this->getTemplatePath() . DIRECTORY_SEPARATOR . $template . '.blade.php';
        return View::file($path, $params)->render();
    }

    protected function getDbConnection()
    {
        return DB::connection($this->db);
    }

    protected function generateProperties(array $columns)
    {
        $properties = [];
        foreach ($columns as $column) {
            if (str_contains($column['type_name'], 'int')) {
                $type = 'int';
            } elseif ($column['name'] == 'created_at' || $column['name'] == 'updated_at') {
                $type = '\Carbon\Carbon';
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

    protected function getTableSchemas(): array
    {
        if (empty($this->tableSchemas)) {
            $this->tableSchemas = Arr::pluck(Schema::getTables(), null, 'name');
        }

        return $this->tableSchemas;
    }

    protected function getTableSchema(string $table): array
    {
        return $this->getTableSchemas()[$table] ?? [];
    }

    protected function getTableColumn(string $table): array
    {
        if (!empty($this->tableColumns[$table])) {
            return $this->tableColumns[$table];
        }
        $this->tableColumns[$table] = Arr::pluck(Schema::getColumns($table), null, 'name');
        return $this->tableColumns[$table];
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
