<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

/**
 * 隐藏 属性
 */
trait Hides
{
	/**
	 * 序列化 隐藏属性
	 * 
	 * @var array
	 */
	protected $hidden = [];

	/**
	 * 设置 隐藏属性
	 * 
	 * @param array $hidden
	 * @return $this
	 */
	public function setHidden(array $hidden)
	{
		$this->hidden = $hidden;

		return $this;
	}

	/**
	 * 获取 隐藏属性
	 * 
	 * @return array
	 */
	public function getHidden(): array
	{
		return $this->hidden;
	}

	/**
	 * 设置 属性隐藏
	 * 
	 * @param array|string $attributes
	 * @return $this
	 */
	public function makeHidden($attributes)
	{
		$this->hidden = array_merge(
			$this->hidden,
			is_array($attributes) ? $attributes : func_get_args()
		);

		return $this;
	}

}

