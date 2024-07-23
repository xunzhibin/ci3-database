<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// 模型类
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 一对一、一对多 关系 抽象类
 */
abstract class HasOneOrMany extends Relation
{
	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $associationModel
	 * @param \Xzb\Ci3\Database\Eloquent\Model $parentModel
	 * @param string $parentModelForeignKey
	 * @param string $parentModelPrimaryKey
	 * @return void
	 */
	public function __construct(Model $associationModel, Model $parentModel, string $parentModelForeignKey, string $parentModelPrimaryKey)
	{
		$this->parentModelForeignKey = $parentModelForeignKey;
		$this->parentModelPrimaryKey = $parentModelPrimaryKey;

		parent::__construct($associationModel, $parentModel);
	}

	/**
	 * 设置 关系查询 基础约束
	 * 
	 * @return void
	 */
	public function addConstraints()
	{
		$this->setQueryExtension('whereBatch', [
			[ $this->parentModelForeignKey => $this->getParentModelPrimaryKeyValue() ]
		]);
	}

}