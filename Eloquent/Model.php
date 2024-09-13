<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

// 调用转发 trait
use Xzb\Ci3\Helpers\Traits\ForwardsCalls;
// 转换 辅助函数
use Xzb\Ci3\Helpers\Transform;

// 数组式访问 接口
use ArrayAccess;
// JSON 序列化接口
use JsonSerializable;

/**
 * 模型 抽象类
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
	use Traits\HasBoots;
	use Traits\HasConnections;
	use Traits\HasTables;
	use Traits\HasPrimaryKeys;
	use Traits\HasInstances;
	use Traits\HasPerPages;
	use Traits\HasAttributes;
	use Traits\HasEvents;
	use Traits\HasGlobalScopes;
	use Traits\HasRelationships;
	use ForwardsCalls;

	/**
	 * 关联数据表中 是否存在 
	 * 
	 * @var bool
	 */
	public $exists = false;

	/**
	 * 是否为 当前请求生命周期内创建
	 * 
	 * @var bool
	 */
	public $wasRecentlyCreated = false;

    /**
	 * 构造函数
	 * 
	 * @param array $attributes
	 * @return void
	 */
	public function __construct(array $attributes = [])
	{
		// 未启动时 启动
		$this->bootIfNotBooted();

		// 初始化 特性
		$this->initializeTraits();

		// 同步 属性 原始状态
		$this->syncOriginal();

		// 填充 属性
		$this->fill($attributes);
	}

	/**
	 * 填充 模型 属性数组
	 * 
	 * @param array $attributes
	 * @return $this
	 */
	public function fill(array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$this->setAttribute($key, $value);
		}

		return $this;
	}


// ---------------------- 触发事件 ----------------------
	/**
	 * 保存
	 * 
	 * @return bool
	 */
	public function save(): bool
	{
		// saving 保存前 事件
		$this->fireModelEvent('saving');

		// 关联数据表中 存在时 更新; 否则 创建
		$saved = $this->exists
					// 更新
					? $this->performUpdate()
					// 插入
					: $this->performInsert();

		// saved 保存后 事件
		$this->fireModelEvent('saved');

		if ($saved) {
			// 同步 属性 原始状态
			$this->syncOriginal();
		}

		return $saved;
	}

	/**
	 * 更新
	 * 
	 * @param array $attributes
	 * @return bool
	 * 
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelNotFoundException
	 */
	public function update(array $attributes = []): bool
	{
		// 在关联数据表中 不存在
		if (! $this->exists) {
			throw (new ModelNotFoundException('Not exist for update model [' . static::class . ']'))->setModel(static::class);
		}

		return $this->fill($attributes)->save();
	}

	/**
	 * 重新加载 模型实例
	 * 
	 * @return static
	 */
	public function fresh()
	{
		// 在关联数据表中 不存在
		if (! $this->exists) {
			throw (new ModelNotFoundException('Not exist for fresh model [' . static::class . ']'))->setModel(static::class);
		}

		return $this->setPrimaryKeyWhereForRUD($this->newModelQuery())->first();
	}

	/**
	 * 删除
	 * 
	 * @return int
	 */
	public function delete(): int
	{
		// 在关联数据表中 不存在
		if (! $this->exists) {
			return true;
		}

		// deleting 删除前 事件
		$this->fireModelEvent('deleting');

		// 执行删除
		$rows = $this->performDelete();

		// deleted 删除后 事件
		$this->fireModelEvent('deleted');

		return $rows;
	}

	/**
	 * 强制删除
	 * 
	 * @return bool
	 */
	public function forceDelete()
	{
		return $this->delete();
	}

// ---------------------- 不触发事件 ----------------------
	/**
	 * 保存 不触发事件
	 * 
	 * @return bool
	 */
	public function saveQuietly(): bool
	{
		return static::withoutEvents(function () {
			return $this->save();
		});
	}

	/**
	 * 更新 不触发事件
	 * 
	 * @param array $attributes
	 * @return bool
	 * 
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelNotFoundException
	 */
	public function updateQuietly(array $attributes = []): bool
	{
		// 在关联数据表中 不存在
		if (! $this->exists) {
			throw (new ModelNotFoundException('Not exist for update model [' . static::class . ']'))->setModel(static::class);
		}

		return $this->fill($attributes)->saveQuietly();
	}

	/**
	 * 删除 不触发事件
	 * 
	 * @return bool
	 */
	public function deleteQuietly(): bool
	{
		return static::withoutEvents(function () {
			return $this->delete();
		});
	}

	/**
	 * 强制删除 不触发事件
	 * 
	 * @return int
	 */
	public function forceDeleteQuietly()
	{
		return $this->deleteQuietly();
	}

// ---------------------- 执行操作 ----------------------
	/**
	 * 执行插入
	 * 
	 * @return bool
	 */
	protected function performInsert(): bool
	{
		// creating 创建前 事件
		$this->fireModelEvent('creating');

		$result = $this->newModelQuery()->insert(
			$this->getInsertAttributes(),
			$this->getIncrementing()
		);
		if ($this->getIncrementing()) {
			// 设置 模型主键 属性
			$this->setAttribute($this->getPrimaryKeyName(), $result);
		}

		// 在关联数据表中 存在
		$this->exists = true;

		// 当前 请求生命周期内创建
		$this->wasRecentlyCreated = true;

		// created 创建后 事件
		$this->fireModelEvent('created');

		return true;
	}

	/**
	 * 执行更新
	 * 
	 * @return bool
	 */
	protected function performUpdate(): bool
	{
		// 属性 未被编辑
		if (! $this->hasEditedAttributes()) {
			return true;
		}

		// updating 更新前 事件
		$this->fireModelEvent('updating');

		// 设置 主键条件
		$rows = $this->setPrimaryKeyWhereForRUD($this->newModelQuery())
						// 更新
						->update(
							// 获取 更新操作 所有属性
							$this->getUpdateAttributes()
						);

		// 同步 更改属性
		$this->syncChangeAttributes();

		// updated 更新后 事件
		$this->fireModelEvent('updated');

		return true;
	}

	/**
	 * 执行删除
	 * 
	 * @return int
	 */
	protected function performDelete(): int
	{
		// 设置 主键条件
		$rows = $this->setPrimaryKeyWhereForRUD($this->newModelQuery())
						// 删除
						->delete();

		// 模型 在关联数据表中 不存在
		$this->exists = false;

		return $rows;
	}

	/**
	 * 设置 读取(R)更新(U)删除(D)操作 主键条件
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 * 
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelMissingAttributeException
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelMissingAttributeValueException
	 */
	protected function setPrimaryKeyWhereForRUD(Builder $modelQuery)
	{
		// 未设置 主键
		if (! $this->getPrimaryKeyName()) {
			throw (new ModelMissingAttributeException('No primary key defined for model [' . static::class . ']'));
		}

		// 主键值 不存在
		if (is_null($value = $this->getPrimaryKeyValueForRUD())) {
			throw (new ModelMissingAttributeValueException('No primary key value for model [' . static::class . ']'));
		}

		if ($this->getIncrementing()) {
			$value = (int)$value;
		}

		// 设置 AND 条件
		$modelQuery->where([
			$this->getPrimaryKeyName() => $value
		]);

		return $modelQuery;
	}

// ---------------------- 转换 ----------------------
	/**
	 * 模型 转换为 数组
	 * 
	 * @return array
	 */
	public function toArray(): array
	{
		return array_merge($this->attributesToArray(), $this->relationsToArray());
	}

	/**
	 * 模型 转换为 JSON字符串
	 * 
	 * @return string
	 * 
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelJsonEncodingFailureException
	 */
	public function toJson(): string
	{
		try {
			return Transform::toJson($this->jsonSerialize());
		}
		catch (\Throwable $e) {
			throw (new ModelJsonEncodingFailureException(
				'Error encoding model [' . static::class . '] with ID [' . $this->getPrimaryKeyValue() . '] to JSON: ' . json_last_error_msg(),
				$e->getCode(),
				$e
			))->setModel(static::class);
		}
	}

// ---------------------- PHP JsonSerializable(JSON序列化) 预定义接口 ----------------------
	/**
	 * 转为 JSON可序列化的数据
	 * 
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

// ---------------------- PHP ArrayAccess(数组式访问) 预定义接口 ----------------------
	/**
	 * 模型属性 是否存在
	 * 
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return ! is_null($this->getAttribute($offset));
	}

	/**
	 * 获取 模型属性
	 * 
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->getAttribute($offset);
	}

	/**
	 * 设置 模型属性
	 * 
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void
	{
		$this->setAttribute($offset, $value);
	}

	/**
	 * 销毁 模型属性
	 * 
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset): void
	{
		unset($this->attributes[$offset]);
	}

// ---------------------- 魔术方法 ----------------------
	/**
	 * 动态 设置 模型属性
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $key, $value): void
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * 动态 获取 模型属性
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * 动态 检测 模型属性 是否存在
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function __isset(string $key): bool
	{
		return $this->offsetExists($key);
	}

	/**
	 * 动态 销毁 模型属性
	 * 
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void
	{
		$this->offsetUnset($key);
	}

	/**
	 * 模型属性 转换为 字符串
	 * 
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->toJson();
	}

	/**
	 * 处理调用 不可访问 方法
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if ($method === 'query') {
			return $this->newBaseQueryBuilder()->query(...$parameters);
		}

		return $this->forwardCallTo($this->newQuery(), $method, $parameters);
	}

	/**
	 * 处理调用 不可访问 静态方法
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		// 静态方法 转为 普通方法
		return (new static)->$method(...$parameters);
	}

}
