<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 中间支点
 */
class Pivot extends Model
{
	use Traits\AsPivot;

	/**
	 * 模型 主键 是否自增
	 * 
	 * @var bool
	 */
	public $incrementing = false;
}
