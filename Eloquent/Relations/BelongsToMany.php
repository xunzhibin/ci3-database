<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 多对多 关系
 */
class BelongsToMany extends Relation
{
	/**
	 * 中间关系表(数据透视表)
	 * 
	 * @var string
	 */
	protected $middledTable;

	/**
	 * 关联模型 访问器 名称
	 *
	 * @var string
	 */
	protected $accessor = 'pivot';

	/**
	 * 中间关系表 查询列
	 * 
	 * @var array
	 */
	protected $middledTableColumns = [];

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $associationModel
	 * @param \Xzb\Ci3\Database\Eloquent\Model $parentModel
	 * @param string $middledTable
	 * @param string $parentModelForeignKey
	 * @param string $associationModelForeignKey
	 * @param string $parentModelPrimaryKey
	 * @param string $associationModelPrimaryKey
	 * @return void
	 */
	public function __construct(
        Model $associationModel, Model $parentModel, string $middledTable,
        string $parentModelForeignKey, string $associationModelForeignKey,
        string $parentModelPrimaryKey, string $associationModelPrimaryKey
    )
	{
		$this->parentModelForeignKey = $parentModelForeignKey;
		$this->parentModelPrimaryKey = $parentModelPrimaryKey;

		$this->associationModelForeignKey = $associationModelForeignKey;
		$this->associationModelPrimaryKey = $associationModelPrimaryKey;

		$this->middledTable = $this->resolveTableName($middledTable);

		parent::__construct($associationModel, $parentModel);
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
	 * 读取 记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	public function get($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		$models = $this->performQueryExtension($builder = $this->getQueryBuilder())
						->getModels($this->convertToAssociationModelQualifyColumns($columns, $builder));

		foreach ($models as $model) {
			$model->setRelation($this->accessor, $this->newExistingPivot($this->getMiddledTableAttributes($model)));
		}

		return $this->associationModel->newCollection($models);
	}

	/**
	 * 获取 中间表 属性
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $model
	 * @return array
	 */
	protected function getMiddledTableAttributes(Model $model): array
	{
		$attributes = [];

		foreach ($model->getAttributes() as $key => $value) {
			if (strncmp($key, $prefix = 'pivot_', strlen($prefix)) === 0) {
				$attributes[substr($key, strlen($prefix))] = $value;

				unset($model->$key);
			}
		}

		return $attributes;
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
	 * 设置 关系查询的 基本约束
	 * 
	 * @return void
	 */
	protected function addConstraints()
	{
		$where = [
			$this->getMiddledTableQualifyColumn($this->parentModelForeignKey) => $this->getParentModelPrimaryKeyValue()
		];

		// 关联
		$this->setQueryExtension('join', [
				$this->middledTable,
				$this->getMiddledTableQualifyColumn($this->associationModelForeignKey)
				. '=' .
				$this->getAssociationModelQualifyColumn($this->associationModelPrimaryKey)
		]);

		// 条件
		$this->setQueryExtension('whereBatch', [ $where ]);
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

		return $model->getTable();
	}

	/**
	 * 转换为 关联模型 限定列
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder|null $builder
	 * @param array $columns
	 * @return array
	 */
	protected function convertToAssociationModelQualifyColumns(array $columns = ['*'], $builder = null): array
	{
		$columns = parent::convertToAssociationModelQualifyColumns($columns, $builder);

		return $this->addMiddledTableColumns($columns);
	}

	/**
	 * 添加 中间表 查询列
	 * 
	 * @param array $columns
	 * @return array
	 */
	protected function addMiddledTableColumns(array $columns): array
	{
		$defaults = [
			$this->parentModelForeignKey,
			$this->associationModelForeignKey,
		];

		$middledTableColumns = array_map(function ($column) {
			return $this->getMiddledTableQualifyColumn($column) . ' as pivot_' . $column;
		}, array_merge($defaults, $this->middledTableColumns));

		return array_merge($columns, array_unique($middledTableColumns));
	}

	/**
	 * 设置 关联模型 访问器名称
	 * 
	 * @param string $accessor
	 * @return $this
	 */
	public function as(string $accessor)
	{
		$this->accessor = $accessor;

		return $this;
	}

	/**
	 * 设置 中间表 查询列
	 * 
	 * @param array|string $columns
	 * @return $this
	 */
	public function withPivot($columns)
	{
		$this->middledTableColumns = array_merge(
			$this->middledTableColumns, is_array($columns) ? $columns : func_get_args()
		);

		return $this;
	}

	/**
	 * 中间表 多条件
	 *
	 * AND WHERE
	 * 
	 * @param array $wheres
	 * @return $this
	 */
	public function whereBatchPivot(array $wheres)
	{
		$qualifyWheres = array_filter($wheres, function (&$column) {
			return $column = $this->getMiddledTableQualifyColumn($column);
		}, ARRAY_FILTER_USE_KEY);

		$this->setQueryExtension('whereBatch', [ $qualifyWheres ]);

		return $this;
	}

	/**
	 * 中间表 多排序
	 * 
	 * ORDER BY
	 * 
	 * @param array $orderBy
	 * @return $this
	 */
	public function orderByBatchPivot(array $orderBy)
	{
		$qualifyOrderBy = array_filter($orderBy, function (&$column) {
			return $column = $this->getMiddledTableQualifyColumn($column);
		}, ARRAY_FILTER_USE_KEY);

		$this->setQueryExtension('orderByBatch', [ $qualifyOrderBy ]);

		return $this;
	}

	/**
	 * 获取 中间表 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function getMiddledTableQualifyColumn(string $column): string
	{
		if (str_contains($column, '.')) {
			return $column;
		}

		return $this->middledTable . '.' . $column;
	}

	/**
	 * 创建 数据透视模型 实例
	 * 
	 * @param array $attributes
	 * @param bool $exists
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\Pivot
	 */
	public function newPivot(array $attributes = [], $exists = false)
	{
		$pivot = Pivot::fromAttributes($attributes, $this->middledTable, $exists);

		return $pivot->setPivotKeys($this->parentModelForeignKey, $this->associationModelForeignKey);
	}

	/**
	 * 新建 已存在 数据透视模型 实例
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\Pivot
	 */
	public function newExistingPivot(array $attributes = [])
	{
		return $this->newPivot($attributes, true);
	}

}