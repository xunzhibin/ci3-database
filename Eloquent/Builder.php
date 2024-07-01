<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

// 调用转发 trait
use Xzb\Ci3\Helpers\Traits\ForwardsCalls;

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
	public function macro(string $key, \Closure $callback)
	{
		$this->localMacros[$key] = $callback;
	}

	/**
	 * 是否有 本地宏
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function hasMacro(string $key)
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
	public function performMacro(string $key, array $parameters)
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

// ---------------------- 查询模型 ----------------------
	/**
	 * 查询模型 实例
	 * 
	 * @var \Xzb\Ci3\Core\Eloquent\Model
	 */
	protected $model;

	/**
	 * 设置 查询模型 实例
	 * 
	 * @param \Xzb\Ci3\Core\Eloquent\Model $model
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = $model;

		$qbFromReflectionProperty = (new \ReflectionClass($this->query))->getProperty('qb_from');
		$qbFromReflectionProperty->setAccessible(true);
		// 未设置 数据表
		if (! $qbFromReflectionProperty->getValue($this->query)) {
			// 设置 数据表
			$this->query->from($model->getTable());
		}

		return $this;
	}

	/**
	 * 获取 查询模型 实例
	 * 
	 * @return \Xzb\Ci3\Core\Eloquent\Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * 创建 查询模型 新实例
	 * 
	 * @param array $attributes
	 * @return \Xzb\Ci3\Core\Eloquent\Model
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
	 * @return \Xzb\Ci3\Core\Eloquent\Model
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
	 * @return bool
	 */
	public function insert(array $values)
	{
		if (empty($values)) {
			throw new RuntimeException('No insert data on model [' . get_class($this->model) . ']');
		}

		if (! is_array(reset($values))) {
			$values = [$values];
		}
		else {
			foreach ($values as $key => $value) {
				ksort($value);

				$values[$key] = $value;
			}
		}

		$rows = $this->query->insert_batch('', $values);
		if (! $rows) {
			throw new QueryException($message = $modelQuery->error());
		}

		return true;
	}

// ---------------------- 更新 ----------------------
	/**
	 * 更新
	 * 
	 * @param array $attributes
	 * @return int
	 */
	public function update(array $attributes)
	{
		// 更新
		$result = $this->query->set($attributes)->update();
		// 影响条数
		$rows = $this->query->affected_rows();
		if (! $result) {
			throw new QueryException($message = $modelQuery->error());
		}

		return $rows;
	}

// ---------------------- 删除 ----------------------
	/**
	 * 删除
	 * 
	 * @return int
	 */
	public function delete()
	{
		if ($this->hasMacro($method = 'onDelete')) {
			return $this->performMacro($method, $parameters = []);
		}

		return $this->forceDelete();
	}

	/**
	 * 强制 删除
	 * 
	 * @return int
	 */
	public function forceDelete()
	{
		// 删除
		$result = $this->query->delete();
		// 影响条数
		$rows = $this->query->affected_rows();
		if (! $result) {
			throw new QueryException($message = $modelQuery->error());
		}

		return $rows;
	}

// ---------------------- 软删除 ----------------------
	// 恢复 Restore
	// 恢复 或 创建 RestoreOrCreate
	// 全部 WithTrashed
	// 未删除 WithoutTrashed
	// 已删除 OnlyTrashed

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

	//     /**
    //  * Dynamically handle calls into the query instance.
    //  *
    //  * @param  string  $method
    //  * @param  array  $parameters
    //  * @return mixed
    //  */
    // public function __call($method, $parameters)
    // {
    //     if ($method === 'macro') {
    //         $this->localMacros[$parameters[0]] = $parameters[1];

    //         return;
    //     }

    //     if ($this->hasMacro($method)) {
    //         array_unshift($parameters, $this);

    //         return $this->localMacros[$method](...$parameters);
    //     }

    //     if (static::hasGlobalMacro($method)) {
    //         $callable = static::$macros[$method];

    //         if ($callable instanceof Closure) {
    //             $callable = $callable->bindTo($this, static::class);
    //         }

    //         return $callable(...$parameters);
    //     }

    //     if ($this->hasNamedScope($method)) {
    //         return $this->callNamedScope($method, $parameters);
    //     }

    //     if (in_array($method, $this->passthru)) {
    //         return $this->toBase()->{$method}(...$parameters);
    //     }

    //     $this->forwardCallTo($this->query, $method, $parameters);

    //     return $this;
    // }

    // /**
    //  * Dynamically handle calls into the query instance.
    //  *
    //  * @param  string  $method
    //  * @param  array  $parameters
    //  * @return mixed
    //  *
    //  * @throws \BadMethodCallException
    //  */
    // public static function __callStatic($method, $parameters)
    // {
    //     if ($method === 'macro') {
    //         static::$macros[$parameters[0]] = $parameters[1];

    //         return;
    //     }

    //     if ($method === 'mixin') {
    //         return static::registerMixin($parameters[0], $parameters[1] ?? true);
    //     }

    //     if (! static::hasGlobalMacro($method)) {
    //         static::throwBadMethodCallException($method);
    //     }

    //     $callable = static::$macros[$method];

    //     if ($callable instanceof Closure) {
    //         $callable = $callable->bindTo(null, static::class);
    //     }

    //     return $callable(...$parameters);
    // }

}
