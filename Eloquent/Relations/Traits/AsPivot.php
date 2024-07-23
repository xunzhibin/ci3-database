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
	 * 是否有 时间戳 属性
	 * 
	 * @param array|null $attributes
	 * @return bool
	 */
	public function hasTimestampAttributes(array $attributes = null): bool
	{
		return array_key_exists($this->getCreatedAtColumn(), $attributes ?? $this->attributes);
	}

	/**
	 * 新建 数据透视表 模型实例
	 * 
	 * @param array $attributes
	 * @param string $table
	 * @param bool $exists
     * @return static
	 */
	public static function fromAttributes(array $attributes, string $table, bool $exists = false)
	{
		$instance = new static;

		$instance->timestamps = $instance->hasTimestampAttributes($attributes);

		// 填充属性 并同步原始属性
		$instance->fill($attributes)->syncOriginalAttributes();

		$instance->exists = $exists;

		return $instance;
	}
}