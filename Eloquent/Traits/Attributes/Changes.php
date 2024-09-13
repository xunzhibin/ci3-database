<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

/**
 * 更改 属性
 */
trait Changes
{
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
	public function getChangeAttributes(): array
	{
		return $this->changes;
	}

}
