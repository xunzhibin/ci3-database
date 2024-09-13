<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

/**
 * 事件
 */
trait HasEvents
{
	/**
	 * 是否 触发事件
	 * 
	 * @var array
	 */
	protected static $triggerEvent = true;

	/**
	 * 事件 集合
	 * 
	 * @var array
	 */
	protected $events = [
		'retrieved',
		'creating', 'created',
		'updating', 'updated',
		'saving', 'saved',
		'restoring', 'restored',
		// 'replicating',
		'deleting', 'trashed', 'deleted',
		'forceDeleting', 'forceDeleted',
	];

	/**
	 * 触发 事件
	 * 
	 * @param string $event
	 * @return void
	 */
	protected function fireModelEvent($event)
	{
		if (
			static::$triggerEvent
			&& method_exists(static::class, $event)
			&& in_array($event, $this->events)
		) {
			static::$event($this);
		}
	}

	/**
	 * 不触发事件 执行回调
	 * 
	 * @param callable $callback
	 * @return mixed
	 */
	public static function withoutEvents(callable $callback)
	{
		$isTrigger = static::$triggerEvent;

		if ($isTrigger) {
			static::$triggerEvent = false;
		}

		try {
			return $callback();
		}
		finally {
			static::$triggerEvent = $isTrigger;
		}
	}

}
