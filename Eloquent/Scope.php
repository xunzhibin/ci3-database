<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

/**
 * 作用域 抽象接口类
 */
interface Scope
{
	/**
	 * 应用 作用域
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @param  \Xzb\Ci3\Database\Eloquent\Model $model
	 * @return void
	 */
	public function apply(Builder $builder, Model $model);

}
