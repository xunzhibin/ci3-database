<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

// 调用转发 trait
use Xzb\Ci3\Helpers\Traits\ForwardsCalls;
// PHP 可数 预定义接口
use Countable;
// JSON 序列化接口
use JsonSerializable;

/**
 * 分页器
 */
class Paginator implements Countable, JsonSerializable
{
	use ForwardsCalls;

	/**
	 * 分页 项
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Collection 
	 */
	protected $items;

	/**
	 * 项 总数
	 * 
	 * @var int
	 */
	protected $total;

	/**
	 * 每页显示条数
	 * 
	 * @var int
	 */
	protected $perPage;

	/**
	 * 当前页
	 * 
	 * @var int
	 */
	protected $currentPage;

	/**
	 * 最后页
	 * 
	 * @var int
	 */
	protected $lastPage;

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Collection $item
	 * @param int $total
	 * @param int $perPage
	 * @param int $currentPage
	 * @return void
	 */
	public function __construct(Collection $items, int $total, int $perPage, int $currentPage)
	{
		$this->items		= $items;
		$this->total		= $total;
		$this->perPage		= $perPage;
		$this->currentPage	= $currentPage;
		$this->lastPage		= max((int) ceil($total / $perPage), 1);

		// $this->items = $items instanceof Collection ? $items : Collection::make($items);
	}

	/**
	 * 获取 项总数
	 * 
	 * @return int
	 */
	public function total(): int
	{
		return $this->total;
	}

	/**
	 * 获取 每页显示条数
	 * 
	 * @return int
	 */
	public function perPage(): int
	{
		return $this->perPage;
	}

	/**
	 * 获取 当前页
	 * 
	 * @return int
	 */
	public function currentPage(): int
	{
		return $this->currentPage;
	}

	/**
	 * 获取 最后页
	 * 
	 * @return int
	 */
	public function lastPage(): int
	{
		return $this->lastPage;
	}

	/**
	 * 解析 当前页
	 * 
	 * @param string $pageName
	 * @param int $default
	 * @return int
	 */
	public static function resolveCurrentPage($pageName = 'page', $default = 1): int
	{
		// $page = get_instance()->input->get($pageName);
		$page = $_GET[$pageName] ?? null;

		if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
			return (int)$page;
		}

		return $default;
	}

	/**
	 * 设置 集合
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Collection
	 * @return $this
	 */
	public function setCollection($collection)
	{
		$this->items = $collection;

		return $this;
	}

	/**
	 * 获取 集合
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function getCollection()
	{
		return $this->items;
	}

	/**
	 * 获取 集合
	 * 
	 * @return array
	 */
	public function items()
	{
		return $this->items->all();
	}

	/**
	 * 转为 数组
	 * 
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'data'			=> $this->items->toArray(),
			'total'			=> $this->total(),
			'per_page'		=> $this->perPage(),
			'current_page'	=> $this->currentPage(),
			'last_page'		=> $this->lastPage(),
		];
	}

	/**
	 * 转为 JSON
	 * 
	 * @return string
	 */
	public function toJson()
	{
		return json_encode($this->jsonSerialize());
	}

// ---------------------- PHP Countable(可数) 预定义接口 ----------------------
	/**
	 * 项 总数
	 * 
	 * @return int
	 */
	public function count(): int
	{
		return $this->items->count();
	}

// ---------------------- PHP JsonSerializable(JSON序列化) 预定义接口 ----------------------
	/**
	 * 转为 JSON可序列化的数据
	 * 
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

// ---------------------- 魔术方法 ----------------------
	/**
	 * 处理调用 不可访问 方法
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->forwardCallTo($this->getCollection(), $method, $parameters);
		// if ($method === 'query') {
		// 	return $this->newBaseQueryBuilder()->query(...$parameters);
		// }
		// return $this->forwardCallTo($this->newQuery(), $method, $parameters);
	}

    // /**
    //  * Make dynamic calls into the collection.
    //  *
    //  * @param  string  $method
    //  * @param  array  $parameters
    //  * @return mixed
    //  */
    // public function __call($method, $parameters)
    // {
    //     var_dump($method);
    //     
    // }

}
