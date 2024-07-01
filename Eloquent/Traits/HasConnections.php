<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 模型 查询构造器
use Xzb\Ci3\Database\Eloquent\Builder;

/**
 * 连接 数据库
 */
trait HasConnections
{
// ---------------------- 连接 数据库 ----------------------
	/**
	 * 数据库 连接配置组 名称
	 * 
	 * @var string
	 */
	protected $group = '';

	/**
	 * 设置 连接配置组 名称
	 * 
	 * @param string $group
	 * @return $this
	 */
	public function setConnectionGroup(string $group)
	{
		$this->group = $group;
	}

	/**
	 * 获取 连接配置组 名称
	 * 
	 * @return string
	 */
	public function getConnectionGroup(): string
	{
		return $this->group;
	}

	/**
	 * 基础 查询构造器
	 * 
	 * @return \CI_DB_query_builder
	 */
	protected function baseQueryBuilder()
	{
		// 未连接
		if (! isset(get_instance()->db)) {
			// 连接数据库
			get_instance()->load->database(
				$this->getConnectionGroup()
			);
		}

		return get_instance()->db;
	}

	/**
	 * 模型 查询构造器
	 * 
	 * @return \Xzb\Ci3\Core\Eloquent\Builder
	 */
	public function newModelQueryBuilder()
	{
        return (new Builder($this->baseQueryBuilder()))->setModel($this);
	}

	/**
	 * 作用域 模型 查询构造器
	 * 
	 * @return \Xzb\Ci3\Core\Eloquent\Builder
	 */
	public function newQueryBuilder()
	{
		$builder = $this->newModelQueryBuilder();

		if (in_array($trait = 'SoftDeletes', static::$traits[static::class])) {
			$this->localMacroSoftDeletes($builder);
		}

		return $builder;
	}

}
