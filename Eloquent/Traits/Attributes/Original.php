<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

/**
 * 属性 原始状态
 */
trait Original
{
	/**
	 * 属性 原始状态
	 * 
	 * @var array
	 */
	protected $original = [];

	/**
	 * 同步 属性 原始状态
	 * 
	 * @param array|string $attributes
	 * @return $this
	 */
	public function syncOriginal($attributes = [])
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

	/**
	 * 获取 属性 原始状态 原始值
	 * 
	 * @param array
	 * @return mixed|array
	 */
	public function getRawOriginal($key = null, $default = null)
	{
		if (is_null($key)) {
			return $this->original;
		}

		$this->original[$key] ?? $default;
	}

}
