<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

/**
 * 编辑 属性
 */
trait Edit
{
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
	 * 是否有 已被编辑的 属性
	 * 
	 * @param array|string|null $attributes
	 * @return bool
	 */
	public function hasEditedAttributes($attributes = null): bool
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

		return is_numeric($newValue) && is_numeric($oldValue)
				&& strcmp((string)$newValue, (string)$oldValue) === 0;
	}

}
