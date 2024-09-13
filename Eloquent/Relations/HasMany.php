<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 一对多
 */
class HasMany extends HasOneOrMany
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

		return $this->query->get(); 
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
