<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 集合类
use Xzb\Ci3\Database\Eloquent\Collection;

/**
 * 模型 实例
 */
trait HasInstances
{
	/**
	 * 创建 模型 新实例
	 * 
	 * @param array $attributes
	 * @param bool $exists
	 * @return static
	 */
	public function newInstance($attributes = [], $exists = false)
	{
		$model = new static;

		// 在关联数据表中 是否存在
		$model->exists = $exists;

		// // 设置 关联数据表
		// $model->setTable($this->getTable());

		// 填充 属性
		$model->fill((array)$attributes);

		return $model;
	}

	/**
	 * 创建 模型 新实例
	 * 
	 * @param array $attributes
	 * @param
	 */
	public function newRawInstance($attributes = [], $exists = false, $isSync = false)
	{
		$model = $this->newInstance([], $exists);

		$model->setRawAttributes((array)$attributes, $isSync);

		return $model;
	}

	/**
	 * 创建 模型 新实例 已存在
	 * 
	 * @param array $attributes
	 * @return static
	 */
	public function newInstanceFromBuilder($attributes = [])
	{
		$model = $this->newRawInstance($attributes, true, true);

		// retrieved 取回后 事件
		$model->fireModelEvent('retrieved');

		return $model;
	}

	/**
	 * 创建 模型集合 新实例
	 * 
	 * @param array $models
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function newCollection(array $models = [])
	{
		return new Collection($models);
	}

}
