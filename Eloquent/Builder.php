<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

// 调用转发 trait
use Xzb\Ci3\Helpers\Traits\ForwardsCalls;

// 异常类
use Xzb\Ci3\Database\Exception\{
	// 插入失败
	InsertFailedException,
	// 更新失败
	UpdateFailedException,
	// 查询失败
	SelectFailedException,
	// 删除失败
	DeleteFailedException,
	// 缺少插入数据
	MissingInsertDataException,
	// 找到多个记录
	MultipleRecordsFoundException,
	// 未找到记录
	RecordsNotFoundException,
	// 模型未找到
	ModelNotFoundException
};

// PHP 匿名函数类
use Closure;

/**
 * Eloquent 查询构造器类
 */
class Builder
{
	use ForwardsCalls;

	/**
	 * 构造函数
	 * 
	 * @return void
	 */
	public function __construct(\CI_DB_query_builder $query)
	{
		$this->query = $query;
	}

// ---------------------- 查询作用域 ----------------------
	/**
	 * 查询作用域
	 * 
	 * @var array
	 */
	protected $queryScopes;

	/**
	 * 注册 查询作用域
	 * 
	 * @param string $identifier
	 * @param \Closure $scope
	 * @return $this
	 */
	public function queryScope($identifier, Closure $scope)
	{
		$this->queryScopes[$identifier] = $scope;

		return $this;
	}

	/**
	 * 重置 查询作用域
	 * 
	 * @param string $identifier
	 * @return $this
	 */
	public function resetQueryScope($identifier)
	{
		unset($this->queryScope[$identifier]);

		return $this;
	}

	/**
	 * 应用 查询作用域
	 * 
	 * @return $this
	 */
	public function applyQueryScopes()
	{
		if ($this->queryScopes) {
			foreach ($this->queryScopes as $identifier => $scope) {
				// 执行
				call_user_func($scope, $this);
				// 删除 防止重复执行
				unset($this->queryScopes[$identifier]);
			}
		}

		return $this;
	}

// ---------------------- 本地宏 ----------------------
	/**
	 * 本地宏
	 * 
	 * @var array
	 */
	protected $localMacros = [];

	/**
	 * 设置 本地宏
	 * 
	 * @param string $key
	 * @param \Closure
	 * @return void
	 */
	public function macro(string $key, Closure $callback): void
	{
		$this->localMacros[$key] = $callback;
	}

	/**
	 * 删除 本地宏
	 * 
	 * @param string $key
	 * @return $this
	 */
	public function removeMacro(string $key)
	{
		unset($this->localMacros[$key]);

		return $this;
	}

	/**
	 * 是否有 本地宏
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function hasMacro(string $key): bool
	{
		return isset($this->localMacros[$key]);
	}

	/**
	 * 执行 本地宏
	 * 
	 * @param string $key
	 * @param array $parameters
	 * @return mixed
	 */
	public function performMacro(string $key, array $parameters = [])
	{
		array_unshift($parameters, $this);

		return $this->localMacros[$key](...$parameters);
	}

// ---------------------- 查询构造器 ----------------------
	/**
	 * 基础 查询构造器 实例
	 * 
	 * @var \CI_DB_query_builder 
	 */
	protected $query;

	/**
	 * 设置 基础查询构造器 实例
	 * 
	 * @param \CI_DB_query_builder $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		$this->query = $query;

		return $this;
	}

	/**
	 * 获取 基础查询构造器 实例
	 * 
	 * @return \CI_DB_query_builder
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * 获取 基础查询构造器 属性值
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function getQueryPropertyValue(string $property)
	{
		if (strncmp($property, $prefix = 'qb_', strlen($prefix)) !== 0) {
			$property = $prefix . $property;
		}

		$reflectionProperty = (new \ReflectionClass($this->query))->getProperty($property);
		$reflectionProperty->setAccessible(true);

		return $reflectionProperty->getValue($this->query);
	}

// ---------------------- 查询模型 ----------------------
	/**
	 * 查询模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * 设置 查询模型 实例
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $model
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = $model;

		// 设置 数据表
		if (! $this->getQueryPropertyValue('from')) {
			$this->query->from($model->getTable());
		}

		return $this;
	}

	/**
	 * 获取 查询模型 实例
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * 创建 查询模型 新实例
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function newModelInstance($attributes = [])
	{
		return $this->model->newInstance($attributes);
	}

// ---------------------- 创建 ----------------------
	/**
	 * 创建
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function create(array $attributes)
	{
		$model = $this->newModelInstance($attributes);

		$model->save();

		return $model;
	}

	/**
	 * 插入
	 * 
	 * @param arary $values
	 * @return int
	 * 
	 * @throws \Xzb\Ci3\Database\Exceptio\MissingInsertDataException
	 * @throws \Xzb\Ci3\Database\Exceptio\InsertFailedException
	 */
	public function insert(array $values): int
	{
		if (empty($values)) {
			throw (new MissingInsertDataException($this->error()))->setModel($this->model);
		}

		// 检测 是否为 二维数组
		if (! is_array(reset($values))) {
			// 转为 二维数组
			$values = [$values];
		}
		else {
			// 循环 排序key
			foreach ($values as $key => $value) {
				ksort($value);

				$values[$key] = $value;
			}
		}

		$rows = $this->query->insert_batch('', $values);
		if (! $rows) {
			throw (new InsertFailedException($this->error()))->setModel($this->model);
		}

		return $rows;
	}

// ---------------------- 更新 ----------------------
	/**
	 * 更新
	 * 
	 * @param array $attributes
	 * @return int
	 * 
	 * @throws \Xzb\Ci3\Database\Exception\UpdateFailedException
	 */
	public function update(array $attributes): int
	{
		// 更新
		$result = $this->query->set($this->addUpdatedAtColumn($attributes))->update();
		if (! $result) {
			throw (new UpdateFailedException($this->error()))->setModel($this->model);
		}

		// 影响条数
		$rows = $this->query->affected_rows();

		return $rows;
	}

	/**
	 * 添加 更新时间 列
	 * 
	 * @param array $values
	 * @return array
	 */
	protected function addUpdatedAtColumn(array $values): array
	{
		if (
			// 自动维护 操作时间
			$this->model->usesTimestamps()
			// 更新时间列 存在
			&& $this->model->getUpdatedAtColumn()
		) {
			$column = $this->model->getUpdatedAtColumn();
			$time = $this->model->freshStorageDateFormatTimestamp();

			$values = array_merge([ $column => $time ], $values);
		}

		return $values;
	}

// ---------------------- 删除 ----------------------
	/**
	 * 删除功能 替换函数
	 * 
	 * @var \Closure
	 */
	protected $onDelete;

	/**
	 * 注册 替换默认删除功能 替换函数
	 * 
	 * @param \Closure $callback
	 * @return void
	 */
	public function onDelete(Closure $callback): void
	{
		$this->onDelete = $callback;
	}

	/**
	 * 删除
	 * 
	 * @return int
	 */
	public function delete(): int
	{
		if (isset($this->onDelete)) {
			return call_user_func($this->onDelete, $this);
		}

		return $this->forceDelete();
	}

	/**
	 * 强制 删除
	 * 
	 * @return int
	 * 
	 * @throws \Xzb\Ci3\Database\Exception\DeleteFailedException
	 */
	public function forceDelete(): int
	{
		// 删除
		$result = $this->query->delete();
		if (! $result) {
			throw (new DeleteFailedException($this->error()))->setModel($this->model);
		}

		// 影响条数
		$rows = $this->query->affected_rows();

		return $rows;
	}

// ---------------------- 读取 ----------------------
	/**
	 * 获取 模型集合
	 * 
	 * @param array|string $columns
	 * @return array
	 * 
	 * @throws \Xzb\Ci3\Database\Exception\SelectFailedException
	 */
	public function getModels($columns = ['*']): array
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		$query = $this->query->select($columns)->get();
		if ($query === false) {
			throw (new SelectFailedException($this->error()))->setModel($this->model);
		}

		$instance = $this->newModelInstance();

		return array_map(function ($item) use ($instance) {
			return $instance->newInstanceFromBuilder($item);
		}, $query->result_array());
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

		$models = $this->applyQueryScopes()->getModels($columns);

		return $this->newModelInstance()->newCollection($models);
	}

	/**
	 * 读取 查询结果的第一条记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Model|null
	 */
	public function first($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		return $this->take(1)->get($columns)->first();
	}

	/**
	 * 唯一记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 * 
	 * @throws \Xzb\Ci3\Database\Exception\RecordsNotFoundException
	 * @throws \Xzb\Ci3\Database\Exception\MultipleRecordsFoundException
	 */
	public function baseSole($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();
		$result = $this->take(2)->get($columns);

		$count = $result->count();
		if ($count === 0) {
			throw (new RecordsNotFoundException)->setModel($this->model);
		}

		if ($count > 1) {
			throw (new MultipleRecordsFoundException($count . ' records were found'))->setModel($this->model);
		}

		return $result->first();
	}

	/**
	 * 读取 唯一记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 * 
	 * @throws \Xzb\Ci3\Database\Exception\ModelNotFoundException
	 */
	public function sole($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		try {
			return $this->baseSole($columns);
		}
		catch (RecordsNotFoundException $exception) {
			throw (new ModelNotFoundException('No query results for model [' . get_class($this->model) . '] '))->setModel($this->model);
		}
	}

	/**
	 * 总数
	 * 
	 * @return int
	 */
	public function count(): int
	{
		return $this->applyQueryScopes()->query->count_all_results();
	}

	/**
	 * 偏移量 分页
	 * 
	 * @param int|null $perPage
	 * @param array|string $columns
	 * @param string $pageName
	 * @param int|null $page
	 * @return \Xzb\Ci3\Database\Eloquent\Paginator
	 */
	public function offsetPaginate($perPage = null, $columns = [], $pageName = 'page', $page = null)
	{
		$page = $page ?: Paginator::resolveCurrentPage($pageName);
		$perPage = $perPage ?: $this->model->getPerPage();

		$builder = $this->applyQueryScopes();

		$total = $builder->query->count_all_results('', $reset = false);

		$results = $total
					? $builder->forPage($page, $perPage)->get($columns)
					: $this->model->newCollection();

		return new Paginator($results, $total, $perPage, $page);
	}

// ---------------------- 查询构造器 扩展 ----------------------
	/**
	 * 错误
	 * 
	 * @return string
	 */
	public function error()
	{
		$error = $this->query->error();

		$message = 'SQL Error';
		if ($error['code'] ?? false) {
			$message .= '(' . $error['code'] . ')';
		}
		if ($error['message'] ?? false) {
			$message .= ': ' . $error['message'];
		}

		$message .= ' - Invalid query: ' . $this->query->last_query();

		return $message;
	}

	/**
	 * 查询 列
	 * 
	 * SELECT
	 * 
	 * @param array|string
	 * @return $this
	 */
	public function select($columns)
	{
		$this->query->select(
			is_array($columns) ? $columns : func_get_args()
		);

		return $this;
	}

	/**
	 * 多 条件
	 * 
	 * AND WHERE
	 * 
	 * @param array $where
	 * @return $this
	 */
	public function whereBatch(array $wheres)
	{
		foreach ($wheres as $column => $value) {
			is_array($value)
				? $this->query->where_in($column, $value)
				: $this->query->where($column, $value);
		}

		return $this;
	}

	/**
	 * 主键 条件
	 * 
	 * AND WHERE
	 * 
	 * @param mixed $id
	 * @param array $wheres
	 * @return $this
	 */
	public function wherePrimaryKey($id, array $wheres = [])
	{
		if (in_array($this->model->getPrimaryKeyType(), ['int', 'integer'])) {
			$id = is_array($id)
					? array_map(function ($value) { return (int)$value; }, $id)
					: (int)$id;
		}

		$wheres = array_merge([
			$this->model->getPrimaryKeyName() => $id
		], $wheres);

		return $this->whereBatch($wheres);
	}

	/**
	 * 多 模糊匹配
	 * 
	 * AND (LIKE OR LIKE)
	 * 
	 * @param array $columns
	 * @param string $keyword
	 * @param string $side
	 * @return $this
	 */
	public function likeBatch(array $columns = [], string $keyword = null, string $side = 'both')
	{
		if ($columns && strlen($keyword)) {

			// 条件组 开始
			count($columns) > 1 && $this->query->group_start();

			// 是否为 第一个
			$isFirst = true;
			foreach ($columns as $column) {
				// 第一个
				if ($isFirst) {
					$this->query->like($column, $keyword, $side);
					$isFirst = false;
					continue;
				}

				// 其它 OR
				$this->query->or_like($column, $keyword, $side);
			}

			// 条件组 结束
			count($columns) > 1 && $this->query->group_end();
		}

		return $this;
	}

	/**
	 * 多 排序
	 * 
	 * ORDER BY
	 * 
	 * @param array $orderBy
	 * @return $this
	 */
	public function orderByBatch(array $orderBy)
	{
		foreach ($orderBy as $column => $direction) {
			$this->query->order_by($column, $direction);
		}

		return $this;
	}

	/**
	 * 多 分组
	 * 
	 * GROUP BY
	 * 
	 * @param array $columns
	 * @return $this
	 */
	public function groupByBatch(array $columns = [])
	{
		// 分组
		$this->group_by($columns);

		return $this;
	}

	/**
	 * limit 别名
	 * 
	 * LIMIT
	 * 
	 * @param int $value
	 * @return $this
	 */
	public function take($value)
	{
		$this->query->limit($value);

		return $this;
	}

	/**
	 * 设置 分页 查询条数和偏移量
	 * 
	 * @param int $page
	 * @param int $perPage
	 * @return $this
	 */
	public function forPage($page, $perPage)
	{
		$this->query->offset(((int)$page - 1) * (int)$perPage)->limit((int)$perPage);

		return $this;
	}

// ---------------------- 魔术方法 ----------------------
	/**
	 * 处理调用 不可访问 方法
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if ($this->hasMacro($method)) {
			return $this->performMacro($method, $parameters);
		}

		$this->forwardCallTo($this->query, $method, $parameters);

		return $this;
	}

}
