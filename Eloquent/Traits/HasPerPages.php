<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

/**
 * 每页条数
 */
trait HasPerPages
{
	/**
	 * 每页条数
	 * 
	 * @var int
	 */
	protected $perPage = 15;

	/**
	 * 设置 每页条数
	 * 
	 * @param int $perPage
	 * @return $this
	 */
	public function setPerPage(int $perPage)
	{
		$this->perPage = $perPage;

		return $this;
	}

	/**
	 * 获取 每页条数
	 * 
	 * @return int
	 */
	public function getPerPage(): int
	{
		return $this->perPage;
	}

}
