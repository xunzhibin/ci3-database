<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

/**
 * 一对一 远程
 */
class HasOneThrough extends HasOneOrManyThrough
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

		return $this->first() ?: $this->getDefault();
	}
}
