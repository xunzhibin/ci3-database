<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;
// 软删除 特性
use Xzb\Ci3\Database\Eloquent\SoftDeletes;

/**
 * 远程 一对多
 */
class HasManyThrough extends Relation
{
	/**
	 * 中间模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $middledModel;

	/**
	 * 中间模型 外键
	 * 
	 * @var string
	 */
	protected $middledModelForeignKey;

	/**
	 * 中间模型 主键
	 * 
	 * @var string
	 */
	protected $middledModelPrimaryKey;

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $associationModel
	 * @param \Xzb\Ci3\Database\Eloquent\Model $middledModel
	 * @param \Xzb\Ci3\Database\Eloquent\Model $parentModel
	 * @param string $parentModelForeignKey
	 * @param string $middledModelForeignKey
	 * @param string $parentModelPrimaryKey
	 * @param string $middledModelPrimaryKey
	 * @return void
	 */
	public function __construct(
		Model $associationModel, Model $middledModel, Model $parentModel,
		string $parentModelForeignKey, string $middledModelForeignKey,
		string $parentModelPrimaryKey, string $middledModelPrimaryKey
	)
	{
		$this->parentModelForeignKey = $parentModelForeignKey;
		$this->parentModelPrimaryKey = $parentModelPrimaryKey;

		$this->middledModel = $middledModel;
		$this->middledModelForeignKey = $middledModelForeignKey;
		$this->middledModelPrimaryKey = $middledModelPrimaryKey;

		parent::__construct($associationModel, $parentModel);
	}

	/**
	 * 设置 关系查询 基础约束
	 * 
	 * @return void
	 */
	public function addConstraints()
	{
		$otherConstraints = '';
		$where = [
			$this->getMiddledModelQualifyColumn($this->parentModelForeignKey) => $this->getParentModelPrimaryKeyValue()
		];

		// 中间模型 使用了 软删除
		if ($this->isMiddledModelUseSoftDeletes()) {
			// $otherConstraints = ' AND ' . $this->middledModel->getQualifyDeletedAtColumn() . ' IS NULL';
			$where[$this->middledModel->getQualifyDeletedAtColumn()] = null;
		}

		// 关联
		$this->setQueryExtension('join', [
				$this->middledModel->getTable(),
				$this->getMiddledModelQualifyColumn($this->middledModelPrimaryKey)
				. ' = ' .
				$this->getAssociationModelQualifyColumn($this->middledModelForeignKey)
				. $otherConstraints
		]);

		// 条件
		$this->setQueryExtension('whereBatch', [$where]);
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
		return $this->relatedModel->newCollection();
	}

	/**
	 * 获取 中间模型 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function getMiddledModelQualifyColumn(string $column): string
	{
		return $this->middledModel->qualifyColumn($column);
	}

	/**
	 * 中间模型 是否使用 软删除
	 * 
	 * @return bool
	 */
	public function isMiddledModelUseSoftDeletes(): bool
	{
		return in_array(SoftDeletes::class, class_traits($this->middledModel));
	}

}