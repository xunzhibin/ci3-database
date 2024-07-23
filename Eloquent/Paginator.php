<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

// PHP 可数 预定义接口
use Countable;
// JSON 序列化接口
use JsonSerializable;

/**
 * 分页器
 */
class Paginator implements Countable, JsonSerializable
{
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
		$page = get_instance()->input->get($pageName);

		if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
			return (int)$page;
		}

		return $default;
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

}
