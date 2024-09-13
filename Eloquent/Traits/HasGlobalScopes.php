<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

use Closure;
use Xzb\Ci3\Database\Eloquent\Scope;
use Xzb\Ci3\Database\Eloquent\ModelGlobalScopeException;

/**
 * 全局 作用域
 */
trait HasGlobalScopes
{
	/**
	 * 全局 作用域 集合
	 * 
	 * @var array
	 */
	protected static $globalScopes = [];

	/**
	 * 添加 全局作用域
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Scope|\Closure|string $scope
	 * 
	 * @return mixed
	 */
	public static function addGlobalScope($scope, $implementation = null)
	{
		if (is_string($scope) && ($implementation instanceof Closure || $implementation instanceof Scope)) {
			return static::$globalScopes[static::class][$scope] = $implementation;
		}
		else if ($scope instanceof Closure) {
			return static::$globalScopes[static::class][spl_object_hash($scope)] = $scope;
		}
		else if ($scope instanceof Scope) {
			return static::$globalScopes[static::class][get_class($scope)] = $scope;
		}

		throw new ModelGlobalScopeException('Global scope must be an instance of Closure or Scope.');
	}

	/**
	 * 获取 全局作用域
	 * 
	 * @return array
	 */
	public function getGlobalScopes()
	{
		return static::$globalScopes[static::class] ?? [];
	}
}
