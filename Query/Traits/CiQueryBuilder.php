<?php

// 命名空间
namespace Xzb\Ci3\Database\Query\Traits;

/**
 * CI查询构造器 类方法
 */
trait CiQueryBuilder
{
	/**
	 * CI查询构造器 类方法
	 * 
	 * @var array
	 */
	public $ciQb = [];

	/**
	 * 设置 CI查询构造器 类方法
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return $this
	 */
	public function setCiQb(string $method, array $parameters)
	{
		$this->ciQb[] = compact('method', 'parameters');

		return $this;
	}

	/**
	 * 执行 CI查询构造器 类方法
	 * 
	 * @param \CI_DB_query_builder
	 * @return \CI_DB_query_builder
	 */
	public function performCiQb(\CI_DB_query_builder $builder)
	{
		// 循环执行
		foreach ($this->ciQb as $value) {
			$builder->{$value['method']}(...$value['parameters']);
		}

		return $builder;
	}

	// 执行 写入 CI查询构造器 类方法
	public function performWriteCiQb()
	{
		return $this->performCiQb(
			$this->newWriteCiQueryBuilder()
		);
	}

	// 执行 读取 CI查询构造器 类方法
	public function performReadCiQb()
	{
		return $this->performCiQb(
			$this->newReadCiQueryBuilder()
		);
	}

	/**
	 * 是否有 指定 CI查询构造器 类方法
	 * 
	 * @param string $method
	 * @return bool
	 */
	public function hasCiQb(string $method): bool
	{
		return in_array($method, array_column($this->ciQb, 'method'));

		return false;
	}

// ---------------------- 扩展/别名 ----------------------
	/**
	 * 数据表
	 * 
	 * FROM
	 * 
	 * @param string $from
	 * @return $this
	 */
	public function from(string $from)
	{
		$this->setCiQb('from', array_values(compact('from')));

		return $this;
	}

	/**
	 * 条件
	 * 
	 * WHERE AND
	 * 
	 * @param string $column
	 * @param mixed $value
	 * @return $this
	 */
	public function where($column, $value = null)
	{
		if (! is_array($column)) {
			$column = [ $column => $value ];
		}

		foreach ($column as $k => $v) {
			$this->setCiQb(
				is_array($v) ? 'where_in' :  'where',
				array_values(compact('k', 'v'))
			);
		}

		return $this;
	}

	/**
	 * limit 别名
	 * 
	 * LIMIT
	 * 
	 * @param int $value
	 * @return $this
	 */
	public function take($limit)
	{
		$this->limit($limit);

		return $this;
	}

	/**
	 * 设置 偏移量分页 查询条数、偏移量
	 * 
	 * @param int $page
	 * @param int $perPage
	 * @return $this
	 */
	public function forPage(int $page, int $perPage = 15)
	{
		$offset = ($page - 1) * $perPage;
		$limit = $perPage;

		$this->limit($limit)->offset($offset);

		return $this;
	}

	/**
	 * 模糊匹配
	 * 
	 * AND (LIKE OR LIKE)
	 * 
	 * @param array|string $columns
	 * @param string $keyword
	 * @param string $side
	 * @return $this
	 */
	public function like($columns, string $keyword = null, string $side = 'both')
	{
		if (strlen($keyword)) {
			if (! is_array($columns)) {
				$columns = [ $columns ];
			}

			// 条件组 开始
			count($columns) > 1 && $this->group_start();

			// 是否为 第一个
			$isFirst = true;
			foreach ($columns as $column) {
				// 第一个
				if ($isFirst) {
					$this->setCiQb('like', array_values(compact('column', 'keyword', 'side')));
					$isFirst = false;
					continue;
				}

				// 其它 OR
				$this->setCiQb('or_like', array_values(compact('column', 'keyword', 'side')));
			}

			// 条件组 结束
			count($columns) > 1 && $this->group_end();
		}

		return $this;
	}

	/**
	 * 排序
	 * 
	 * @param array|string $column
	 * @param string $direction
	 * @return $this
	 */
	public function orderBy($column, $direction = 'ASC')
	{
		if (! is_array($column)) {
			if (preg_match('/\s+(ASC|DESC)$/i', rtrim($column), $match, PREG_OFFSET_CAPTURE)) {
				// id asc
				$column = [ $column => '' ];
			}
			else {
				// id
				$column = [ $column => $direction ];
			}
		}

		// [ id => asc, created_at]
		foreach ($column as $key => $value) {
			if (is_numeric($key)) {
				$key = $value;
				$value = $direction;
			}

			$this->order_by($key, strtoupper($value));
		}

		return $this;
	}

}
