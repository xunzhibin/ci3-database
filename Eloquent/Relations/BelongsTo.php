<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 属于 关系
 */
class BelongsTo extends Relation
{
	/**
	 * 子模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $childModel;

	/**
	 * 关系键名
	 * 
	 * @var string
	 */
	protected $relationKeyName;

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $associationModel
	 * @param \Xzb\Ci3\Database\Eloquent\Model $childModel
	 * @param string $parentModelForeignKey
	 * @param string $parentModelPrimaryKey
	 * @return void
	 */
	public function __construct(
		Model $associationModel, Model $childModel,
		string $parentModelForeignKey, string $parentModelPrimaryKey,
		string $relationKeyName
	)
	{
		$this->childModel = $childModel;
		$this->relationKeyName = $relationKeyName;

		$this->parentModelForeignKey = $parentModelForeignKey;
		$this->parentModelPrimaryKey = $parentModelPrimaryKey;

		parent::__construct($associationModel, $associationModel);
	}

	/**
	 * 设置 关系查询 基础约束
	 * 
	 * @return void
	 */
	public function addConstraints()
	{
		$this->setQueryExtension('whereBatch', [
			[ $this->parentModelPrimaryKey => $this->childModel->{$this->parentModelForeignKey} ]
		]);
	}

	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	public function getResults()
	{
		if (! strlen($this->childModel->{$this->parentModelForeignKey})) {
			return $this->getDefaultFor();
		}

		return $this->first() ?: $this->getDefaultFor();
	}

// ---------------------- 关联(更新) ----------------------
	/**
	 * 关联 给定父
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function associate($model)
	{
		$parentModelPrimaryKeyValue = $model instanceof Model
										? $model->getAttribute($this->parentModelPrimaryKey)
										: $model;

		$this->childModel->setAttribute($this->parentModelForeignKey, $parentModelPrimaryKeyValue);

		if ($model instanceof Model) {
			$this->childModel->setRelation($this->relationKeyName, $model);
		}
		else {
			$this->childModel->unsetRelation($this->relationKeyName);
		}

		return $this->childModel;
	}

// ---------------------- 分离(移除) ----------------------
	/**
	 * 移除 父
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function dissociate()
	{
		$this->childModel->setAttribute($this->parentModelForeignKey, null);

		$this->childModel->unsetRelation($this->relationKeyName);
		// $this->child->setRelation($this->relationKeyName, null);

		return $this->childModel;
	}

}