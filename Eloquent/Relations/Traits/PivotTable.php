<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations\Traits;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

// 中间表 支点类
use Xzb\Ci3\Database\Eloquent\Relations\Pivot;
// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;
// 模型 Eloquent 查询构造器类
use Xzb\Ci3\Database\Eloquent\Builder;
// 集合
use Xzb\Ci3\Database\Eloquent\Collection;

/**
 * 支点 中间表
 */
trait PivotTable
{
	/**
	 * 中间表
	 * 
	 * @var string
	 */
	protected $table;

	/**
	 * 中间表 是否有时间戳
	 * 
	 * @var bool
	 */
	public $pivotTimestamps = false;

	/**
	 * 中间表 创建时间 列名
	 * 
	 * @var string
	 */
	protected $pivotCreatedAt;

	/**
	 * 中间表 更新时间 列名
	 * 
	 * @var string
	 */
	protected $pivotUpdatedAt;

	/**
	 * 中间表 日期属性 存储格式
	 * 
	 * @var string
	 */
	protected $pivotDateFormat;

	/**
	 * 中间表 模型 类名
	 * 
	 * @var string
	 */
	protected $using;

	/**
	 * 中间表 列的 默认值
	 * 
	 * @var array
	 */
	protected $pivotValues = [];

	/**
	 * 中间表 查询列
	 * 
	 * @var array
	 */
	protected $pivotColumns = [];

	/**
	 * 解析 中间表名
	 * 
	 * @param string $table
	 * @return string
	 */
	protected function resolveTableName(string $table)
	{
		if (! Str::contains($table, '\\') || ! class_exists($table)) {
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

	/**
	 * 中间表 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function pivotQualifyColumn(string $column)
	{
		return Str::contains($column, '.')
					? $column
					: $this->table.'.'.$column;
	}

// ---------------------- 操作时间列 ----------------------
	/**
	 * 获取 中间表 创建时间 列名称
	 * 
	 * @return string|null
	 */
	public function getPivotCreatedAtColumn()
	{
		return $this->pivotCreatedAt ?: $this->parent->getCreatedAtColumn();
	}

	/**
	 * 获取 中间表 更新时间 列名称
	 * 
	 * @return string|null
	 */
	public function getPivotUpdatedAtColumn()
	{
		return $this->pivotUpdatedAt ?: $this->parent->getUpdatedAtColumn();
	}

// ---------------------- 模型 实例----------------------
	/**
	 * 设置 中间表 模型类名
	 * 
	 * @param string $class
	 * @return $this
	 */
	public function using($class)
	{
		$this->using = $class;

		return $this;
	}

	/**
	 * 获取 中间表 模型类名
	 * 
	 * @return string
	 */
	public function getPivotClass()
	{
		return $this->using ?? Pivot::class;
	}

	/**
	 * 新建 中间表 模型实例
	 * 
	 * @param array $attributes
	 * @param bool $exists
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\Pivot
	 */
	public function newPivotInstance(array $attributes = [], $exists = false)
	{
		if ($this->using) {
			$pivot = $this->using::newPivotRawInstance(
				$attributes, $this->table, $exists, $this->pivotTimestamps, $this->pivotDateFormat
			);
		}
		else {
			$pivot = Pivot::newPivotInstance(
				$attributes, $this->table, $exists, $this->pivotTimestamps, $this->pivotDateFormat
			);
		}

		return $pivot->setPivotKeys($this->parentForeignKey, $this->relatedForeignKey);
	}

	/**
	 * 新建 中间表 模型实例
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\Pivot
	 */
	public function newExistingPivotInstance(array $attributes = [])
	{
		return $this->newPivotInstance($attributes, true);
	}

// ---------------------- 查询构造器 扩展 ----------------------
	/**
	 * 设置 中间表 列的 默认值
	 * 
	 * @param string|array $columns
	 * @param mixed $value
	 * @return $this
	 */
	public function withPivotValue($columns, $value = null)
	{
		if (! is_array($columns)) {
			$columns = [ $columns => $value];
		}

		foreach ($columns as $column => $value) {
			if (is_null($value)) {
				throw new InvalidArgumentException('The provided value may not be null.');
			}

			$this->pivotValues[] = compact('column', 'value');

			$this->wherePivot($column, $value);
		}

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
		$this->pivotColumns = array_merge(
			$this->pivotColumns, is_array($columns) ? $columns : func_get_args()
		);

		return $this;
	}

	/**
	 * 获取 中间表 查询列
	 * 
	 * @return array
	 */
	protected function getPivotColumns()
	{
		$defaults = [
			$this->parentForeignKey,
			$this->relatedForeignKey
		];

		return array_unique(array_map(function ($column) {
			return $this->pivotQualifyColumn($column) . ' as pivot_' . $column;
		}, array_merge($defaults, $this->pivotColumns)));
	}

	/**
	 * 设置 中间表 自动维护 操作时间列
	 * 
	 * @param string $createdAt
	 * @param string $updateDAt
	 * @return $this
	 */
	public function withTimestamps(string $createdAt = null, string $updatedAt = null, string $dateFormat = null)
	{
		$this->pivotTimestamps = true;
		$this->pivotDateFormat = $dateFormat ?: $this->parent->getDateFormat();

		$this->pivotCreatedAt = $createdAt;
		$this->pivotUpdatedAt = $updatedAt;

		return $this;
		// return $this->withPivot($this->getPivotCreatedAtColumn(), $this->getPivotUpdatedAtColumn());
	}

	/**
	 * 设置 中间表 查询条件
	 * 
	 * @param string $column
	 * @param mixed $value
	 * @return $this
	 */
	public function wherePivot($column, $value = null)
	{
		return $this->where($this->pivotQualifyColumn($column), $value);
	}

	/**
	 * 设置 中间表 排序
	 * 
	 * @param string $column
	 * @param string $direction
	 * @return $this
	 */
	public function orderByPivot($column, $direction = 'asc')
	{
		return $this->orderBy($this->pivotQualifyColumn($column), $direction);
	}

// ---------------------- 附加 ----------------------
	/**
	 * 附加
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return void
	 */
	public function attach($id, array $attributes = [])
	{
		return $this->using
					? $this->attachUsingCustomClass($id, $attributes)
					: $this->attachUsingDefault($id, $attributes);
	}

	/**
	 * 附加 自定义类
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return void
	 */
	protected function attachUsingCustomClass($id, array $attributes)
	{
		$records = $this->formatAttachRecords(
			$this->parseRelatedPrimaryKeyValues($id),
			$attributes
		);

		foreach ($records as $record) {
			$this->newPivotInstance($record)->save();
		}
	}

	/**
	 * 附加  默认类
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return void
	 */
	protected function attachUsingDefault($id, array $attributes)
	{
		$records = $this->formatAttachRecords(
			$this->parseRelatedPrimaryKeyValues($id),
			$attributes
		);

		foreach ($records as $record) {
			$record = $this->newPivotInstance($record)->getInsertAttributes();
			$this->newPivotQuery()->insert($record);
		}
	}

// ---------------------- 分离 ----------------------
	/**
	 * 分离
	 * 
	 * @param mixed $ids
	 * @param array $wheres
	 * @return int
	 */
	public function detach($ids = null)
	{
		return $this->using && !empty($ids)
					? $this->detachUsingCustomClass($ids)
					: $this->detachUsingDefault($ids);
	}

	/**
	 * 分离 自定义类
	 * 
	 * @param mixed $ids
	 * @return int
	 */
	protected function detachUsingCustomClass($ids)
	{
		$results = 0;
		foreach ($this->parseRelatedPrimaryKeyValues($ids) as $id) {
			$results += $this->newPivotInstance([
				$this->parentForeignKey => $this->parent->{$this->parentPrimaryKey},
				$this->relatedForeignKey => $id
			], true)->delete();
		}
	
		return $results;
	}

	/**
	 * 分离 默认
	 * 
	 * @param mixed $ids
	 * @return int
	 */
	protected function detachUsingDefault($ids)
	{
		$pivotWhere = [
			$this->parentForeignKey => $this->parent->{$this->parentPrimaryKey}
		];

		if (! is_null($ids)) {
			$ids = $this->parseRelatedPrimaryKeyValues($ids);
			if (empty($ids)) {
				return 0;
			}
	
			$pivotWhere[$this->relatedForeignKey] = (array)$ids;
		}

		return $this->newPivotInstance()->newQuery()
						->where($pivotWhere)
						->delete();
	}

// ---------------------- 更新 ----------------------
	/**
	 * 更新
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return int
	 */
	public function updateExistingPivot($id, array $attributes)
	{
		return $this->using
					? $this->updateUsingCustomClass($id, $attributes)
					: $this->updateUsingDefault($id, $attributes);
	}

	/**
	 * 更新 自定义类
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return int
	 */
	protected function updateUsingCustomClass($id, array $attributes)
	{
		$pivot = $this->newPivotQueryForParentForeignKey()->where(
			$this->relatedForeignKey,
			$this->parseRelatedPrimaryKeyValues($id)
		)->get()->first();

		$updated = $pivot ? $pivot->fill($attributes)->hasEditedAttributes() : false;

		if ($updated) {
			$pivot->save();
		}

		return (int)$updated;
	}

	/**
	 * 更新 默认类
	 * 
	 * @param mixed $id
	 * @param array $attributes
	 * @return int
	 */
	protected function updateUsingDefault($id, array $attributes)
	{
		return $this->newPivotQueryForParentForeignKey()->where(
			$this->relatedForeignKey,
			$this->parseRelatedPrimaryKeyValues($id)
		)->update(
			$this->newPivotInstance($attributes, true)->getUpdateAttributes()
		);
	}

// ---------------------- 同步 ----------------------
	/**
	 * 同步
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Collection|\Xzb\Ci3\Database\Eloquent\Model| array $ids
	 * @param bool $detaching
	 * @return array
	 */
	public function sync($ids, bool $detaching = true)
	{
		$changes = [
			'attached' => [], 'detached' => [], 'updated' => [],
		];

		// 当前 附加关联
		$collection = $this->getCurrentlyAttachedPivots();
		$current = array_unique($collection->pluck($this->relatedForeignKey)->all());

		// 格式化 记录
		$records = $this->formatSyncRecords($this->parseRelatedPrimaryKeyValues($ids));

		// 分离
		if ($detaching) {
			$detach = array_diff($current, array_keys($records));
			if (count($detach) > 0) {
				$this->detach($detach);
				// $changes['detached'] = $this->castKeys($detach);
			}
		}

		// 附加
		$attach = array_diff_key($records, array_flip($current));
		if ($attach) {
			$this->attach($attach);
			// $changes['attached'][] = $this->castKey($id);
		}

		// 更新
		$update = array_intersect_key($records, array_flip($current));
		if ($update) {
			foreach ($update as $id => $attributes) {
				if (count($attributes)) {
					if ($this->updateExistingPivot($id, $attributes)) {
						// $changes['updated'][] = $this->castKey($id);
					}
				}
			}
		}

		return $changes;
	}

// ---------------------- 格式化 ----------------------
	/**
	 * 解析 关联 主键值
	 * 
	 * @param mixed $value
	 * @return array
	 */
	protected function parseRelatedPrimaryKeyValues($value)
	{
		// 属于 Eloquent 模型类
		if ($value instanceof Model) {
			return [ $value->{$this->relatedPrimaryKey} ];
		}

		// 属于 Eloquent 集合类
		if ($value instanceof Collection) {
			return $value->pluck($this->relatedPrimaryKey)->all();
		}

		return (array)$value;
	}

	/**
	 * 格式化 附加 记录
	 * 
	 * @param array $ids
	 * @param array $attributes
	 * @return array
	 */
	protected function formatAttachRecords(array $ids, array $attributes)
	{
		$records = [];

		// 默认值
		foreach ($this->pivotValues as $value) {
			$attributes[$value['column']] = $value['value'];
		}

		foreach ($ids as $key => $value) {
			$id = is_array($value) ? $key : $value;
			$record = is_array($value) ? array_merge($value, $attributes) : $attributes;

			// 父模型 外键
			$record[$this->parentForeignKey] = $this->parent->{$this->parentPrimaryKey};
			// 关联模型 外键
			$record[$this->relatedForeignKey] = $id;

			$records[] = $record;
		}

		return $records;
	}

	/**
	 * 格式化 同步 记录
	 * 
	 * @param array $ids
	 * @return array
	 */
	protected function formatSyncRecords(array $ids)
	{
		$records = [];

		foreach ($ids as $key => $value) {
			$id = is_array($value) ? $key : $value;
			$record = is_array($value) ? $value : [];

			$records[$id] = $record;
		}

		return $records;
	}

	/**
	 * 获取 当前 附加 中间表 记录
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	protected function getCurrentlyAttachedPivots()
	{
		return $this->newPivotQueryForParentForeignKey()->get();
	}

	/**
	 * 新建 中间表 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function newPivotQueryForParentForeignKey()
	{
		return $this->newPivotQuery()->where(
			$this->parentForeignKey,
			$this->parent->{$this->parentPrimaryKey}
		);
	}

	/**
	 * 新建 中间表 Eloquent 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function newPivotQuery()
	{
		return $this->newPivotInstance()->newQuery();
	}
}
