<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

/**
 * 一对一
 */
class HasOne extends HasOneOrMany
{
	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	public function getResults()
	{
		if (is_null($this->getParentPrimaryKeyValue())) {
			return $this->getDefault();
		}

		return $this->query->first() ?: $this->getDefault();
	}

}
