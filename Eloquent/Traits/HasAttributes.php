<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

// 模型 缺少属性 异常类
use Xzb\Ci3\Database\Excepton\ModelMissingAttributeException;

/**
 * 属性
 */
trait HasAttributes
{
// ---------------------- 属性 ----------------------
	/**
	 * 模型 属性
	 * 
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * 设置 给定属性
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function setAttribute(string $key, $value)
	{
		// 有 属性修改器
		if ($this->hasSetMutator($key)) {
			return $this->setMutatedAttributeValue($key, $value);
		}
		// 日期属性
		else if ($this->isDateAttribute($key) && ! is_null($value)) {
			$value = $this->transformToStorageDateFormat($value);
		}
		// JSON属性
		else if ($this->isJsonAttribute($key) && ! is_null($value)) {
			$value = $this->transformAttributeValueToJson($key, $value);
		}

		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * 获取 指定属性
	 * 
	 * @param string $key
	 * @return mixed
	 * 
	 * @throws \Xzb\Ci3\Database\Excepton\ModelMissingAttributeException
	 */
	public function getAttribute(string $key)
	{
		if (! $key) {
			return;
		}

		// 属性数组中存在 或者 存在 属性访问器
		if (array_key_exists($key, $this->attributes) || $this->hasGetAccessor($key)) {
			return $this->transformModelAttributeValue($key, $this->getAttributes()[$key] ?? null);
		}

		throw (new ModelMissingAttributeException('The attribute [' . $key . '] either does not exist or was not retrieved for model [' . static::class . '].'))->setModel(static::class);
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

	/**
	 * 设置 模型 属性
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
			$this->syncOriginalAttributes();
		}

		return $this;
	}

// ---------------------- 原始属性 ----------------------
	/**
	 * 模型 原始属性
	 * 
	 * @var array
	 */
	protected $original = [];

	/**
	 * 同步 原始属性
	 * 
	 * @param array|string $attributes
	 * @return $this
	 */
	public function syncOriginalAttributes($attributes = [])
	{
		$attributes = is_array($attributes) ? $attributes : func_get_args();
		if ($attributes) {
			$modelAttributes = $this->getAttributes();

			// 循环设置
			foreach ($attributes as $attribute) {
				$this->original[$attribute] = $modelAttributes[$attribute];
			}

			return $this;
		}

		$this->original = $this->getAttributes();

		return $this;
	}

// ---------------------- 编辑、更改 属性 ----------------------
	/**
	 * 更改属性
	 * 
	 * 最后一次 保存模型时 更改的属性
	 * 
	 * @var array
	 */
	protected $changes = [];

	/**
	 * 同步 更改属性
	 * 
	 * @return $this
	 */
	public function syncChangeAttributes()
	{
		$this->changes = $this->getEditedAttributes();

		return $this;
	}

	/**
	 * 获取 更改属性
	 * 
	 * @return array
	 */
	public function getChangeAttributes()
	{
		return $this->changes;
	}

	/**
	 * 获取 被编辑的 所有属性
	 * 
	 * @return array
	 */
	public function getEditedAttributes(): array
	{
		$edited = [];

		foreach ($this->getAttributes() as $key => $value) {

			// 新值 和 原始值 是否相等
			if (! $this->originalIsEquivalent($key)) {
				$edited[$key] = $value;
			}
		}

		return $edited;
	}

	/**
	 * 属性 是否被 编辑
	 * 
	 * @param array|string|null $attributes
	 * @return bool
	 */
	public function isEdited($attributes = null): bool
	{
		// 已被编辑 所有属性
		$editedAttributes = $this->getEditedAttributes();

		// 检测 指定属性
		if ($attributes = is_array($attributes) ? $attributes : func_get_args()) {
			// 循环检测属性 任意一个被编辑 返回true
			foreach ($attributes as $attribute) {
				if (array_key_exists($attribute, $editedAttributes)) {
					return true;
				}
			}

			return false;
		}

		return count($editedAttributes) > 0;
	}

	/**
	 * 对比属性 新值和旧值 是否相等
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function originalIsEquivalent(string $key): bool
	{
		// 原始属性 不存在
		if (! array_key_exists($key, $this->original)) {
			return false;
		}

		$newValue = $this->attributes[$key] ?? null;
		$oldValue = $this->original[$key] ?? null;

		if ($newValue === $oldValue) {
			return true;
		}
		else if (is_null($newValue)) {
			return false;
		}
		// 日期属性
		else if ($this->isDateAttribute($key)) {
			// 转为 存储格式 进行对比
			return $this->transformToStorageDateFormat($newValue)
					=== $this->transformToStorageDateFormat($oldValue);
			// 转为 Unix时间戳 进行对比
			// return $this->transformToDateCustomFormat($newValue, $format = 'U')
			// 		=== $this->transformToDateCustomFormat($oldValue, $format = 'U');
		}
		// JSON属性
		else if ($this->isJsonAttribute($key)) {
			// 转为 数组 进行对比
			return $this->transformToArray($newValue) === $this->transformToArray($oldValue);
		}

		return is_numeric($newValue) && is_numeric($oldValue)
				&& strcmp((string)$newValue, (string)$oldValue) === 0;
	}

// ---------------------- 日期属性 ----------------------
	/**
	 * 模型 日期属性
	 * 
	 * @var array
	 */
	protected $dates = [];

	/**
	 * 模型 日期属性 存储格式
	 * 
	 * @var string
	 */
	protected $dateFormat = 'Y-m-d H:i:s';

	/**
	 * 获取 日期属性
	 * 
	 * @return array
	 */
	public function getDateAttributes(): array
	{
		// 自动维护 操作时间
		if ($this->usesTimestamps()) {
			return array_unique(array_merge($this->dates, [
				$this->getCreatedAtColumn(),
				$this->getUpdatedAtColumn(),
			]));
		}

		return $this->dates;
	}

	/**
	 * 是否为 日期属性
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function isDateAttribute(string $key): bool
	{
		return in_array($key, $this->getDateAttributes(), true);
	}

	/**
	 * 设置 日期属性 存储格式
	 * 
	 * @param string $format
	 * @return $this
	 */
	public function setDateFormat(string $format)
	{
		$this->dateFormat = $format;

		return $this;
	}

	/**
	 * 获取 日期属性 存储格式
	 * 
	 * @return string
	 */
	public function getDateFormat(): string
	{
		return $this->dateFormat;
	}

// ---------------------- JSON属性 ----------------------
	/**
	 * 模型 JSON属性
	 * 
	 * @var array
	 */
	protected $jsons = [];

	/**
	 * 获取 JSON属性
	 * 
	 * @return array
	 */
	public function getJsonAttributes(): array
	{
		return $this->jsons;
	}

	/**
	 * 是否为 JSON属性
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function isJsonAttribute(string $key): bool
	{
		return in_array($key, $this->getJsonAttributes(), true);
	}

// ---------------------- 强制转换属性 ----------------------
	/**
	 * 强制转换 属性
	 * 
	 * @var array
	 */
	protected $casts = [];

	/**
	 * 强制转换属性 数据类型 缓存
	 * 
	 * @var array
	 */
	protected static $castTypeCache = [];

	/**
	 * 获取 强制转换 属性
	 * 
	 * @return array
	 */
	public function getCastAttributes(): array
	{
		// 模型主键 自增
		if ($this->getIncrementing()) {
			return array_merge([$this->getPrimaryKeyName() => $this->getPrimaryKeyType()], $this->casts);
		}

		return $this->casts;
	}

	/**
	 * 是否为 强制转换属性
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function isCastAttribute(string $key): bool
	{
		return array_key_exists($key, $this->getCastAttributes());
	}

	/**
	 * 获取 强制转换属性 转换类型
	 * 
	 * @param string $key
	 * @param string
	 */
	protected function getCastAttributeType(string $key): string
	{
		// 强制转换 类型
		$castType = $this->getCastAttributes()[$key];

		// 缓存中 存在
		if (isset(static::$castTypeCache[$castType])) {
			return static::$castTypeCache[$castType];
		}

		// 是否为 自定义日期时间 类型
		if ($this->isCustomDateTimeCastType($castType)) {
			$convertedCastType = 'custom_datetime';
		}
		// 是否为 小数 类型
		elseif ($this->isDecimalCastType($castType)) {
			$convertedCastType = 'decimal';
		}
		else {
			$convertedCastType = trim(strtolower($castType));
		}

		return static::$castTypeCache[$castType] = $convertedCastType;
	}

	/**
	 * 是否为 自定义日期时间 强制转换 类型
	 * 
	 * @param string $castType
	 * @return bool
	 */
	protected function isCustomDateTimeCastType(string $castType): bool
	{
		return strncmp($castType, $str = 'datetime:', strlen($str)) === 0;
	}

	/**
	 * 是否为 小数 强制转换 类型
	 * 
	 * @param string $castType
	 * @return bool
	 */
	protected function isDecimalCastType(string $castType): bool
	{
		return strncmp($castType, $str = 'decimal:', strlen($str)) === 0;
	}

// ---------------------- 属性 修改器、访问器 ----------------------
	/**
	 * 访问器属性 缓存
	 * 
	 * @var array
	 */
	protected static $accessorAttributeCache = [];

	/**
	 * 是否存在 属性 修改器
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function hasSetMutator(string $key): bool
	{
		return method_exists($this, 'set' . Str::upperCamel($key) . 'Attribute');
	}

	/**
	 * 使用 属性修改器 设置属性值
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	protected function setMutatedAttributeValue(string $key, $value)
	{
		return $this->{'set' . Str::upperCamel($key) . 'Attribute'}($value);
	}

	/**
	 * 是否存在 属性 访问器
	 * 
	 * @param string $Key
	 * @return bool
	 */
	public function hasGetAccessor(string $key): bool
	{
		return method_exists($this, 'get' . Str::upperCamel($key) . 'Attribute');
	}

	/**
	 * 获取 属性访问器 属性值
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	protected function getAccessorAttributeValue(string $key, $value)
	{
		return $this->{'get' . Str::upperCamel($key) . 'Attribute'}($value);
	}

	/**
	 * 获取 访问器 属性
	 * 
	 * @return array
	 */
	public function getAccessorAttributes(): array
	{
		if (! isset(static::$accessorAttributeCache[static::class])) {
			static::cacheAccessorAttributes($this);
		}

		return static::$accessorAttributeCache[static::class];
	}

	/**
	 * 缓存 访问器 属性
	 * 
	 * @param object|string $class
	 * @return void
	 */
	public static function cacheAccessorAttributes($class)
	{
		// 获取类名
		$className = (new \ReflectionClass($class))->getName();

		// 获取 类 所有方法
		$methods = implode(';', get_class_methods($className));

		// 匹配 访问器 方法
		preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', $methods, $matches);

		// 缓存
		static::$accessorAttributeCache[$className] = array_map(function ($value) {
			return Str::snake($value);
		}, $matches[1]);
	}

}
