<?php

// 命名空间
namespace Xzb\Ci3\Database\Query\Traits;

// 异常类
use Xzb\Ci3\Database\Query\{
	RecordsNotFoundException,
	MultipleRecordsFoundException,
	SelectFailedException
};

/**
 * 读取
 */
trait Read
{
	/**
	 * 获取
	 * 
	 * @param array $columns
	 * @return \CI_DB_result
	 * 
	 * @throws \Xzb\Ci3\Database\Query\SelectFailedException
	 */
	public function get(array $columns = ['*'])
	{
		$builder = $this->select($columns)->performReadCiQb();

		// 执行 查询
		$result = $builder->get();
		if ($result === false) {
			throw new SelectFailedException($this->error($builder));
		}

		// 设置 操作 SQL
		static::setQueries($builder);

		return $result;
	}

	/**
	 * 记录 总数
	 * 
	 * @return int
	 * 
	 * @throws \Xzb\Ci3\Database\Query\SelectFailedException
	 */
	public function count(): int
	{
		$builder = $this->performReadCiQb();

		$result = $builder->count_all_results();
		if ($result === false) {
			throw new SelectFailedException($this->error($builder));
		}

		// 设置 操作 SQL
		static::setQueries($builder);

		return $result;
	}

	/**
	 * 唯一 记录
	 * 
	 * @param array $columns
	 * @return \CI_DB_result
	 * 
	 * @throws \Xzb\Ci3\Database\Query\RecordsNotFoundException
	 * @throws \Xzb\Ci3\Database\Query\MultipleRecordsFoundException
	 */
	public function sole(array $columns = ['*'])
	{
		$result = $this->take(2)->get($columns);

		$count = $result->num_rows();

		if ($count === 0) {
			throw new RecordsNotFoundException;
		}

		if ($count > 1) {
			throw new MultipleRecordsFoundException($count . ' records were found');
		}

		return $result;
	}

	/**
	 * 最大值
	 * 
	 * @param string $column
	 * @param string $alias
	 * @return mixed
	 */
	public function max(string $column, string $alias = '')
	{
		$alias = $alias ?: 'max_' . $column;

		return $this->select_max($column, $alias)
						->get([])->row()->$alias;
	}

	/**
	 * 存在
	 * 
	 * @return bool
	 */
	public function exists()
	{
		return (bool)$this->count();
	}

	/**
	 * 不存在
	 * 
	 * @return bool
	 */
	public function doesntExist()
	{
		return ! $this->exists();
	}

}
