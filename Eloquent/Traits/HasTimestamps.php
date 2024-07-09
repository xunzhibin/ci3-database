<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 日期 辅助函数
use Xzb\Ci3\Helpers\Date;

/**
 * 时间戳
 */
trait HasTimestamps
{
// ---------------------- 创建时间 ----------------------
	/**
	 * 创建时间 列名称
	 * 
	 * @var string|null
	 */
	// const CREATED_AT = 'created_at';

	/**
	 * 获取 创建时间 列名称
	 * 
	 * @return string|null
	 */
	public function getCreatedAtColumn()
	{
		return defined(static::class.'::CREATED_AT') ? static::CREATED_AT : 'created_at';
	}

// ---------------------- 更新时间 ----------------------
	/**
	 * 更新时间 列名称
	 * 
	 * @var string|null
	 */
	// const UPDATED_AT = 'updated_at';

	/**
	 * 获取 更新时间 列名称
	 * 
	 * @return string|null
	 */
	public function getUpdatedAtColumn()
	{
		return defined(static::class.'::UPDATED_AT') ? static::UPDATED_AT : 'updated_at';
	}

// ---------------------- 时间戳 ----------------------
	/**
	 * 模型 是否使用 时间戳
	 * 
	 * @var bool
	 */
	public $timestamps = true;

	/**
	 * 模型 是否使用 时间戳
	 * 
	 * @return bool
	 */
	public function usesTimestamps(): bool
	{
		return $this->timestamps;
	}

	/**
	 * 新时间戳
	 * 
	 * @return \Xzb\Ci3\Helpers\Date
	 */
	public function freshTimestamp()
	{
		return Date::now();
	}

	/**
	 * 新的存储日期格式时间戳
	 * 
	 * @return mixed
	 */
	public function freshStorageDateFormatTimestamp()
	{
		return $this->transformToStorageDateFormat(
			$this->freshTimestamp()
		);
	}

	/**
	 * 更新 时间戳
	 * 
	 * @return $this
	 */
	public function updateTimestamps()
	{
		// 新时间戳
		$time = $this->freshTimestamp();

		// 创建时间
		if (
			// 模型 在关联数据表中 不存在
			! $this->exists
			// 创建时间列 存在
			&& $this->getCreatedAtColumn()
			// 创建时间列 没有 被编辑
			&&  ! $this->isEdited($this->getCreatedAtColumn())
		) {
			$this->setAttribute($this->getCreatedAtColumn(), $time);
		}

		// 更新时间
		if (
			// 更新时间列 存在
			$this->getUpdatedAtColumn()
			// 更新时间列 没有 被编辑
			&&  ! $this->isEdited($this->getUpdatedAtColumn())
		) {
			$this->setAttribute($this->getUpdatedAtColumn(), $time);
		}

		return $this;
	}

}
