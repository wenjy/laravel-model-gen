## Laravel Model Generator

参考 YII的 model generator

### 使用说明

- 引入包
```shell
composer require --dev wenjy/laravel-model-gen
```

- 执行生成
```shell
php artisan gen:model [tableName] [modelClass]
```

参数表名：tableName
不传入表名，默认生成所有表的model，使用默认的数据库连接

参数模型类名：modelClass
不传入模型类名，使用表名单数大驼峰命名方式

- 举例

test_comments 表结构为：
```sql
CREATE TABLE `test_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '评论标题',
  `post_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '文章ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

执行 `php artisan gen:model test_comments`

会生成文件`TestComment`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This is the model class for table "测试评论表".
 * @property int $id
 * @property string $title 评论标题
 * @property int $post_id 文章ID
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class TestComment extends Model
{
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
        'created_at',
        'updated_at',
    ];
}

```

### 注意

laravel 8、laravel 9 只支持MySql（其它的数据库没有把SQL复制过来），laravel 10原生支持`Schema::getColumns` `Schema::getTables()`
