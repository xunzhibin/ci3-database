<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

/**
 * 软删除(逻辑删除)
 */
trait SoftDeletes
{
	/**
	 * 是否 强制删除
	 * 
	 * @var bool
	 */
	protected $forceDeleting = false;

	/**
	 * 初始化 软删除
	 * 
	 * @return void
	 */
	public function initializeSoftDeletes()
	{
		array_push($this->dates, $this->getDeletedAtColumn());
	}

	/**
	 * 获取 删除时间 列名称
	 * 
	 * @return string
	 */
	public function getDeletedAtColumn()
	{
		// return defined(static::class.'::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
		return $this->deletedAt ?? 'deleted_at';
	}

	/**
	 * 强制 删除
	 * 
	 * @return bool
	 */
	public function forceDelete()
	{
		// forceDeleting 强制删除前 事件

		// 强制删除
		$this->forceDeleting = true;

		// 删除
		$result = $this->delete();

		// 取消 强制删除
		$this->forceDeleting = false;

		// forceDeleted 强制删除后 事件

		return true;
	}

	/**
	 * 恢复
	 * 
	 * @return bool
	 */
	public function restore()
	{
		// restoring 恢复前 事件

		// 删除时间
		$this->setAttribute($this->getDeletedAtColumn(), null);

		$this->exists = true;

		$result = $this->save();

		// restored 恢复后 事件

		return $result;
	}

// ---------------------- 执行删除 ----------------------
	/**
	 * 执行删除
	 * 
	 * @return bool
	 */
	protected function performDelete()
	{
		if ($this->forceDeleting) {
			// 设置 主键条件
			$result = $this->setPrimaryKeyWhereForDML($this->newModelQueryBuilder())
							// 删除
							->delete();

			// 数据表中 模型存在
			$this->exists = false;

			return true;
		}

		return $this->runSoftDelete();
	}

	/**
	 * 执行软删除
	 * 
	 * @return bool
	 */
	protected function runSoftDelete()
	{
		$time = $this->freshTimestamp();

		// 删除时间
		$this->setAttribute($this->getDeletedAtColumn(), $time);

		// 更新时间
		if ($this->usesTimestamps() && $this->getUpdatedAtColumn()) {
			$this->setAttribute($this->getUpdatedAtColumn(), $time);
		}

		// 获取 被修改 属性
		$editedAttributes = $this->getEditedAttributes();

		// 设置 主键条件
		$rows = $this->setPrimaryKeyWhereForDML($this->newModelQueryBuilder())
						// 更新
						->update($editedAttributes);

		// 同步 原始属性
		$this->syncOriginalAttributes();

		return true;
	}

// ---------------------- 本地宏 ----------------------
	/**
	 * 本地宏 扩展
	 * 
	 * @var array
	 */
	protected $extensions = [
		'onDelete',
		'restore',
		// 'RestoreOrCreate', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'
	];

	/**
	 * 设置 软删除 本地宏
	 * 
	 * @param \Xzb\Ci3\Core\Eloquent\Builder $builder
	 * @return void
	 */
	protected function  localMacroSoftDeletes(Builder $builder)
	{
		foreach ($this->extensions as $extension) {
			$this->{'add'. ucfirst($extension)}($builder);
		}
	}

	/**
	 * 添加 删除替换 扩展到 查询构造器
	 * 
	 * @param \Xzb\Ci3\Core\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addOnDelete(Builder $builder)
	{
		$builder->macro('onDelete', function (Builder $builder) {
			$column = $builder->getModel()->getDeletedAtColumn();

			return $builder->update([
				$column => $builder->getModel()->freshStorageDateFormatTimestamp()
			]);
		});
	}

	/**
	 * 添加 恢复 扩展到 查询构造器
	 * 
	 * @param \Xzb\Ci3\Core\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addRestore(Builder $builder)
	{
		$builder->macro('restore', function (Builder $builder) {
			$column = $builder->getModel()->getDeletedAtColumn();
			return $builder->update([ $column => null ]);

            // $builder->withTrashed();
            // return $builder->update([$builder->getModel()->getDeletedAtColumn() => null]);
		});
	}

}
