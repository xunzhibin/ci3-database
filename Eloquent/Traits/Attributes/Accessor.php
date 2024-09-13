<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

/**
 * 属性 访问器
 */
trait Accessor
{
	/**
	 * 访问器属性 缓存
	 * 
	 * @var array
	 */
	protected static $accessorAttributeCache = [];

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
