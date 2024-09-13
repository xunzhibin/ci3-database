<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

// 关系
use Xzb\Ci3\Database\Eloquent\Relations\Relation;

// 模型 缺少关系 异常类
use Xzb\Ci3\Database\Eloquent\ModelMissingRelationException;

/**
 * 关系 属性
 */
trait Relations
{
	/**
	 * 已加载 关系
	 * 
	 * @var array
	 */
	protected $relations = [];

	/**
	 * 设置 关系
	 * 
	 * @param string $relation
	 * @param mixed $value
	 * @return $this
	 */
	public function setRelation(string $relation, $value)
	{
		$this->relations[$relation] = $value;

		return $this;
	}

	/**
	 * 获取 指定 关系
	 * 
	 * @param string $relation
	 * @return mixed
	 */
	public function getRelation(string $relation)
	{
		return $this->relations[$relation];
	}

	/**
	 * 销毁 指定 关系
	 * 
	 * @param string $relation
	 * @return $this
	 */
	public function unsetRelation(string $relation)
	{
		unset($this->relations[$relation]);

		return $this;
	}

	/**
	 * 获取 所有 关系
	 * 
	 * @return array
	 */
	public function getRelations(): array
	{
		return $this->relations;
	}

	/**
	 * 关系 是否 已加载
	 * 
	 * @param string $relation
	 * @return bool
	 */
	public function isRelationLoaded(string $relation): bool
	{
		return array_key_exists($relation, $this->relations);
	}

	/**
	 * 是否为 关系方法
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function isRelation(string $key): bool
	{
		return method_exists($this, $key);
	}

	/**
	 * 获取 关系
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function getRelationValue(string $key)
	{
		// 是否已加载
		if ($this->isRelationLoaded($key)) {
			return $this->relations[$key];
		}

		// 不是 关系方法
		if (! $this->isRelation($key)) {
			return ;
		}

		// 设置 关系
		$this->setRelation(
			$key,
			// 获取 关系
			$results = $this->getRelationshipInstance($key)->getResults()
		);

		return $results;
	}

	/**
	 * 获取 关系 实例
	 * 
	 * @param string $method
	 * @return Xzb\Ci3\Database\Eloquent\Relations\Relation
	 * 
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelMissingRelationException
	 */
	protected function getRelationshipInstance(string $method)
	{
		$relation = $this->{$method}();

		if (! $relation instanceof Relation) {
			$message = static::class . '::' .  $method . ' must return a relationship instance';
			if (is_null($relation)) {
				$message = static::class . '::' .  $method . ' must return a relationship instance, but "null" was returned. Was the "return" keyword used?';
			}

			throw new ModelMissingRelationException($message);
		}

		return $relation;
	}

		// /**
	//  * 获取 关系 实例
	//  * 
	//  * @param string $method
	//  * @return Xzb\Ci3\Database\Eloquent\Relations\Relation
	//  * 
	//  * @throws \Xzb\Ci3\Database\Exception\ModelMissingRelationException
	//  */
	// protected function (string $method)
	// {
	// 	$relation = $this->{$method}();

	// 	if (! $relation instanceof Relation) {
	// 		$message = static::class . '::' .  $method . ' must return a relationship instance';
	// 		if (is_null($relation)) {
	// 			$message = static::class . '::' .  $method . ' must return a relationship instance, but "null" was returned. Was the "return" keyword used?';
	// 		}

	// 		throw new ModelMissingRelationException($message);
	// 	}

	// 	return $relation;
	// }


	/**
	 * 获取 可序列化 关系
	 * 
	 * @param array
	 */
	protected function getArrayableRelations()
	{
		return $this->getArrayableItems($this->relations);
	}

	/**
	 * 关系 转换为 数组
	 * 
	 * @return array
	 */
	public function relationsToArray()
	{
		$attributes = [];

		foreach ($this->getArrayableRelations() as $key => $value) {
			if (method_exists($value, $method = 'toArray')) {
				$relation = $value->{$method}();
			}
			else if (is_null($value)) {
				$relation = $value;
			}

			$key = Str::snake($key);

			if (isset($relation) || is_null($value)) {
				$attributes[$key] = $relation;
			}

			unset($relation);
		}

		return $attributes;
	}

}
