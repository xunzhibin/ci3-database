<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

/**
 * 软删除 作用域
 */
class SoftDeletingScope implements Scope
{
	/**
	 * 扩展
	 *
	 * @var array
	 */
	protected $extensions = [
		'Restore',
		'WithSoftDeleted',
		'WithoutSoftDeleted',
		'OnlySoftDeleted'
		// 'Restore', 'RestoreOrCreate', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'
	];

	/**
	 * 应用 作用域
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @param  \Xzb\Ci3\Database\Eloquent\Model $model
	 * @return void
	 */
	public function apply(Builder $builder, Model $model)
	{
		$deletedAtColumn = $builder->getQuery()->hasCiQb('join')
								? $model->qualifyColumn($model->getDeletedAtColumn())
								: $model->getDeletedAtColumn();

		$builder->where($deletedAtColumn, null);
	}

	/**
	 * 扩展 查询构造器
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	public function extend(Builder $builder)
	{
		// 注册 删除功能 替代项
		$builder->onDelete(function (Builder $builder) {
			return $builder->update([
				$builder->getModel()->getDeletedAtColumn() => $builder->getModel()->freshStorageDateFormatTimestamp(),
			]);
		});

		// 添加 扩展
		foreach ($this->extensions as $extension) {
			$this->{"add{$extension}"}($builder);
		}
	}

	/**
	 * 添加 恢复 扩展
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addRestore(Builder $builder)
	{
		$builder->macro('restore', function (Builder $builder) {
			// $builder->withSoftDeleted();
			$builder->onlySoftDeleted();

			return $builder->update([
				$builder->getModel()->getDeletedAtColumn() => null
			]);
		});
	}

	/**
	 * 添加 包含软删除 扩展
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addWithSoftDeleted(Builder $builder)
	{
		$builder->macro('withSoftDeleted', function (Builder $builder) {
			return $builder->withoutGlobalScope($this);
		});
	}

	/**
	 * 添加 没有被软删除 扩展
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addWithoutSoftDeleted(Builder $builder)
	{
		$builder->macro('withoutSoftDeleted', function (Builder $builder) {
			$builder->withoutGlobalScope($this)->where(
				$builder->getModel()->getDeletedAtColumn(),
				null
			);

			return $builder;
		});
	}

	/**
	 * 添加 只有软删除 扩展
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return void
	 */
	protected function addOnlySoftDeleted(Builder $builder)
	{
		$builder->macro('onlySoftDeleted', function (Builder $builder) {
			$builder->withoutGlobalScope($this)->where(
				$builder->getModel()->getDeletedAtColumn() . ' is not ',
				null
			);

			return $builder;
		});
	}

}

// namespace Illuminate\Database\Eloquent;

// class SoftDeletingScope implements Scope
// {
//     /**
//      * All of the extensions to be added to the builder.
//      *
//      * @var string[]
//      */
//     protected $extensions = ['Restore', 'RestoreOrCreate', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

//     /**
//      * Apply the scope to a given Eloquent query builder.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @param  \Illuminate\Database\Eloquent\Model  $model
//      * @return void
//      */
//     public function apply(Builder $builder, Model $model)
//     {
//         $builder->whereNull($model->getQualifiedDeletedAtColumn());
//     }

//     /**
//      * Extend the query builder with the needed functions.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @return void
//      */
//     public function extend(Builder $builder)
//     {
//         foreach ($this->extensions as $extension) {
//             $this->{"add{$extension}"}($builder);
//         }

//         $builder->onDelete(function (Builder $builder) {
//             $column = $this->getDeletedAtColumn($builder);

//             return $builder->update([
//                 $column => $builder->getModel()->freshTimestampString(),
//             ]);
//         });
//     }

//     /**
//      * Get the "deleted at" column for the builder.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @return string
//      */
//     protected function getDeletedAtColumn(Builder $builder)
//     {
//         if (count((array) $builder->getQuery()->joins) > 0) {
//             return $builder->getModel()->getQualifiedDeletedAtColumn();
//         }

//         return $builder->getModel()->getDeletedAtColumn();
//     }

//     /**
//      * Add the restore extension to the builder.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @return void
//      */
//     protected function addRestore(Builder $builder)
//     {
//         $builder->macro('restore', function (Builder $builder) {
//             $builder->withTrashed();

//             return $builder->update([$builder->getModel()->getDeletedAtColumn() => null]);
//         });
//     }

//     /**
//      * Add the restore-or-create extension to the builder.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @return void
//      */
//     protected function addRestoreOrCreate(Builder $builder)
//     {
//         $builder->macro('restoreOrCreate', function (Builder $builder, array $attributes = [], array $values = []) {
//             $builder->withTrashed();

//             return tap($builder->firstOrCreate($attributes, $values), function ($instance) {
//                 $instance->restore();
//             });
//         });
//     }

//     /**
//      * Add the with-trashed extension to the builder.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @return void
//      */
//     protected function addWithTrashed(Builder $builder)
//     {
//         $builder->macro('withTrashed', function (Builder $builder, $withTrashed = true) {
//             if (! $withTrashed) {
//                 return $builder->withoutTrashed();
//             }

//             return $builder->withoutGlobalScope($this);
//         });
//     }

//     /**
//      * Add the without-trashed extension to the builder.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @return void
//      */
//     protected function addWithoutTrashed(Builder $builder)
//     {
//         $builder->macro('withoutTrashed', function (Builder $builder) {
//             $model = $builder->getModel();

//             $builder->withoutGlobalScope($this)->whereNull(
//                 $model->getQualifiedDeletedAtColumn()
//             );

//             return $builder;
//         });
//     }

//     /**
//      * Add the only-trashed extension to the builder.
//      *
//      * @param  \Illuminate\Database\Eloquent\Builder  $builder
//      * @return void
//      */
//     protected function addOnlyTrashed(Builder $builder)
//     {
//         $builder->macro('onlyTrashed', function (Builder $builder) {
//             $model = $builder->getModel();

//             $builder->withoutGlobalScope($this)->whereNotNull(
//                 $model->getQualifiedDeletedAtColumn()
//             );

//             return $builder;
//         });
//     }
// }
