<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

/**
 * 主键
 */
trait HasPrimaryKeys
{
	/**
	 * 模型 主键
	 * 
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * 模型 主键 数据类型
	 * 
	 * @var string
	 */
	protected $primaryKeyType = 'int';

	/**
	 * 模型 主键 是否自增
	 * 
	 * @var bool
	 */
	public $incrementing = true;

	/**
	 * 设置 模型主键 名称
	 * 
	 * @param string $key
	 * @return $this
	 */
	public function setPrimaryKeyName(string $key)
	{
		$this->primaryKey = $key;

		return $this;
	}

	/**
	 * 获取 模型主键 名称
	 * 
	 * @return string
	 */
	public function getPrimaryKeyName(): string
	{
		return $this->primaryKey;
	}

	/**
	 * 设置 模型主键 数据类型
	 * 
	 * @param string $type
	 * @return $this
	 */
	public function setPrimaryKeyType(string $type)
	{
		$this->primaryKeyType = $type;

		return $this;
	}

	/**
	 * 获取 模型主键 数据类型
	 * 
	 * @return string
	 */
	public function getPrimaryKeyType(): string
	{
		return $this->primaryKeyType;
	}

	/**
	 * 设置 模型主键 是否自增
	 * 
	 * @param bool $value
	 * @return $this
	 */
	public function setIncrementing(bool $value)
	{
		$this->incrementing = $value;

		return $this;
	}

	/**
	 * 获取 模型主键 是否自增
	 * 
	 * @return bool
	 */
	public function getIncrementing(): bool
	{
		return $this->incrementing;
	}

	/**
	 * 获取 模型主键 值
	 * 
	 * @return mixed
	 */
	public function getPrimaryKeyValue()
	{
		return $this->getAttribute($this->getPrimaryKeyName());
	}

	/**
	 * 获取 读取(R)更新(U)删除(D)操作 主键值
	 * 
	 * @return mixed
	 */
	protected function getPrimaryKeyValueForRUD()
	{
		return $this->original[$this->getPrimaryKeyName()] ?? $this->getPrimaryKeyValue();
	}

	/**
	 * 获取 模型外键 名称
	 * 
	 * @return string
	 */
	public function getForeignKeyName()
	{
		return Str::snake(class_basename($this)) . '_' . $this->getPrimaryKeyName();
	}

}
