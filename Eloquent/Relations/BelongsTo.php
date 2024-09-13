<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 属于
 */
class BelongsTo extends Relation
{
	/**
	 * 子模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $child;

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
	 * 关系 名称
	 * 
	 * @var string
	 */
	protected $relationName;

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $child
	 * @param \Xzb\Ci3\Database\Eloquent\Model $related
	 * @param string $foreignKey
	 * @param string $primaryKey
	 * @return void
	 */
	public function __construct(
		Model $child, Model $related,
		string $foreignKey, string $primaryKey,
		string $relationName
	)
	{
		$this->child = $child;

		$this->foreignKey = $foreignKey;
		$this->primaryKey = $primaryKey;

		$this->relationName = $relationName;

		parent::__construct($related, $related);
	}

	/**
	 * 添加 基本约束
	 * 
	 * @return void
	 */
	public function addConstraints()
	{
		$query = $this->getRelationQuery();

		$this->query->where($this->primaryKey, $this->child->{$this->foreignKey});
	}

	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	public function getResults()
	{
		if (is_null($this->child->{$this->foreignKey})) {
			return $this->getDefault();
		}

		return $this->query->first() ?: $this->getDefault();
	}

// ---------------------- 子模型 操作 ----------------------
	/**
	 * 关联 指定 父
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model|string|int|null $model
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function associate($model)
	{
		$primaryKey = $model instanceof Model ? $model->getAttribute($this->primaryKey) : $model;

		$this->child->setAttribute($this->foreignKey, $primaryKey);

		if ($model instanceof Model) {
			$this->child->setRelation($this->relationName, $model);
		}
		else {
			$this->child->unsetRelation($this->relationName);
		}

		return $this->child;
	}

	/**
	 * 分离 父
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function dissociate()
	{
		$this->child->setAttribute($this->foreignKey, null);

		return $this->child->setRelation($this->relationName, null);
	}

}
