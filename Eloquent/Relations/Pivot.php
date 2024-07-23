<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 数据透视 模型
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