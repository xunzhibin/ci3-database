<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

/**
 * 一对多 关系类
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
		if (! strlen($this->getParentModelPrimaryKeyValue())) {
			return $this->getDefaultFor();
		}

		return $this->get();
	}

	/**
	 * 获取 默认值
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	protected function getDefaultFor()
	{
		return $this->relatedModel->newCollection();
	}

}