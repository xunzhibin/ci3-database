<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

/**
 * 强制转换 属性
 */
trait Cast
{
	/**
	 * 强制转换 属性
	 * 
	 * @var array
	 */
	protected $casts = [
		// 属性key => 类型
	];

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
		return $this->getCastAttributes()[$key];
	}

}
