<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

/**
 * 属性 修改器
 */
trait Mutator
{
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

}
