<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 一对一 或 一对多 抽象类
 */
abstract class HasOneOrMany extends Relation
{
	/**
	 * 父模型 外键
	 * 
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * 父模型 主键
	 * 
	 * @var string
	 */
	protected $primaryKey;

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $parent
	 * @param \Xzb\Ci3\Database\Eloquent\Model $related
	 * @param string $foreignKey
	 * @param string $primaryKey
	 * @return void
	 */
	public function __construct(Model $parent, Model $related, string $foreignKey, string $primaryKey)
	{
		$this->foreignKey = $foreignKey;
		$this->primaryKey = $primaryKey;

		parent::__construct($parent, $related);
	}

	/**
	 * 添加 基本约束
	 * 
	 * @return void
	 */
	public function addConstraints()
	{
		$query = $this->getRelationQuery();

		$query->where($this->foreignKey, $this->getParentPrimaryKeyValue());

		// $query->whereNotNull($this->foreignKey);
	}

	/**
	 * 获取 父模型 主键值
	 * 
	 * @return mixed
	 */
	public function getParentPrimaryKeyValue()
	{
		return $this->parent->getAttribute($this->primaryKey);
	}

// ---------------------- 关联模型 操作 ----------------------
	/**
	 * 创建 关联模型
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function create(array $attributes = [])
	{
		return $this->save(
			$this->related->newInstance($attributes)
		);
	}

	/**
	 * 创建/更新 关联模型
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $model
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function save(Model $model)
	{
		$model->setAttribute($this->foreignKey, $this->getParentPrimaryKeyValue());

		$model->save();

		return $model;
	}

}
