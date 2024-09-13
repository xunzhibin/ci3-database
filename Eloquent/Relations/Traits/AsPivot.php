<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations\Traits;

/**
 * 支点
 */
trait AsPivot
{
	/**
	 * 父模型 外键
	 * 
	 * @var string
	 */
	protected $parentForeignKey;

	/**
	 * 关联模型 外键
	 * 
	 * @var string
	 */
	protected $relatedForeignKey;

	/**
	 * 新建 模型实例
	 * 
	 * @param array $attributes
	 * @param string $table
	 * @param bool $exists
	 * @param bool $timestamps
	 * @param string $dateFormat
	 * @return static
	 */
	public static function newPivotInstance(
		array $attributes, string $table, $exists = false,
		bool $timestamps = false, string $dateFormat = null
	)
	{
		$instance = new static;

		// 是否自动维护 时间戳
		$instance->timestamps = $timestamps;

		// 设置 日期时间 存储格式
		if (! is_null($dateFormat)) {
			$instance->setDateFormat($dateFormat);
		}

		$instance->setTable($table)->fill($attributes);
		// $instance->setTable($table)->fill($attributes)->syncOriginal();

		$instance->exists = $exists;

		return $instance;
	}

	/**
	 * 创建 模型实例
	 * 
	 * @param array $attributes
	 * @param string $table
	 * @param bool $exists
	 * @param bool $timestamps
	 * @param string $dateFormat
	 * @return static
	 */
	public static function newPivotRawInstance(
		array $attributes, string $table, $exists = false,
		bool $timestamps = false, string $dateFormat = null
	)
	{
		$instance = static::newPivotInstance([], $table, $exists, $timestamps, $dateFormat);

		$instance->setRawAttributes(
			array_merge($instance->getRawOriginal(), $attributes), $exists
		);

		return $instance;
	}

	/**
	 * 设置 中间表 键名
	 * 
	 * @param string $parentForeignKey
	 * @param string $relatedForeignKey
	 * @return $this
	 */
	public function setPivotKeys(string $parentForeignKey, string $relatedForeignKey)
	{
		$this->parentForeignKey = $parentForeignKey;
		$this->relatedForeignKey = $relatedForeignKey;

		return $this;
	}

	/**
	 * 删除
	 * 
	 * @return int
	 */
	public function delete(): int
	{
		if (isset($this->attributes[$this->getPrimaryKeyName()])) {
			return parent::delete();
		}

		return $this->newQuery()->where([
			$this->parentForeignKey => $this->original[$this->parentForeignKey] ?? $this->getAttribute($this->parentForeignKey),
			$this->relatedForeignKey => $this->original[$this->relatedForeignKey] ?? $this->getAttribute($this->relatedForeignKey),
		])->delete();
	}

}
