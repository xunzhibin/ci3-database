<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

// JSON 序列化接口
use JsonSerializable;
// PHP 可数 预定义接口
use Countable;

/**
 * 集合
 */
class Collection implements Countable, JsonSerializable
{
	/**
	 * 集合 项
	 * 
	 * @var array
	 */
	protected $items = [];

	/**
	 * 构造函数
	 * 
	 * @param mixed $items
	 * @return void
	 */
	public function __construct($items = [])
	{
		$this->items = $this->getArrayableItems($items);
	}

	/**
	 * 获取 项
	 * 
	 * @param mixed $items
	 * @return array
	 */
	public function getArrayableItems($items): array
	{
		if (is_array($items)) {
			return $items;
		}
		else if ($items instanceof JsonSerializable) {
			return (array)$items->jsonSerialize();
		}

		return (array)$items;
	}

	/**
	 * 获取 所有item
	 * 
	 * @return array
	 */
	public function all(): array
	{
		return $this->items;
	}

	/**
	 * 获取 第一个item
	 * 
	 * @return mixed
	 */
	public function first()
	{
		$item = reset($this->items);

		return $item ? $item : null;
	}

	/**
	 * 每个item 映射 回调函数
	 * 
	 * @param callable $callback
	 * @return static
	 */
	public function map(callable $callback)
	{
		return new static(array_map($callback, $this->items));
	}

	/**
	 * 转为 数组
	 * 
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->map(function ($value) {
			if ($value instanceof Model) {
				return $value->toArray();
			}

			return $value;
		})->all();
	}

	/**
	 * 转为 JSON
	 * 
	 * @return string
	 */
	public function toJson(): string
	{
		return json_encode($this->jsonSerialize());
	}

// ---------------------- PHP JsonSerializable(JSON序列化) 预定义接口 ----------------------
	/**
	 * 转为 JSON可序列化的数据
	 * 
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		return array_map(function ($value) {
			if ($value instanceof JsonSerializable) {
				return $value->jsonSerialize();
			}

			return $value;
		}, $this->all());
	}

// ---------------------- PHP Countable(可数) 预定义接口 ----------------------
	/**
	 * 集合项 总数
	 * 
	 * @return int
	 */
	public function count(): int
	{
		return count($this->items);
	}

// ---------------------- 魔术方法 ----------------------
	/**
	 * 转为 字符串
	 * 
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->toJson();
	}

}
