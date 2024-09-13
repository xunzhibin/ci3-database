<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits\Attributes;

// 转换 辅助函数
use Xzb\Ci3\Helpers\Transform;
// 日期 辅助函数
use Xzb\Ci3\Helpers\Date;

/**
 * 日期 属性
 */
trait DateTimestamps
{
	/**
	 * 创建时间 列名称
	 * 
	 * @var string|null
	 */
	// const CREATED_AT = 'created_at';

	/**
	 * 更新时间 列名称
	 * 
	 * @var string|null
	 */
	// const UPDATED_AT = 'updated_at';

	/**
	 * 是否使用 时间戳
	 * 
	 * @var bool
	 */
	public $timestamps = true;

	/**
	 * 模型 日期属性 存储格式
	 * 
	 * @var string
	 */
	protected $dateFormat = 'Y-m-d H:i:s';

	/**
	 * 模型 日期属性
	 * 
	 * @var array
	 */
	protected $dates = [];

	/**
	 * 获取 创建时间 列名称
	 * 
	 * @return string|null
	 */
	public function getCreatedAtColumn()
	{
		return defined(static::class.'::CREATED_AT') ? static::CREATED_AT : 'created_at';
	}

	/**
	 * 获取 更新时间 列名称
	 * 
	 * @return string|null
	 */
	public function getUpdatedAtColumn()
	{
		return defined(static::class.'::UPDATED_AT') ? static::UPDATED_AT : 'updated_at';
	}

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
	 * 获取 日期属性
	 * 
	 * @return array
	 */
	public function getDateAttributes(): array
	{
		// 自动维护 操作时间
		if ($this->usesTimestamps()) {
			return array_unique(array_merge($this->dates, [
				$this->getCreatedAtColumn(),
				$this->getUpdatedAtColumn(),
			]));
		}

		return $this->dates;
	}

	/**
	 * 是否为 日期属性
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function isDateAttribute(string $key): bool
	{
		return in_array($key, $this->getDateAttributes(), true);
	}

	/**
	 * 设置 日期属性 存储格式
	 * 
	 * @param string $format
	 * @return $this
	 */
	public function setDateFormat(string $format)
	{
		$this->dateFormat = $format;

		return $this;
	}

	/**
	 * 获取 日期属性 存储格式
	 * 
	 * @return string
	 */
	public function getDateFormat(): string
	{
		return $this->dateFormat;
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
			&&  ! $this->hasEditedAttributes($this->getCreatedAtColumn())
		) {
			$this->setAttribute($this->getCreatedAtColumn(), $time);
		}

		// 更新时间
		if (
			// 更新时间列 存在
			$this->getUpdatedAtColumn()
			// 更新时间列 没有 被编辑
			&&  ! $this->hasEditedAttributes($this->getUpdatedAtColumn())
		) {
			$this->setAttribute($this->getUpdatedAtColumn(), $time);
		}

		return $this;
	}

	/**
	 * 转换为 存储日期格式
	 * 
	 * @param mixed $value
	 * @return mixed
	 */
	public function transformToStorageDateFormat($value)
	{
		return empty($value)
					? $value
					: Transform::toDateObject($value, $format = $this->getDateFormat())->format($format);
	}

}
