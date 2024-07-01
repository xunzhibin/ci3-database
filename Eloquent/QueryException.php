<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

use RuntimeException;
use Throwable;

/**
 * 查询 异常
 */
class QueryException extends RuntimeException
{
	/**
	 * 构造函数
	 * 
	 * @return void
	 */
	public function __construct(string $message = "", int $code = 500, ?Throwable $previous = null)
	{
		// 父类
		parent::__construct($message, $code, $previous);
	}
}
