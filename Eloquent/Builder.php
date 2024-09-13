<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

use Xzb\Ci3\Database\Query\Builder AS QueryBuilder;

// 调用转发 trait
use Xzb\Ci3\Helpers\Traits\ForwardsCalls;

// 异常类
use Xzb\Ci3\Database\Query\RecordsNotFoundException;

// PHP 匿名函数类
use Closure;

/**
 * Eloquent 查询构造器类
 */
class Builder
{
	use ForwardsCalls;

	/**
	 * 查询构造器 实例
	 * 
	 * @var \Xzb\Ci3\Database\Query\Builder
	 */
	protected $query;

	/**
	 * 模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * 应用 全局作用域
	 * 
	 * @var array
	 */
	protected $scopes = [];

	/**
	 * 删除功能 替代项
	 * 
	 * @var \Colsure
	 */
	protected $onDelete;

	/**
	 * 本地宏
	 * 
	 * @var array
	 */
	protected $localMacros = [];

	/**
	 * 构造函数
	 * 
	 * @return void
	 */
	public function __construct(QueryBuilder $query)
	{
		$this->query = $query;
	}

// ---------------------- 查询构造器 ----------------------
	/**
	 * 设置 查询构造器 实例
	 * 
	 * @param \Xzb\Ci3\Database\Query\Builder $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		$this->query = $query;

		return $this;
	}

	/**
	 * 获取 查询构造器 实例
	 * 
	 * @return \Xzb\Ci3\Database\Query\Builder
	 */
	public function getQuery()
	{
		return $this->query;
	}

// ---------------------- 操作模型 ----------------------
	/**
	 * 设置 模型 实例
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model $model
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = $model;

		// 设置 数据表
		$this->query->from($model->getTable());

		return $this;
	}

	/**
	 * 获取 模型 实例
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

// ---------------------- 全局作用域 ----------------------
	/**
	 * 注册 全局作用域
	 * 
	 * @param string $identifier
	 * @param \Xzb\Ci3\Database\Eloquent\Scope|\Closure|string $scope
	 * @return $this
	 */
	public function withGlobalScope($identifier, $scope)
	{
		$this->scopes[$identifier] = $scope;

		if (method_exists($scope, 'extend')) {
			$scope->extend($this);
		}

		return $this;
	}

	/**
	 * 删除 全局作用域
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Scope|string $scope
	 * @return $this
	 */
	public function withoutGlobalScope($scope)
	{
		if (! is_string($scope)) {
			$scope = get_class($scope);
		}

		unset($this->scopes[$scope]);

		// $this->removedScopes[] = $scope;

		return $this;
	}

	/**
	 * 应用 作用域
	 * 
	 * @return static
	 */
	public function applyScopes()
	{
		if (! $this->scopes) {
			return $this;
		}

		$builder = clone $this;

		foreach ($this->scopes as $identifier => $scope) {
			if (! isset($builder->scopes[$identifier])) {
				continue;
			}

			$builder->callScope(function (self $builder) use ($scope) {
				if ($scope instanceof Closure) {
					$scope($builder);
				}

				if ($scope instanceof Scope) {
					$scope->apply($builder, $this->getModel());
				}
			});
		}

		return $builder;
	}

	/**
	 * 执行 作用域
	 * 
	 * @param callable $scope
	 * @param array $parameters
	 * @return mixed
	 */
	protected function callScope(callable $scope, array $parameters = [])
	{
		array_unshift($parameters, $this);

		$result = $scope(...$parameters) ?? $this;

		return $result;
	}

// ---------------------- 删除功能 ----------------------
	/**
	 * 注册 删除功能 替代项
	 * 
	 * @param \Closure $callback
	 * @return void
	 */
	public function onDelete(Closure $callback)
	{
		$this->onDelete = $callback;
	}

// ---------------------- 本地宏 ----------------------
	/**
	 * 是否为 本地宏
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function hasMacro($name)
	{
		return isset($this->localMacros[$name]);
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
	 * 创建 不触发事件
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 */
	public function createQuietly(array $attributes)
	{
		$model = $this->newModelInstance($attributes);

		$model->saveQuietly();

		return $model;
	}

// ---------------------- 读取 ----------------------
	/**
	 * 读取 唯一记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Model
	 * 
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelNotFoundException
	 */
	public function sole($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		try {
			return $this->newModelInstance()->newInstanceFromBuilder(
				$this->applyScopes()->getQuery()->sole($columns)->row_array()
			);
		}
		catch (RecordsNotFoundException $e) {
			throw (new ModelNotFoundException('No query results for model [' . get_class($this->model) . '] '))->setModel($this->model);
		}
	}

	/**
	 * 读取 记录
	 * 
	 * @param array
	 * @return \Xzb\Ci3\Database\Eloquent\Model[]
	 */
	public function getModels($columns = ['*'])
	{
		$results = $this->applyScopes()->getQuery()->get($columns)->result_array();

		$instance = $this->newModelInstance();

		return array_map(function ($item) use ($instance) {
			return $instance->newInstanceFromBuilder($item);
		}, $results);
	}

	/**
	 * 读取 记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	public function get($columns = ['*'])
	{
		$instance = $this->newModelInstance();

		return $instance->newCollection(
			$this->getModels(is_array($columns) ? $columns : func_get_args())
		);
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
	 * 偏移量 分页
	 * 
	 * @param int|null $perPage
	 * @param array|string $columns
	 * @param string $pageName
	 * @param int|null $page
	 * @return \Xzb\Ci3\Database\Eloquent\Paginator
	 */
	public function offsetPaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
	{
		$page = $page ?: Paginator::resolveCurrentPage($pageName);
		$perPage = $perPage ?: $this->model->getPerPage();

		$total = $this->applyScopes()->getQuery()->count();

		$results = $total
					? $this->forPage($page, $perPage)->get($columns)
					: $this->model->newCollection();

		return new Paginator($results, $total, $perPage, $page);
	}

// ---------------------- 更新 ----------------------
	/**
	 * 更新
	 * 
	 * @param array $values
	 * @return int
	 */
	public function update(array $values): int
	{
		return $this->applyScopes()->getQuery()->update($this->addUpdatedAtColumn($values));
	}

// ---------------------- 删除 ----------------------
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

		return $this->query->delete();
	}

	/**
	 * 强制 删除
	 * 
	 * @return int
	 */
	public function forceDelete(): int
	{
		return $this->query->delete();
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

		return $this->where($wheres);
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
		// 注册 本地宏
		if ($method === 'macro') {
			$this->localMacros[$parameters[0]] = $parameters[1];
			return;
		}

		// 执行 本地宏
		if ($this->hasMacro($method)) {
			array_unshift($parameters, $this);
			return $this->localMacros[$method](...$parameters);
		}

		$passthru = [
			'insert',
			'max',
			'exists',
			'transaction'
		];
		if (in_array($method, $passthru)) {
		// if (in_array($method, $this->passthru)) {
			return $this->applyScopes()->getQuery()->{$method}(...$parameters);
			// return $this->query->{$method}(...$parameters);
			// return $this->toBase()->{$method}(...$parameters);
		}

		$this->forwardCallTo($this->query, $method, $parameters);

		return $this;
	}

	/**
	 * 克隆
	 * 
	 * @return void
	 */
	public function __clone()
	{
		$this->query = clone $this->query;
	}

}
