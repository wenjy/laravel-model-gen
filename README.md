## Laravel Model Generator

参考 YII的 model generator

### 使用说明

- 引入包
```shell
composer require --dev wenjy/laravel-model-gen
```

- 执行生成
```shell
php artisan gen:model [--table=] [--model=] [--conn=] [--ns=]
```

参数表名：`--table`
不传入表名，默认生成所有表的model，例如 `--table=test_comments`

参数模型类名：`--model`，例如 `--model=TestAbc`
不传入模型类名，使用表名单数大驼峰命名方式

参数数据库连接名：`--conn`，例如 `--conn=test_conn`
不传入数据库连接名，使用默认的数据库连接

参数命名空间：`--ns`，例如 `--ns=App\Models\Tests`
不传入命名空间，默认使用 `App\Models`，

- 举例

test_comments 表结构为：
```sql
CREATE TABLE `test_comments` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '评论标题',
    `post_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '文章ID',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `aaa` decimal(10,0) NOT NULL DEFAULT '0',
    `v` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `w` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'aaaa',
    `a` text COLLATE utf8mb4_unicode_ci COMMENT 'json aa',
    `json1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'json 1',
    `json2` json DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='测试评论表';
```

执行 `php artisan gen:model --table=test_comments`

会生成文件`TestComment`
```php
<?php
/**
 * @date: 2024-02-04 05:39:19
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * This is the model class for table "测试评论表".
 * @property int $id
 * @property string $title 评论标题
 * @property int $post_id 文章ID
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $aaa
 * @property string|null $v
 * @property string $w aaaa
 * @property array|null $a json aa
 * @property array|null $json1 json 1
 * @property array|null $json2
 */
class TestComment extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'test_comments';

    /**
     * @var array
     */
    protected $fillable = [
        'title',
        'post_id',
        'aaa',
        'v',
        'w',
        'a',
        'json1',
        'json2',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'a' => 'array',
        'json1' => 'array',
        'json2' => 'array',
    ];
}

```

### 注意

laravel 8、laravel 9 只支持MySql（其它的数据库没有把SQL复制过来），laravel 10原生支持`Schema::getColumns` `Schema::getTables()`
