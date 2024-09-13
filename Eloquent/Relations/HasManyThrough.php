<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;
// 软删除 特性
use Xzb\Ci3\Database\Eloquent\SoftDeletes;

/**
 * 一对多 远程
 */
class HasManyThrough extends HasOneOrManyThrough
{
	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	public function getResults()
	{
		if (is_null($this->parent->{$this->parentPrimaryKey})) {
			return $this->getDefault();
		}

		return $this->get();
	}

	/**
	 * 获取 默认结果
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function getDefault()
	{
		return parent::getDefault() ?: $this->related->newCollection();
	}

}
