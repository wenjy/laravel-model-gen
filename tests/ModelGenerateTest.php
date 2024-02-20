<?php

namespace Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use WenGen\Commands\ModelGen;

/**
 * @date: 15:41 2024/1/29
 */
class ModelGenerateTest extends TestCase
{
    public function testGenerate()
    {
        $this->artisan(ModelGen::class, ['--table' => 'test_comments']);
        $this->assertTrue(true);
    }

    public function testGenerateByNamespace()
    {
        $this->artisan(ModelGen::class, ['--table' => 'test_comments', '--model' => 'App\Test\TestComment']);
        $this->assertTrue(true);
    }

    public function testGetAllTable()
    {
        var_dump(Arr::pluck(Schema::getTables(), null, 'name'));
        var_dump(Arr::pluck(Schema::getColumns('test_comments'), null, 'name'));
        $this->assertTrue(true);
    }

    public function testGetTables()
    {
        $sql = sprintf(
            'select table_name as `name`, (data_length + index_length) as `size`, '
            .'table_comment as `comment`, engine as `engine`, table_collation as `collation` '
            ."from information_schema.tables where table_schema = '%s' and table_type in ('BASE TABLE', 'SYSTEM VERSIONED') "
            .'order by table_name', DB::connection()->getDatabaseName());
        $tables = DB::select($sql);
        foreach ($tables as &$table) {
            $table = (array)$table;
        }
         var_dump($tables);
        $this->assertTrue(true);
    }

    public function testGetColumns()
    {
        $sql = sprintf(
            'select column_name as `name`, data_type as `type_name`, column_type as `type`, '
            .'collation_name as `collation`, is_nullable as `nullable`, '
            .'column_default as `default`, column_comment as `comment`, extra as `extra` '
            ."from information_schema.columns where table_schema = '%s' and table_name = '%s' "
            .'order by ordinal_position asc', DB::connection()->getDatabaseName(), 'test_comments');

        $columns = DB::select($sql);
        foreach ($columns as &$column) {
            $column = (array)$column;
            $column['nullable'] = $column['nullable'] == 'YES';
            $column['auto_increment'] = $column['extra'] == 'auto_increment';
            unset($column['extra']);
        }
        var_dump($columns);
        $this->assertTrue(true);
    }
}
