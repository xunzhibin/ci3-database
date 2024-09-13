<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

/**
 * 可见 属性
 */
trait Visibles
{
	/**
	 * 序列化 可见属性
	 * 
	 * @var array
	 */
	protected $visible = [];

	/**
	 * 设置 可见属性
	 * 
	 * @param array $visible
	 * @return $this
	 */
	public function setVisible(array $visible)
	{
		$this->visible = $visible;

		return $this;
	}

	/**
	 * 获取 可见属性
	 * 
	 * @return array
	 */
	public function getVisible(): array
	{
		return $this->visible;
	}

	/**
	 * 设置 属性可见
	 * 
	 * @param array|string $attributes
	 * @return $this
	 */
	public function makeVisible($attributes)
	{
		$attributes = is_array($attributes) ? $attributes : func_get_args();

		// 隐藏属性 中 去除
		$this->hidden = array_diff($this->hidden, $attributes);

		// 合并到 可见属性 中 
		if (! empty($this->visible)) {
			$this->visible = array_merge($this->visible, $attributes);
		}

		return $this;
	}

}
