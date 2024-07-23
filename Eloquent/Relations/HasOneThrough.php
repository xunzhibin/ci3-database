<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

/**
 * 远程 一对一
 */
class HasOneThrough extends HasManyThrough
{
	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	public function getResults()
	{
		if (! strlen($this->getParentModelPrimaryKeyValue())) {
			return $this->getDefaultFor();
		}

		return $this->first() ?: $this->getDefaultFor();
	}

	/**
	 * 获取 关系 默认值
	 * 
	 * @return mixed
	 */
	protected function getDefaultFor()
	{
		return ;
	}

}