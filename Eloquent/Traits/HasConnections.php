<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 查询构造器
use Xzb\Ci3\Database\Query\Builder AS QueryBuilder;
// Eloquent 查询构造器
use Xzb\Ci3\Database\Eloquent\Builder;

/**
 * 连接 数据库
 */
trait HasConnections
{
	/**
	 * 数据库 读取 连接配置组 名称
	 * 
	 * @var string
	 */
	protected $readGroup = '';

	/**
	 * 数据库 写入 连接配置组 名称
	 * 
	 * @var string
	 */
	protected $writeGroup = '';

	/**
	 * 设置 读取 连接配置组 名称
	 * 
	 * @param string $group
	 * @return $this
	 */
	public function setReadConnectionGroup(string $group)
	{
		$this->readGroup = $group;

		return $this;
	}

	/**
	 * 获取 读取 连接配置组 名称
	 * 
	 * @return string
	 */
	public function getReadConnectionGroup(): string
	{
		return $this->readGroup;
	}

	/**
	 * 设置 写入 连接配置组 名称
	 * 
	 * @param string $group
	 * @return $this
	 */
	public function setWriteConnectionGroup(string $group)
	{
		$this->writeGroup = $group;

		return $this;
	}

	/**
	 * 获取 写入 连接配置组 名称
	 * 
	 * @return string
	 */
	public function getWriteConnectionGroup(): string
	{
		return $this->writeGroup;
	}

	/**
	 * 新建 基础 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Query\Builder
	 */
	protected function newBaseQueryBuilder()
	{
		return new QueryBuilder(
			$this->getReadConnectionGroup(),
			$this->getWriteConnectionGroup()
		);
	}

	/**
	 * 新建 Eloquent 查询构造器
	 * 
	 * @param \Xzb\Ci3\Database\Query\Builder
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function newEloquentBuilder($query)
	{
		return new Builder($query);
	}

	/**
	 * 新建 Eloquent模型 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function newModelQuery()
	{
		return $this->newEloquentBuilder(
			$this->newBaseQueryBuilder()
		)->setModel($this);
	}

	/**
	 * 新建 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function newQuery()
	{
		return $this->registerGlobalScopes($this->newModelQuery());
		// return $this->newModelQuery();
	}

	/**
	 * 注册 全局作用域
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function registerGlobalScopes(Builder $builder)
	{
		foreach ($this->getGlobalScopes() as $identifier => $scope) {
			$builder->withGlobalScope($identifier, $scope);
		}

		return $builder;
	}



    // /**
    //  * Get a new query builder for the model's table.
    //  *
    //  * @return \Illuminate\Database\Eloquent\Builder
    //  */
    // public function newQuery()
    // {
    //     return $this->registerGlobalScopes($this->newQueryWithoutScopes());
    // }


    // /**
    //  * Register the global scopes for this builder instance.
    //  *
    //  * @param  \Illuminate\Database\Eloquent\Builder  $builder
    //  * @return \Illuminate\Database\Eloquent\Builder
    //  */
    // public function registerGlobalScopes($builder)
    // {
    //     foreach ($this->getGlobalScopes() as $identifier => $scope) {
    //         $builder->withGlobalScope($identifier, $scope);
    //     }

    //     return $builder;
    // }
}
