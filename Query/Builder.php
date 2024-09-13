<?php

// 命名空间
namespace Xzb\Ci3\Database\Query;

/**
 * CI 查询构造器 扩展
 */
class Builder
{
	use Traits\CiQueryBuilder;
	use Traits\Write;
	use Traits\Read;

	/**
	 * 读取 连接配置组
	 * 
	 * @var string
	 */
	protected $read;

	/**
	 * 写入 连接配置组
	 * 
	 * @var string
	 */
	protected $write;

	/**
	 * 查询构造器 实例 缓存
	 * 
	 * @var array
	 */
	protected static $builderCache;

	/**
	 * 构造函数
	 * 
	 * @return void
	 */
	public function __construct(string $read = '', string $write = '')
	{
		$this->read = $read;
		$this->write = $write;
	}

	/**
	 * 新建 CI框架 查询构造器
	 * 
	 * @return \CI_DB_query_builder
	 */
	protected function newCiQueryBuilder(string $group = '', bool $isReturn = false)
	{
		$key = $group ?: 'default';

		if (isset(static::$builderCache[$key])){
			return static::$builderCache[$key];
		}

		return static::$builderCache[$key] = load_class('Loader', 'core')
												->database($group, $isReturn);
	}

	/**
	 * 新建 读取 CI框架 查询构造器
	 * 
	 * @return \CI_DB_query_builder
	 */
	protected function newReadCiQueryBuilder()
	{
		return $this->newCiQueryBuilder($this->read, true);
	}

	/**
	 * 新建 写入 CI框架 查询构造器
	 * 
	 * @return \CI_DB_query_builder
	 */
	protected function newWriteCiQueryBuilder()
	{
		return $this->newCiQueryBuilder($this->write, true);
	}

	/**
	 * 执行 SQL
	 * 
	 * @param string $sql
	 * @param array $binds
	 * @return \CI_DB_query_builder|\CI_DB_result
	 */
	public function query(string $sql, $binds = [])
	{
		// 操作
		$operation = trim(strtolower(mb_substr(trim($sql), 0, 6)));

		// 查询构造器
		$builder = $operation === 'select'
					? $this->newReadCiQueryBuilder()
					: $this->newWriteCiQueryBuilder();

		// 执行
		$result = $builder->query($sql, $binds);
		if ($result === false) {
			$this->throwOperationException($operation, $builder);
		}

		// 设置 操作 SQL
		static::setQueries($builder);

		return $operation === 'select' ? $result : $builder;
	}

	/**
	 * 错误
	 * 
	 * @param \CI_DB_query_builder
	 * @return string
	 */
	public function error(\CI_DB_query_builder $query)
	{
		$error = $query->error();

		$message = 'SQL Error';
		if ($error['code'] ?? false) {
			$message .= '(' . $error['code'] . ')';
		}
		if ($error['message'] ?? false) {
			$message .= ': ' . $error['message'];
		}

		$message .= ' - Invalid query: ' . $query->last_query();

		return $message;
	}

	/**
	 * 设置 操作 SQL
	 * 
	 * @param \CI_DB_query_builder
	 * @return void
	 */
	public static function setQueries(\CI_DB_query_builder $query)
	{
		$key = 'queries';

		$config =& load_class('Config', 'core');
		$queries = $config->item($key) ?: [];

		array_push($queries, [
			'database'	=> $query->hostname . ':' . $query->database,
			'sql'		=> $query->last_query(),
			'time'		=> end($query->query_times)
		]);

		$config->set_item($key, $queries);
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
		$this->setCiQb($method, $parameters);

		return $this;
	}

}
