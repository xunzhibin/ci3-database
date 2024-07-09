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
		return defined(static::class.'::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
	}

	/**
	 * 强制 删除
	 * 
	 * 重写 父类方法
	 * 
	 * @return bool
	 */
	public function forceDelete(): bool
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
	public function restore(): bool
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
	 * 重写 父类方法
	 * 
	 * @return bool
	 */
	protected function performDelete(): bool
	{
		// 强制删除
		if ($this->forceDeleting) {
			// 设置 主键条件
			$result = $this->setPrimaryKeyWhereForDML($this->newModelQueryBuilder())
							// 删除
							->delete();

			// 数据表中 模型存在
			$this->exists = false;

			return true;
		}

		// 软删除
		return $this->runSoftDelete();
	}

	/**
	 * 执行软删除
	 * 
	 * @return bool
	 */
	protected function runSoftDelete(): bool
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
		'withDeleted', // 全部 已删除和未删除
		'withoutDeleted', // 未删除
		'onlyDeleted', // 仅已删除
		'restore', // 恢复
		// 'RestoreOrCreate'
	];

	/**
	 * 模型 查询构造器
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function newQueryBuilder()
	{
		// 调用 父类方法
		$builder = parent::newQueryBuilder();

		// 软删除 作用域
		$this->softDeleteScopes($builder);

		return $builder;
	}

	/**
	 * 软删除 作用域
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function softDeleteScopes(Builder $builder)
	{
		// 删除功能 替换函数
		$builder->onDelete(function (Builder $builder) {
			$column = $builder->getModel()->getDeletedAtColumn();

			return $builder->update([
				$column => $builder->getModel()->freshStorageDateFormatTimestamp()
			]);
		});

		// 查询作用域
		$builder->queryScope(function (Builder $builder) {
			$column = $builder->getModel()->getDeletedAtColumn();

			$builder->whereBatch([ $column => null ]);
		});

		// 添加 扩展
		foreach ($this->extensions as $extension) {
			$this->{'add'. ucfirst($extension)}($builder);
		}
	}

	/**
	 * 添加 恢复 扩展到 查询构造器
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addRestore(Builder $builder)
	{
		$builder->macro('restore', function (Builder $builder) {
			$builder->withDeleted();

			$column = $builder->getModel()->getDeletedAtColumn();

			return $builder->update([ $column => null ]);
		});
	}

	/**
	 * 添加 仅删除 扩展到 查询构造器
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addOnlyDeleted(Builder $builder)
	{
		$builder->macro('onlyDeleted', function (Builder $builder) {
			return $builder->resetQueryScope()->queryScope(function (Builder $builder) {
				$column = $builder->getModel()->getDeletedAtColumn();

				$builder->whereBatch([ $column . ' is not ' => null ]);
			});
		});
	}

	/**
	 * 添加 全部(已删除和未删除) 扩展到 查询构造器
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addWithDeleted(Builder $builder)
	{
		$builder->macro('withDeleted', function (Builder $builder) {
			return $builder->resetQueryScope();
		});
	}

	/**
	 * 添加 未删除 扩展到 查询构造器
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addWithoutDeleted(Builder $builder)
	{
		$builder->macro('withoutDeleted', function (Builder $builder) {
			return $builder->resetQueryScope()->queryScope(function (Builder $builder) {
				$column = $builder->getModel()->getDeletedAtColumn();

				$builder->whereBatch([ $column => null ]);
			});
		});
	}

}
