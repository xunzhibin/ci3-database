<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 转换 辅助函数
use Xzb\Ci3\Helpers\Transform;

// 模型 缺少属性 异常类
use Xzb\Ci3\Database\Eloquent\ModelMissingAttributeException;

/**
 * 属性
 */
trait HasAttributes
{
	use Attributes\Mutator;
	use Attributes\DateTimestamps;
	use Attributes\Edit;
	use Attributes\Original;
	use Attributes\Relations;
	use Attributes\Accessor;
	use Attributes\Visibles;
	use Attributes\Hides;
	use Attributes\Cast;

	/**
	 * 模型 属性
	 * 
	 * @var array
	 */
	protected $attributes = [
		// 属性key => 值
	];

	/**
	 * 模型 属性数据类型
	 * 
	 * @var array
	 */
	protected $attributeTypes = [
		// 属性key => 数据类型
	];

// ---------------------- 属性 ----------------------
	/**
	 * 设置 属性
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public function setAttribute(string $key, $value)
	{
		// 是否有 属性修改器
		if ($this->hasSetMutator($key)) {
			return $this->setMutatedAttributeValue($key, $value);
		}
		// 是否有 属性数据类型
		else if ($this->hasAttributeType($key)) {
			$value = Transform::valueType($this->getAttributeType($key), $value);
		}
		// 是否为 日期属性
		else if ($this->isDateAttribute($key)) {
			$value = $this->transformToStorageDateFormat($value);
		}
		// 是否为 json中某个属性
		if (mb_strpos($key, '->') !== false) {
			return $this->fillJsonAttribute($key, $value);
		}

		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * 设置 模型 属性数组
	 * 
	 * @param array $attributes
	 * @param bool $sync
	 * @return $this
	 */
	public function setRawAttributes(array $attributes, $sync = false)
	{
		$this->attributes = $attributes;

		// 是否 同步原始属性
		if ($sync) {
			$this->syncOriginal();
		}

		return $this;
	}

	/**
	 * 获取 指定属性
	 * 
	 * @param string $key
	 * @return mixed
	 * 
	 * @throws \Xzb\Ci3\Database\Eloquent\ModelMissingAttributeException
	 */
	public function getAttribute(string $key)
	{
		if (! $key) {
			return;
		}

		$value = $this->getAttributes()[$key] ?? null;

		// 存在 属性访问器
		if ($this->hasGetAccessor($key)) {
			return $this->getAccessorAttributeValue($key, $value);
		}

		// 属性数组中存在
		if (array_key_exists($key, $this->attributes)) {
			// 是否为 强制转换属性
			if ($this->isCastAttribute($key)) {
				return Transform::valueType($this->getCastAttributeType($key), $value);
			}

			return $value;
		}

		// 关系属性
		if ($this->isRelation($key)) {
			return $this->getRelationValue($key);
		}

		// 抛出异常
		if ($this->exists && ! $this->wasRecentlyCreated && is_null($value)) {
			$message = 'The attribute [' . $key . '] either does not exist or was not retrieved for model [' . static::class . ']';
			throw (new ModelMissingAttributeException($message));
		}

		return $value;
	}

	/**
	 * 获取 所有属性
	 * 
	 * @return array
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * 获取 插入操作 属性
	 * 
	 * @return array
	 */
	public function getInsertAttributes(): array
	{
		// 自动维护 操作时间
		if ($this->usesTimestamps()) {
			$this->updateTimestamps();
		}

		return $this->getAttributes();
	}

	/**
	 * 获取 更新操作 属性
	 * 
	 * @return array
	 */
	public function getUpdateAttributes(): array
	{
		// 自动维护 操作时间
		if ($this->usesTimestamps()) {
			$this->updateTimestamps();
		}

		return $this->getEditedAttributes();
	}

	/**
	 * 获取 可序列化 属性
	 * 
	 * @return array
	 */
	protected function getArrayableAttributes()
	{
		return $this->getArrayableItems($this->getAttributes());
	}

	/**
	 * 获取 可序列化 项
	 * 
	 * @param array
	 * @return array
	 */
	protected function getArrayableItems(array $values)
	{
		if (count($this->getVisible()) > 0) {
			$values = array_intersect_key($values, array_flip($this->getVisible()));
		}

		if (count($this->getHidden()) > 0) {
			$values = array_diff_key($values, array_flip($this->getHidden()));
		}

		return $values;
	}

// ---------------------- 属性类型 ----------------------
	/**
	 * 获取 属性数据类型
	 * 
	 * @return array
	 */
	public function getAttributeTypes(): array
	{
		return array_merge(
			[ $this->getPrimaryKeyName() => $this->getPrimaryKeyType() ],
			$this->attributeTypes
		);
	}

	/**
	 * 是否有 属性数据类型
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function hasAttributeType(string $key): bool
	{
		return array_key_exists($key, $this->getAttributeTypes());
	}

	/**
	 * 获取 指定属性 数据类型
	 * 
	 * @param string $key
	 * @return string
	 */
	public function getAttributeType(string $key): string
	{
		return $this->getAttributeTypes()[$key];
	}

// ---------------------- JSON类型 ----------------------
	/**
	 * 填充 JSON 属性
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function fillJsonAttribute(string $key, $value)
	{
		[$key, $path] = explode('->', $key, 2);

		$array = Transform::toArray($this->attributes[$key] ?? []);

		$jsonKeys = explode('->', $path);
		foreach ($jsonKeys as $i => $k) {
			if (count($jsonKeys) === 1) {
				break;
			}
			unset($jsonKeys[$i]);

			if (! isset($array[$k]) || ! is_array($array[$k])) {
				$array[$k] = [];
			}

			$array = &$array[$k];
		}

		$array[array_shift($jsonKeys)] = $value;

		$this->attributes[$key] = Transform::toJson($array);

		return $this;
	}

// ---------------------- 转换 ----------------------
	/**
	 * 转换为 数组
	 * 
	 * @return array
	 */
	public function attributesToArray(): array
	{
		// 添加 访问器 属性值
		$attributes = $this->addAccessorAttributeValues(
			$attributes = $this->getArrayableAttributes()
		);

		// 添加 强制转换 属性值
		$attributes = $this->addCastAttributeValues($attributes);

		return $attributes;
	}


	/**
	 * 添加 访问器属性值
	 * 
	 * @param array $attributes
	 * @return array
	 */
	protected function addAccessorAttributeValues(array $attributes): array
	{
		foreach ($this->getAccessorAttributes() as $key) {
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
	 * 添加 强制转换 属性值
	 * 
	 * @param array $attributes
	 * @return array
	 */
	protected function addCastAttributeValues(array $attributes): array
	{
		// 访问器 属性
		$accessorAttributes = $this->getAccessorAttributes();

		foreach ($this->getCastAttributes() as $key => $type) {
			// 不存在 或者 有属性访问器
			if ( ! array_key_exists($key, $attributes) || in_array($key, $accessorAttributes)) {
				continue;
			}

			// 转换 值类型
			$attributes[$key] = Transform::valueType($type, $attributes[$key]);
			
		}

		return $attributes;
	}

}
