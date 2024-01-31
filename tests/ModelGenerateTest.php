<?php

namespace Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use WenGen\Commands\ModelGen;

/**
 * @date: 15:41 2024/1/29
 */
class ModelGenerateTest extends TestCase
{
    public function testGenerate()
    {
        $this->artisan(ModelGen::class, ['tableName' => 'test_comments']);
        $this->assertTrue(true);
    }

    public function testGetAllTable()
    {
        var_dump(Arr::pluck(Schema::getTables(), null, 'name'));
        var_dump(Arr::pluck(Schema::getColumns('test_comments'), null, 'name'));
        $this->assertTrue(true);
    }
}
