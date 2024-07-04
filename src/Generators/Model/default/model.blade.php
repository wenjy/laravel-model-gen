<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/** @var WenGen\Generators\Model\Generator $generator */
/** @var string $modelClassName */
/** @var string $tableName */
/** @var array $tableSchema */
/** @var array $properties */

echo "<?php\n";
?>
/**
 * @date: <?= date('Y-m-d H:i:s') . "\n" ?>
 */

namespace <?= $generator->ns ?>;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
<?php if(!empty($properties['created_at'])): ?>
use Illuminate\Support\Carbon;
<?php endif; ?>

/**
 * This is the model class for table "<?= $tableSchema['comment'] ?>".
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}" . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
 */
class <?= $modelClassName ?> extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = '<?= $tableName ?>';
<?php if(empty($properties['created_at'])): ?>
    public $timestamps = false;
<?php endif; ?>

    /**
     * @var array
     */
    protected $fillable = [
<?php foreach ($properties as $property => $data): ?>
<?php if(!($data['auto_increment'] === true || $property == 'created_at' || $property == 'updated_at')): ?>
        '<?= $property ?>',
<?php endif; ?>
<?php endforeach; ?>
    ];

    /**
     * @var array
     */
    protected $casts = [
<?php foreach ($properties as $property2 => $data2): ?>
<?php if(str_contains($data2['type'], 'array')): ?>
        '<?= $property2 ?>' => 'array',
<?php endif; ?>
<?php endforeach; ?>
    ];
}
