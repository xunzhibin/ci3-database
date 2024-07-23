<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

/**
 * 关联 数据表
 */
trait HasTables
{
// ---------------------- 关联 数据表 ----------------------
	/**
	 * 模型 关联数据表
	 * 
	 * @var string
	 */
	protected $table;

	/**
	 * 设置 模型 关联数据表
	 * 
	 * @param string $table
	 * @return $this
	 */
	public function setTable(string $table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * 获取 模型 关联数据表
	 * 
	 * @return string
	 */
	public function getTable(): string
	{
		return $this->table ?: Str::snake(Str::plural(class_basename($this)));
	}

	/**
	 * 获取 belongsToMany(多对多)关系的 中间联接表名称的 一段
	 * 
	 * @return string
	 */
	public function getJoiningTableSegment()
	{
		return Str::snake(class_basename($this));
	}

}
