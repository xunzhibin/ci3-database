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
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $associationModel
	 * @param \Xzb\Ci3\Database\Eloquent\Model $childModel
	 * @param string $parentModelForeignKey
	 * @param string $parentModelPrimaryKey
	 * @return void
	 */
	public function __construct(Model $associationModel, Model $childModel, string $parentModelForeignKey, string $parentModelPrimaryKey)
	{
		$this->childModel = $childModel;

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

}