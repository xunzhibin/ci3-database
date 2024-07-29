<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// 模型类
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 一对多 关系 抽象类
 */
class HasMany extends Relation
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
		return $this->associationModel->newCollection();
	}

// ---------------------- 创建 ----------------------
	/**
	 * 创建 关联
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function create(array $attributes = [])
	{
		$model = $this->associationModel->newInstance($attributes);

		// 设置 父 外键
		$this->setParentForeignKeyWhenCreateAssociation($model);

		$model->save();

		return $model;
	}

	/**
	 * 创建 多关联
	 * 
	 * @param iterable $records
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	public function createMany(iterable $records)
	{
		$collection = $this->associationModel->newCollection();

		foreach ($records as $record) {
			$collection->push($this->create($record));
		}

		return $collection;
	}

	/**
	 * 创建 关联时, 设置 父 外键
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model
	 * @return void
	 */
	protected function setParentForeignKeyWhenCreateAssociation(Model $model)
	{
		$model->setAttribute($this->parentModelForeignKey, $this->getParentModelPrimaryKeyValue());
	}

}