<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型
use Xzb\Ci3\Database\Eloquent\Model;
// 调用转发 trait
use Xzb\Ci3\Helpers\Traits\ForwardsCalls;

// PHP 匿名函数
use Closure;

/**
 * 关系 抽象类
 */
abstract class Relation
{
	use ForwardsCalls;

	/**
	 * 父 模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $parent;

	/**
	 * 关联 模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $related;

	/**
	 * Eloquent 查询构造器 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Builder
	 */
	protected $query;

	/**
	 * 默认 结果
	 * 
	 * @var \Closure
	 */
	protected $default;

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $parent 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $related
	 */
	public function __construct(Model $parent, Model $related)
	{
		$this->parent = $parent;
		$this->related = $related;
		$this->query = $related->newQuery();

		// 添加 关联 基本约束
		$this->addConstraints();
	}

	/**
	 * 添加 基本约束
	 */
	abstract public function addConstraints();

	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	abstract public function getResults();

// ---------------------- 父 模型 ----------------------
	/**
	 * 获取 父模型 实例
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function getParent()
	{
		return $this->parent;
	}

// ---------------------- 关联 模型 ----------------------
	/**
	 * 获取 关联模型 实例
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function getRelated()
	{
		return $this->related;
	}

	/**
	 * 关联模型 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function relatedQualifyColumn($column)
	{
		return $this->related->qualifyColumn($column);
	}

// ---------------------- Eloquent 查询构造器 ----------------------
	/**
	 * 获取 关系 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	protected function getRelationQuery()
	{
		return $this->query;
	}

// ---------------------- 默认 结果 ----------------------
	/**
	 * 设置 默认结果
	 * 
	 * @param \Closure $callback
	 * @return $this
	 */
	public function default(Closure $callback)
	{
		$this->default = $callback;

		return $this;
	}

	/**
	 * 获取 默认结果
	 * 
	 * @return mixed
	 */
	public function getDefault()
	{
		if ($this->default) {
            return call_user_func($this->withDefault);
		}

		return ;
	}

// ---------------------- 魔术方法 ----------------------
	/**
	 * 处理调用 不可访问 方法
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$result = $this->forwardCallTo($this->query, $method, $parameters);

		return $result === $this->query ? $this : $result;
	}

}
