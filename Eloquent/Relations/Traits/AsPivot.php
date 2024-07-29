<?php
// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations\Traits;

/**
 * 数据透视
 */
trait AsPivot
{
	/**
	 * 父模型 外键
	 * 
	 * @var string
	 */
	protected $parentModelForeignKey;

	/**
	 * 关联模型 外键
	 * 
	 * @var string
	 */
	protected $associationModelForeignKey;

	/**
	 * 设置 数据透视表 模型实例的 键名
	 * 
	 * @param string $parentModelForeignKey
	 * @param string $associationModelForeignKey
	 * @return $this
	 */
	public function setPivotKeys(string $parentModelForeignKey, string $associationModelForeignKey)
	{
		$this->parentModelForeignKey = $parentModelForeignKey;

		$this->associationModelForeignKey = $associationModelForeignKey;

		return $this;
	}

	/**
	 * 新建 模型 实例
	 * 
	 * 填充属性值 需要转换
	 * 
	 * @param array $attributes
	 * @param string $table
	 * @param bool $timestamps
	 * @param bool $exists
     * @return static
	 */
	public static function fromAttributes(array $attributes, string $table, bool $timestamps = false, string $dateFormat = null, bool $exists = false)
	{
		$instance = new static;

		// 是否自动维护 时间戳
		$instance->timestamps = $timestamps;

		// 设置 日期时间 存储格式
		if (! is_null($dateFormat)) {
			$instance->setDateFormat($dateFormat);
		}

		// 填充属性
		$instance->fill($attributes)
					// 同步原始属性
					// ->syncOriginalAttributes()
					// 设置 数据表
					->setTable($table);

		// 是否存在
		$instance->exists = $exists;

		return $instance;
	}

	/**
	 * 新建 模型 实例
	 * 
	 * 填充属性值 无需转换
	 * 
	 * @param array $attributes
	 * @param string $table
	 * @param bool $exists
	 */
	public static function fromRawAttributes(array $attributes, string $table, bool $timestamps = false, string $dateFormat = null, bool $exists = false)
	{
		$instance = static::fromAttributes([], $table, $timestamps, $exists);

		$instance->setRawAttributes(
			array_merge($instance->getRawOriginal(), $attributes),
			$exists
		);

		return $instance;
	}

}