<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

// 转换 辅助函数
use Xzb\Ci3\Helpers\Transform;
// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;
// 调用转发 trait
use Xzb\Ci3\Helpers\Traits\ForwardsCalls;

// 数组式访问 接口
use ArrayAccess;
// JSON 序列化接口
use JsonSerializable;

// 异常类
use Xzb\Ci3\Database\Exception\{
	// 模型不存在
	ModelNotFoundException,
	// 模型 缺少属性
	ModelMissingAttributeException,
	// 模型 缺少属性值
	ModelMissingAttributeValueException,
	// 模型 JSON编码失败
	ModelJsonEncodingFailureException
};

/**
 * 模型类
 */
class Model implements ArrayAccess, JsonSerializable
{
	use Traits\HasConnections;
	use Traits\HasTables;
    use Traits\HasAttributes;
    // use Traits\HasEvents;
    use Traits\HasTimestamps;
    use Traits\HasTransforms;
	use Traits\HasRelationships;
	use ForwardsCalls;

	/**
	 * 模型在 关联数据表中 是否存在 
	 * 
	 * @var bool
	 */
	public $exists = false;

	/**
	 * 构造函数
	 * 
	 * @return void
	 */
	public function __construct()
	{
		$this->initializeTraits();
	}

// ---------------------- trait 初始化 ----------------------
	/**
	 * trait 使用集合
	 * 
	 * @var array
	 */
	protected static $traits = [];

	/**
	 * 需要初始化 trait
	 * 
	 * @var array
	 */
	protected static $traitInitializers = [];

	/**
	 * 初始化 trait
	 * 
	 * @return void
	 */
	protected function initializeTraits(): void
	{
		if (! isset(static::$traitInitializers[static::class])) {
			static::cacheTraits($this);
		}

		foreach (static::$traitInitializers[static::class] as $method) {
			$this->{$method}();
		}
	}

	/**
	 * 缓存 trait
	 * 
	 * @return void
	 */
	protected static function cacheTraits(): void
	{
		$class = static::class;

		// trait 使用集合
		static::$traits[$class] = [];

		// 需要初始化 trait 集合
		static::$traitInitializers[$class] = [];

		foreach (class_traits($class) as $trait) {
			$trait = class_basename($trait);

			array_push(static::$traits[$class], $trait);

			// 初始化
			if (method_exists($class, $method = 'initialize' . class_basename($trait))) {
				static::$traitInitializers[$class][] = $method;

				static::$traitInitializers[$class] = array_unique(
					static::$traitInitializers[$class]
				);
			}
		}
	}

// ---------------------- 主键 ----------------------
	/**
	 * 模型 主键
	 * 
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * 模型 主键 数据类型
	 * 
	 * @var string
	 */
	protected $primaryKeyType = 'int';

	/**
	 * 模型 主键 是否自增
	 * 
	 * @var bool
	 */
    public $incrementing = true;

	/**
	 * 设置 模型主键 名称
	 * 
	 * @param string $key
	 * @return $this
	 */
	public function setPrimaryKeyName(string $key)
	{
		$this->primaryKey = $key;

		return $this;
	}

	/**
	 * 获取 模型主键 名称
	 * 
	 * @return string
	 */
	public function getPrimaryKeyName(): string
	{
		return $this->primaryKey;
	}

	/**
	 * 设置 模型主键 数据类型
	 * 
	 * @param string $type
	 * @return $this
	 */
	public function setPrimaryKeyType(string $type)
	{
		$this->primaryKeyType = $type;

		return $this;
	}

	/**
	 * 获取 模型主键 数据类型
	 * 
	 * @return string
	 */
	public function getPrimaryKeyType(): string
	{
		return $this->primaryKeyType;
	}

	/**
	 * 设置 模型主键 是否自增
	 * 
	 * @param bool $value
	 * @return $this
	 */
	public function setIncrementing(bool $value)
	{
		$this->incrementing = $value;

		return $this;
	}

	/**
	 * 获取 模型主键 是否自增
	 * 
	 * @return bool
	 */
	public function getIncrementing(): bool
	{
		return $this->incrementing;
	}

	/**
	 * 获取 模型主键 值
	 * 
	 * @return mixed
	 */
	public function getPrimaryKeyValue()
	{
		return $this->getAttribute($this->getPrimaryKeyName());
	}

	/**
	 * 获取 数据操作的 主键值
	 * 
	 * @return mixed
	 */
	protected function getPrimaryKeyValueForDML()
	{
		return $this->original[$this->getPrimaryKeyName()] ?? $this->getPrimaryKeyValue();
	}

	/**
	 * 获取 限定 模型主键
	 * 
	 * @return string
	 */
	public function getQualifyPrimaryKeyName(): string
	{
		return $this->qualifyColumn($this->getPrimaryKeyName());
	}

// ---------------------- 外键 ----------------------
	/**
	 * 获取 模型外键 名称
	 * 
	 * @return string
	 */
	public function getForeignKeyName(): string
	{
		return Str::snake(class_basename($this)) . '_' . $this->getPrimaryKeyName();
	}

// ---------------------- 限定列 ----------------------
	/**
	 * 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function qualifyColumn(string $column): string
	{
		if (str_contains($column, '.')) {
			return $column;
		}

		return $this->getTable() . '.' . $column;
	}

	/**
	 * 限定列
	 * 
	 * @param array $columns
	 * @return array
	 */
	public function qualifyColumns(array $columns): array
	{
		return array_map(function ($column) {
			return $this->qualifyColumn($column);
		}, $columns);
	}

// ---------------------- 模型转换 ----------------------
	/**
	 * 添加 属性访问器的 值
	 * 
	 * @param array $attributes
	 * @param array $accessorAttributes
	 * @return array
	 */
	protected function addAccessorAttributesToArray(array $attributes, array $accessorAttributes): array
	{
		foreach ($accessorAttributes as $key) {
			// 不存在
			if (! array_key_exists($key, $attributes)) {
				continue;
			}

			// 获取 属性访问器的 值
			$attributes[$key] = $this->getAccessorAttributeValue($key, $value = $attributes[$key]);
		}

		return $attributes;
	}

	/**
	 * 添加 属性强制转换的 值
	 * 
	 * @param array $attributes
	 * @param array $accessorAttributes
	 * @return array
	 */
	protected function addCastAttributesToArray(array $attributes, array $accessorAttributes): array
	{
		foreach ($this->getCastAttributes() as $key => $value) {
			// 不存在 或者 有属性访问器
			if ( ! array_key_exists($key, $attributes) || in_array($key, $accessorAttributes)) {
				continue;
			}

			// 转换 强制转换属性的 值
			$attributes[$key] = $this->transformCastAttributeValue($key, $value = $attributes[$key]);
		}

		return $attributes;
	}

	/**
	 * 模型属性 转换为 数组
	 * 
	 * @return array
	 */
	public function attributesToArray(): array
	{
		// 添加 属性访问器的 值
		$attributes = $this->addAccessorAttributesToArray(
			$attributes = $this->getAttributes(),
			// 访问器 属性
			$accessorAttributes = $this->getAccessorAttributes()
		);

		// 添加 属性强制转换的 值
		$attributes = $this->addCastAttributesToArray(
			$attributes, $accessorAttributes
		);

		return $attributes;
	}

	/**
	 * 模型关系 转换为 数组
	 * 
	 * @return array
	 */
	public function relationsToArray(): array
	{
		$attributes = [];

		foreach ($this->relations as $key => $value) {
			if (method_exists($value, $method = 'toArray')) {
				$relation = $value->{$method}();
			}
			else if (is_null($value)) {
				$relation = $value;
			}

			$key = Str::snake($key);

			if (isset($relation) || is_null($value)) {
				$attributes[$key] = $relation;
			}

			unset($relation);
		}

		return $attributes;
	}

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
	 * @throws \Xzb\Ci3\Database\Exception\ModelJsonEncodingFailureException
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

// ---------------------- 模型 新实例 ----------------------
	/**
	 * 创建 模型 新实例
	 * 
	 * @param array $attributes
	 * @param bool $exists
	 * @return static
	 */
	public function newInstance($attributes = [], $exists = false)
	{
		$model = new static;

		// 模型 在关联数据表中 是否存在
		$model->exists = $exists;

		// 设置 关联数据表
		$model->setTable($this->getTable());

		// 填充 属性
		$model->fill((array)$attributes);

		return $model;
	}

	/**
	 * 创建 模型 新实例 已存在
	 * 
	 * @param array $attributes
	 * @return static
	 */
	public function newInstanceFromBuilder($attributes = [])
	{
		$model = $this->newInstance([], true);

		$model->setRawAttributes((array)$attributes, true);

		return $model;
	}

	/**
	 * 创建 模型集合 新实例
	 * 
	 * @param array $models
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function newCollection(array $models = [])
	{
		return new Collection($models);
	}

// ---------------------- 保存 ----------------------
	/**
	 * 保存 模型
	 * 
	 * @return bool
	 */
	public function save(): bool
	{
		// 模型 查询构造器
		$modelQuery = $this->newModelQueryBuilder();

		// 模型 在关联数据表 存在时 更新; 否则 创建
		$saved = $this->exists
					// 更新
					? $this->performUpdate($modelQuery)
					// 插入
					: $this->performInsert($modelQuery);

		return $saved;
	}

// ---------------------- 更新 ----------------------
	/**
	 * 更新模型
	 * 
	 * @param array $attributes
	 * @return bool
	 * 
	 * @throws \Xzb\Ci3\Database\Exception\ModelNotFoundException
	 */
	public function update(array $attributes = []): bool
	{
		// 模型 在关联数据表中 不存在
		if (! $this->exists) {
			throw (new ModelNotFoundException('Not exist for update model [' . static::class . ']'))->setModel(static::class);
		}

		return $this->fill($attributes)->save();
	}

// ---------------------- 删除 ----------------------
	/**
	 * 删除模型
	 * 
	 * @return bool
	 */
	public function delete(): bool
	{
		// 模型 在关联数据表中 不存在
		if (! $this->exists) {
			return true;
		}

		// deleting 删除前 事件

		$this->performDelete();

		// deleted 删除后 事件

		return true;
	}

	/**
	 * 强制 删除
	 * 
	 * @return bool
	 */
	public function forceDelete(): bool
	{
		return $this->delete();
	}

// ---------------------- 执行操作 ----------------------
	/**
	 * 设置 数据操作 主键条件
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 * 
	 * @throws \Xzb\Ci3\Database\Excepton\ModelMissingAttributeException
	 * @throws \Xzb\Ci3\Database\Excepton\ModelMissingAttributeValueException
	 */
	protected function setPrimaryKeyWhereForDML(Builder $modelQuery)
	{
		// 未设置 主键
		if (! $this->getPrimaryKeyName()) {
			throw (new ModelMissingAttributeException('No primary key defined for model [' . static::class . ']'))->setModel(static::class);
		}

		// 主键值 不存在
		if (is_null($value = $this->getPrimaryKeyValueForDML())) {
			throw (new ModelMissingAttributeValueException('No primary key value for model [' . static::class . ']'))->setModel(static::class);
		}

		// 设置 AND 条件
		$modelQuery->whereBatch([
			$this->getPrimaryKeyName() => $value
		]);

		return $modelQuery;
	}

	/**
	 * 执行插入
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder
	 * @return bool
	 */
	protected function performInsert(Builder $modelQuery): bool
	{
		// creating 创建前 事件

		// 自动维护 操作时间
		if ($this->usesTimestamps()) {
			$this->updateTimestamps();
		}

		// 获取 插入操作 所有属性
		$attributes = $this->getAttributes();

		// 执行插入
		$result = $modelQuery->insert($attributes);

		// 数据表 自增主键
		$id = $modelQuery->insert_id();

		// 模型主键 自增
		if ($this->getIncrementing()) {
			// 设置 模型主键 属性
			$this->setAttribute($this->getPrimaryKeyName(), $id);
		}

		// 模型 在关联数据表中 存在
		$this->exists = true;
    
		// $this->wasRecentlyCreated = true;

		// 同步 原始属性
		$this->syncOriginalAttributes();

		// created 创建后 事件

		return true;
	}

	/**
	 * 执行更新
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder
	 * @return bool
	 */
	protected function performUpdate(Builder $modelQuery): bool
	{
		// 属性 未被编辑
		if (! $this->isEdited()) {
			return true;
		}

		// updating 更新前 事件

		// 自动维护 操作时间
		if ($this->usesTimestamps()) {
			$this->updateTimestamps();
		}

		// 获取 被修改 属性
		$editedAttributes = $this->getEditedAttributes();

		// 设置 主键条件
		$rows = $this->setPrimaryKeyWhereForDML($modelQuery)
						// 更新
						->update($editedAttributes);

		// 同步 更改属性
		$this->syncChangeAttributes();

		// 同步 原始属性
		$this->syncOriginalAttributes();

		// updated 更新后 事件

		return true;
	}

	/**
	 * 执行删除
	 * 
	 * @return bool
	 */
	protected function performDelete(): bool
	{
		// 设置 主键条件
		$rows = $this->setPrimaryKeyWhereForDML($this->newModelQueryBuilder())
						// 删除
						->delete();

		// 模型 在关联数据表中 不存在
		$this->exists = false;

		return true;
	}

// ---------------------- 分页 ----------------------
	/**
	 * 每页条数
	 * 
	 * @var int
	 */
	protected $perPage = 15;

	/**
	 * 设置 每页条数
	 * 
	 * @param int $perPage
	 * @return $this
	 */
	public function setPerPage(int $perPage)
	{
		$this->perPage = $perPage;

		return $this;
	}

	/**
	 * 获取 每页条数
	 * 
	 * @return int
	 */
	public function getPerPage(): int
	{
		return $this->perPage;
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
			return $this->baseQueryBuilder()->query(...$parameters);
		}

		return $this->forwardCallTo($this->newQueryBuilder(), $method, $parameters);
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
