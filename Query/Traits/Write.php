<?php

// 命名空间
namespace Xzb\Ci3\Database\Query\Traits;

// 异常类
use Xzb\Ci3\Database\Query\{
	MissingInsertDataException,
	MissingUpdateDataException,
	InsertFailedException,
	UpdateFailedException,
	DeleteFailedException
};

/**
 * 写入
 */
trait Write
{
	/**
	 * 执行 插入
	 * 
	 * @param arary $values
	 * @param bool $isGetLastInsertId
	 * @return int
	 * 
	 * @throws \Xzb\Ci3\Database\Query\MissingInsertDataException
	 * @throws \Xzb\Ci3\Database\Query\InsertFailedException
	 */
	public function insert(array $values, bool $isGetLastInsertId = false): int
	{
		if (empty($values)) {
			throw new MissingInsertDataException();
		}

		// 检测 是否为 二维数组
		if (! is_array(reset($values))) {
			// 转为 二维数组
			$values = [$values];
		}
		else {
			// 循环 排序key
			foreach ($values as $key => $value) {
				ksort($value);

				$values[$key] = $value;
			}
		}

		$builder = $this->set_insert_batch($values)->performWriteCiQb();

		// 执行 批量插入
		if ($builder->insert_batch('') === false) {
			throw new InsertFailedException($this->error($builder));
		}

		// 设置 操作 SQL
		static::setQueries($builder);

		// 获取 最后插入ID
		if ($isGetLastInsertId) {
			$id = $builder->insert_id();

			return is_numeric($id) ? (int)$id : $id;
		}

		return $builder->affected_rows();
	}

	/**
	 * 更新
	 * 
	 * @param array $values
	 * @param bool $isEscape
	 * @return int
	 * 
	 * @throws \Xzb\Ci3\Database\Query\MissingUpdateDataException
	 * @throws \Xzb\Ci3\Database\Query\UpdateFailedException
	 */
	public function update(array $values, bool $isEscape = true): int
	{
		if (empty($values)) {
			throw new MissingUpdateDataException();
		}
	
		$builder = $this->set($values, '', $isEscape)->performWriteCiQb();

		// 执行更新
		if ($builder->update() === false) {
			throw new UpdateFailedException($this->error($builder));
		}

		// 设置 操作 SQL
		static::setQueries($builder);

		return $builder->affected_rows();
	}

	/**
	 * 删除
	 * 
	 * @return int
	 * 
	 * @throws \Xzb\Ci3\Database\Query\DeleteFailedException
	 */
	public function delete(): int
	{
		$builder = $this->performWriteCiQb();

		// 执行删除
		if ($builder->delete() === false) {
			throw new DeleteFailedException($this->error($builder));
		}

		// 设置 操作 SQL
		static::setQueries($builder);

		return $builder->affected_rows();
	}


	/**
	 * 事务 中执行
	 * 
	 * @param \Closure $callback
	 * @return mixed
	 */
	public function transaction(\Closure $callback)
	{
		$builder = $this->newWriteCiQueryBuilder();

		// 事务 开启
		$builder->trans_begin();

		try {
			// $callbackResult = $callback($this);
			$callbackResult = $callback(new static($this->read, $this->write));
		}
		catch(\Throwable $e) {
			// 事务 回滚
			$builder->trans_rollback();
			throw $e;
		}

		// 事务 提交
		$builder->trans_commit();

		return $callbackResult;
	}

}
