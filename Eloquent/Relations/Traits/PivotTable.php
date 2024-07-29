<?php
// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations\Traits;

// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;
// 模型 Eloquent 查询构造器类
use Xzb\Ci3\Database\Eloquent\Builder;
// 数据透视 Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Relations\Pivot;
// 转换 辅助函数
use Xzb\Ci3\Helpers\Transform;

// PHP 匿名函数类
use Closure;

/**
 * 中间关系表(数据透视表)
 */
trait PivotTable
{
// ---------------------- 访问器名称 ----------------------
	/**
	 * 关联模型 访问器 名称
	 *
	 * @var string
	 */
	protected $accessor = 'pivot';

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

// ---------------------- 数据表 ----------------------
	/**
	 * 中间关系表(数据透视表)
	 * 
	 * @var string
	 */
	protected $pivotTable;

	/**
	 * 解析 中间关系表(数据透视表) 名称
	 * 
	 * @param string $table
	 * @return string
	 */
	protected function resolvePivotTableName(string $table): string
	{
		if (
			// 不包含 反斜杠, 不是 包含命名空间的类
			! str_contains($table, '\\')
			// 类不存在
			|| ! class_exists($table)
		) {
			return $table;
		}

		// 实例化
		$model = new $table;

		// 不属于 Eloquent 模型类
		if (! $model instanceof Model) {
			return $table;
		}

		// 具有 数据透视表 特性
		if (in_array(AsPivot::class, class_traits($model))) {
			$this->using($table);
		}

		return $model->getTable();
	}

// ---------------------- 模型类 ----------------------
	/**
	 * 中间关系表模型类(数据透视表模型类)
	 * 
	 * @var string
	 */
	protected $pivotModelClass;

	/**
	 * 获取 中间关系表 模型类
	 * 
	 * @return string
	 */
	public function getPivotModelClass(): string
	{
		return $this->pivotModelClass ?: Pivot::class;
	}

	/**
	 * 设置 中间关系表 模型实例
	 * 
	 * @param string $class
	 * @return $this
	 */
	public function using(string $class)
	{
		$this->pivotModelClass = $class;

		return $this;
	}

// ---------------------- 时间戳 ----------------------
	/**
	 * 中间关系表(数据透视表) 是否 自动维护 操作时间
	 * 
	 * @var bool
	 */
	public $pivotTimestamps = false;

	/**
	 * 中间关系表(数据透视表) 创建时间 列名
	 * 
	 * @var string
	 */
	protected $pivotCreatedAt;

	/**
	 * 中间关系表(数据透视表) 更新时间 列名
	 * 
	 * @var string
	 */
	protected $pivotUpdatedAt;

	/**
	 * 中间关系表(数据透视表) 日期属性 存储格式
	 * 
	 * @var string
	 */
	protected $pivotDateFormat;

	/**
	 * 指定 中间关系表(数据透视表) 具有 创建时间、更新时间
	 * 
	 * @param string $createdAt
	 * @param string $updateAt
	 * @param string $dateFormat
	 * @return $this
	 */
	public function withPivotTimestamps(string $createdAt = null, string $updatedAt = null, string $dateFormat = null)
	{
		$this->pivotTimestamps = true;
		$this->pivotDateFormat = $dateFormat ?: $this->parentModel->getDateFormat();

		$this->pivotCreatedAt = $createdAt ?: $this->parentModel->getCreatedAtColumn();
		$this->pivotUpdatedAt = $updatedAt ?: $this->parentModel->getUpdatedAtColumn();

		return $this;
	}

// ---------------------- 模型实例 ----------------------
	/**
	 * 创建 模型 实例
	 * 
	 * @param array $attributes
	 * @param bool $exists
	 * @param bool $isRaw
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\Pivot
	 */
	public function newPivot(array $attributes = [], bool $exists = false, $isRaw = false)
	{
		$pivot = $isRaw
					? $this->getPivotModelClass()::fromRawAttributes($attributes, $this->pivotTable, $this->pivotTimestamps, $this->pivotDateFormat, $exists)
					: $this->getPivotModelClass()::fromAttributes($attributes, $this->pivotTable, $this->pivotTimestamps, $this->pivotDateFormat, $exists);

		// 设置 相关键名
		return $pivot->setPivotKeys($this->parentModelForeignKey, $this->associationModelForeignKey);
	}

	/**
	 * 新建 已存在 模型 实例
	 * 
	 * @param array $attributes
	 * @param bool $isRaw
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\Pivot
	 */
	public function newExistingPivot(array $attributes = [], bool $isRaw = false)
	{
		return $this->newPivot($attributes, true, $isRaw);
	}

// ---------------------- 限定列 ----------------------
	/**
	 * 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function pivotQualifyColumn(string $column): string
	{
		return $this->newPivot()->qualifyColumn($column);
	}

// ---------------------- 查询构造器 ----------------------
	/**
	 * 新建 数据透视表 模型 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function newPivotQueryBuilder()
	{
		return $this->newPivot()->newQueryBuilder();
	}

// ---------------------- 查询构造器操作扩展 ----------------------
	/**
	 * 查询列
	 * 
	 * @var array
	 */
	protected $pivotColumns = [];

	/**
	 * where子句
	 * 
	 * @var array
	 */
	protected $pivotWheres = [];

	/**
	 * 设置 查询列
	 * 
	 * @param array|string $columns
	 * @return $this
	 */
	public function withPivot($columns)
	{
		$this->pivotColumns = array_merge(
			$this->pivotColumns, is_array($columns) ? $columns : func_get_args()
		);

		return $this;
	}

	/**
	 * 获取 查询列
	 * 
	 * @return array
	 */
	public function getPivotColumns(): array
	{
		$defaults = [
			$this->parentModelForeignKey,
			$this->associationModelForeignKey,
		];

		$columns = array_map(function ($column) {
			return $this->pivotQualifyColumn($column) . ' as pivot_' . $column;
		}, array_merge($defaults, $this->pivotColumns));

		return array_unique($columns);
	}

	/**
	 * 多条件
	 *
	 * AND WHERE
	 * 
	 * @param array $wheres
	 * @return $this
	 */
	public function wherePivot(array $wheres)
	{
		$wheres = array_filter($wheres, function (&$column) {
			return $column = $this->pivotQualifyColumn($column);
		}, ARRAY_FILTER_USE_KEY);

		$this->pivotWheres[] = $wheres;

		return $this;
	}

	/**
	 * 执行 查询扩展
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	protected function performPivotQueryExtension(Builder $builder)
	{
		// 执行 查询 扩展
		foreach ($this->pivotWheres as $where) {
			$builder->whereBatch($where);
		}

		return $builder;
	}

// ---------------------- 解析 关联 主键值 ----------------------
	/**
	 * 解析 关联 主键值
	 * 
	 * @param mixed $value
	 * @return array
	 */
	protected function parseAssociationPrimaryKeyValues($value)
	{
		// 属于 Eloquent 模型类
		if ($value instanceof Model) {
			return [ $value->{$this->associationModelPrimaryKey} ];
		}

		// 属于 Eloquent 集合类
		if ($value instanceof Collection) {
			return $value->pluck($this->associationModelPrimaryKey)->all();
		}

		return (array)$value;
	}

// ---------------------- 格式化 记录 ----------------------
	/**
	 * 格式化 记录
	 * 
	 * @param array $ids
	 * @param Closure $callback
	 * @return array
	 */
	protected function formatRecords($ids, Closure $callback = null)
	{
		$records = [];

		foreach ($ids as $id => $attributes) {
			if (! is_array($attributes)) {
				$id = $attributes;
				$attributes = [];
			}

			$records[$id] = $callback instanceof Closure
								? $callback($id, $attributes)
								: $attributes;
		}

		return $records;
	}

	/**
	 * 格式化 关联 记录
	 * 
	 * @param array $ids
	 * @param array $otherAttributes
	 * @return array
	 */
	protected function formatAttachRecords($ids, array $otherAttributes = [])
	{
		$records = [];

		// 父模型 外键
		$otherAttributes[$this->parentModelForeignKey] = $this->getParentModelPrimaryKeyValue();

		return $this->formatRecords($ids, function ($id, $attributes) use ($otherAttributes) {
			// 关联模型 外键
			$attributes[$this->associationModelForeignKey] = $id;

			return $this->newPivot(array_merge($otherAttributes, $attributes))->getInsertAttributes();
		});

		return $records;
	}

// ---------------------- 约束 ----------------------
	/**
	 * 添加 约束
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder
	 * @param array|null $ids
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	protected function addConstraintsToPivot(Builder $builder, $ids = null)
	{
		$wheres = [
			$this->parentModelForeignKey => $this->getParentModelPrimaryKeyValue(),
		];

		if (! is_null($ids)) {
			$wheres[$this->associationModelForeignKey] = $ids;
		}

		// 合并 where 条件
		$wheres = array_merge($wheres, []);
		// $wheres = array_merge($wheres, $this->pivotWheres);

		return $builder->whereBatch($wheres);
	}

// ---------------------- 附加(添加) ----------------------
	/**
	 * 附加(添加) 关系
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return int
	 */
	public function attach($id, array $attributes = []): int
	{
		// 格式化 关联记录
		$records = $this->formatAttachRecords($this->parseAssociationPrimaryKeyValues($id), $attributes);

		return $this->newPivotQueryBuilder()->insert($records);
	}

// ---------------------- 读取 ----------------------
	/**
	 * 获取 当前 所有关系
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	protected function getCurrentPivots()
	{
		return $this->addConstraintsToPivot($this->newPivotQueryBuilder())
						->get()
						->map(function ($record) {
							return $this->newExistingPivot((array)$record->getAttributes(), true);
						});
	}

// ---------------------- 更新 ----------------------
	/**
	 * 更新 关系信息
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return int
	 */
	public function updateExistingPivot($id, array $attributes)
	{
		$attributes = $this->newExistingPivot($attributes)->getInsertAttributes();

		return $this->addConstraintsToPivot(
			$this->newPivotQueryBuilder(), $this->parseAssociationPrimaryKeyValues($id)
		)->update($attributes);
	}

// ---------------------- 分离(移除) ----------------------
	/**
	 * 分离(移除) 关系
	 * 
	 * @param mixed $ids
	 * @return int
	 */
	public function detach($ids)
	{
		$ids = $this->parseAssociationPrimaryKeyValues($ids);
		if (empty($ids)) {
			return 0;
		}

		return $this->addConstraintsToPivot(
			$this->newPivotQueryBuilder(), $ids
		)->delete();
	}

// ---------------------- 同步 ----------------------
	/**
	 * 同步 关系
	 * 
	 * @param mixed $ids
	 * @param bool $detaching
	 * @return array
	 */
	public function sync($ids, $detaching = true)
	{
		$changes = [
			'attached' => [],
			'detached' => [],
			'updated' => [],
		];

		// 获取 当前 所有关系的 关联外键
		$currentAssociationForeignKeyValues = $this->getCurrentPivots()->pluck($this->associationModelForeignKey)->all();

		// 格式化 同步记录
		$records = $this->formatRecords($this->parseAssociationPrimaryKeyValues($ids));

		// 分离
		$detachIds = array_diff($currentAssociationForeignKeyValues, array_keys($records));
		if ($detaching && count($detachIds) > 0) {
			$this->detach($detachIds);
			$changes['detached'] = $detachIds;
		}

		// 附加
		$attachIds = array_diff(array_keys($records), $currentAssociationForeignKeyValues);
		$attach = array_intersect_key($records, array_flip($attachIds));
		if ($attach) {
			$this->attach($attach);
			$changes['attached'] = $attachIds;
		}

		// 更新关系
		$existedIds = array_intersect(array_keys($records), $currentAssociationForeignKeyValues);
		$existed = array_intersect_key($records, array_flip($existedIds));
		if ($existed) {
			foreach ($existed as $id => $attributes) {
				if (count($attributes) > 0 && $this->updateExistingPivot($id, $attributes)) {
					$changes['updated'][] = $id;
				}
			}
		}

		return $changes;
	}

}
