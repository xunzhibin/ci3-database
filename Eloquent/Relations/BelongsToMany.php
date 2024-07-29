<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// 模型 Eloquent 查询构造器类
use Xzb\Ci3\Database\Eloquent\Builder;
// Eloquent 模型类 集合
use Xzb\Ci3\Database\Eloquent\Collection;
// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;
// 数据透视 特性
use Xzb\Ci3\Database\Eloquent\Relations\Traits\AsPivot;

// 转换 辅助函数
use Xzb\Ci3\Helpers\Transform;

/**
 * 多对多 关系
 */
class BelongsToMany extends Relation
{
	use Traits\PivotTable;

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $associationModel
	 * @param \Xzb\Ci3\Database\Eloquent\Model $parentModel
	 * @param string $pivotTable
	 * @param string $parentModelForeignKey
	 * @param string $associationModelForeignKey
	 * @param string $parentModelPrimaryKey
	 * @param string $associationModelPrimaryKey
	 * @return void
	 */
	public function __construct(
        Model $associationModel, Model $parentModel, string $pivotTable,
        string $parentModelForeignKey, string $associationModelForeignKey,
        string $parentModelPrimaryKey, string $associationModelPrimaryKey
    )
	{
		// 父模型
		$this->parentModelForeignKey = $parentModelForeignKey;
		$this->parentModelPrimaryKey = $parentModelPrimaryKey;

		// 关联模型
		$this->associationModelForeignKey = $associationModelForeignKey;
		$this->associationModelPrimaryKey = $associationModelPrimaryKey;

		// 解析 中间关系表(数据透视表)
		$this->pivotTable = $this->resolvePivotTableName($pivotTable);

		parent::__construct($associationModel, $parentModel);
	}

	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	public function getResults()
	{
		// 父模型 主键值 不存在
		if (! strlen($this->getParentModelPrimaryKeyValue())) {
			// 响应 默认值
			return $this->getDefaultFor();
		}

		return $this->get();
	}

	/**
	 * 设置 关系查询的 基本约束
	 * 
	 * @return void
	 */
	protected function addConstraints()
	{
		$where = [
			$this->pivotQualifyColumn($this->parentModelForeignKey) => $this->getParentModelPrimaryKeyValue()
		];

		// 关联
		$this->setQueryExtension('join', [
				$this->pivotTable,
				$this->pivotQualifyColumn($this->associationModelForeignKey)
				. '=' .
				$this->getAssociationModelQualifyColumn($this->associationModelPrimaryKey)
		]);

		// 条件
		$this->setQueryExtension('whereBatch', [ $where ]);
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

	/**
	 * 解析 中间表名称
	 * 
	 * @param string $table
	 * @return string
	 */
	protected function resolveTableName(string $table): string
	{
		if (! str_contains($table, '\\') || ! class_exists($table)) {
			return $table;
		}

		$model = new $table;

		if (! $model instanceof Model) {
			return $table;
		}

		if (in_array(AsPivot::class, class_traits($model))) {
			$this->using($table);
		}

		return $model->getTable();
	}

// ---------------------- 读取操作 ----------------------
	/**
	 * 读取 记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	public function get($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		// 执行 查询扩展
		$builder = $this->performQueryExtension($this->getQueryBuilder());

		// 执行 中间关系表(数据透视表) 查询扩展
		$builder = $this->performPivotQueryExtension($builder);

		// 查询 模型集合
		$models = $builder->getModels(
			$this->qualifySelectColumns($columns, $builder)
		);

		foreach ($models as $model) {
			// 获取 中间关系表(数据透视表) 属性
			$attributes = $this->getPivotAttributes($model);

			// 设置 模型 关系属性
			$model->setRelation($this->accessor, $this->newExistingPivot($attributes, true));
		}

		return $this->associationModel->newCollection($models);
	}

	/**
	 * 读取 查询结果的第一条记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Model|null
	 */
	public function first($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		return $this->get($columns)->first();
	}

	/**
	 * 获取 中间表 属性
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $model
	 * @return array
	 */
	protected function getPivotAttributes(Model $model): array
	{
		$attributes = [];

		foreach ($model->getAttributes() as $key => $value) {
			// 中间关系表(数据透视表) 列名称 前缀为 pivot_
			if (strncmp($key, $prefix = 'pivot_', strlen($prefix)) === 0) {
				$attributes[substr($key, strlen($prefix))] = $value;

				unset($model->$key);
			}
		}

		return $attributes;
	}

	/**
	 * 限定 查询 列
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder|null $builder
	 * @param array $columns
	 * @return array
	 */
	protected function qualifySelectColumns(array $columns = ['*'], $builder = null): array
	{
		return array_merge(
			parent::qualifySelectColumns($columns, $builder),
			$this->getPivotColumns()
		);
	}

// ---------------------- 创建 关联 ----------------------
	/**
	 * 创建 关联
	 * 
	 * @param array $attributes
	 * @param array $pivotAttributes
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function create(array $attributes, array $pivotAttributes = [])
	{
		$instance = $this->associationModel->newInstance($attributes);

		$instance->save();

		$this->attach($instance, $pivotAttributes);

		return $instance;
	}

	/**
	 * 创建 多关联
	 * 
	 * @param iterable $records
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	public function createMany(iterable $records, array $pivotAttributes = [])
	{
		$collection = $this->associationModel->newCollection();

		foreach ($records as $key => $record) {
			$pivotAttributes = $pivotAttributes[$key] ?? [];
			$collection->push($this->create($record, (array)$pivotAttributes));
		}

		return $collection;
	}

}