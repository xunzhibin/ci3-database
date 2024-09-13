<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

/**
 * 启动、初始化
 */
trait HasBoots
{
	/**
	 * 已启动 模型
	 * 
	 * @var array
	 */
	protected static $booted = [];

	/**
	 * 初始化 特性 集合
	 * 
	 * @var array
	 */
	protected static $traitInitializers = [];

	/**
	 * 未启动时 启动
	 *
	 * @return void
	 */
	protected function bootIfNotBooted()
	{
		if (! isset(static::$booted[static::class])) {
			static::$booted[static::class] = true;

			static::booting();
			static::boot();
			static::booted();
		}
	}

	/**
	 * 启动 模型前 执行
	 * 
	 * @return void
	 */
	protected static function booting()
	{

	}

	/**
	 * 启动 模型
	 * 
	 * @return void
	 */
	protected static function boot()
	{
		static::bootTraits();
	}

	/**
	 * 启动 模型 特性
	 * 
	 * @return void
	 */
	protected static function bootTraits()
	{
		$class = static::class;

		$booted = [];
		static::$traitInitializers[$class] = [];

		foreach (class_traits($class) as $trait) {
			// 启动 特性
			$method = 'boot' . class_basename($trait);
			if (method_exists($class, $method) && ! in_array($method, $booted)) {
				forward_static_call([$class, $method]);

				$booted[] = $method;
			}

			// 特性 初始化
			if (method_exists($class, $method = 'initialize' . class_basename($trait))) {
				static::$traitInitializers[$class][] = $method;

				static::$traitInitializers[$class] = array_unique(
					static::$traitInitializers[$class]
				);
			}
		}
	}

	/**
	 * 初始化 特性
	 * 
	 * @return void
	 */
	protected function initializeTraits()
	{
		foreach (static::$traitInitializers[static::class] as $method) {
			$this->{$method}();
		}
	}

	/**
	 * 启动 模型后 执行
	 * 
	 * @return void
	 */
	protected static function booted()
	{

	}
}
