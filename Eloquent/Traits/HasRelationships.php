<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 关系
use Xzb\Ci3\Database\Eloquent\Relations\{
	HasOne,
	hasMany,
	HasOneThrough,
	hasManyThrough,
	BelongsTo,
	BelongsToMany
};

/**
 * 关系
 */
trait HasRelationships
{
	/**
	 * 已加载 关系
	 * 
	 * @var array
	 */
	protected $relations = [];

	/**
	 * 设置 已加载 关系
	 * 
	 * @param string $relation
	 * @param mixed $value
	 * @return $this
	 */
	public function setRelation(string $relation, $value)
	{
		$this->relations[$relation] = $value;

		return $this;
	}

	/**
	 * 获取 指定 已加载 关系
	 * 
	 * @param string $relation
	 * @return mixed
	 */
	public function getRelation(string $relation)
	{
		return $this->relations[$relation];
	}

	/**
	 * 销毁 指定 已加载 关系
	 * 
	 * @param string $relation
	 * @return $this
	 */
	public function unsetRelation(string $relation)
	{
		unset($this->relations[$relation]);

		return $this;
	}

	/**
	 * 获取 所有 已加载 关系
	 * 
	 * @return array
	 */
	public function getRelations(): array
	{
		return $this->relations;
	}

	/**
	 * 关系 是否 已加载
	 * 
	 * @param string $relation
	 * @return bool
	 */
	public function isRelationLoaded(string $relation): bool
	{
		return array_key_exists($relation, $this->relations);
	}

// ---------------------- 一对一 ----------------------
	/**
	 * 一对一 关系
	 * 
	 * 通过 父外键 查询 子
	 *
	 * @param string $associationModel
	 * @param string|null $parentForeignKey
	 * @param string|null $parentPrimaryKey
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\HasOne
	 */
	public function hasOne(string $associationModel, string $parentForeignKey = null, string $parentPrimaryKey = null)
	{
		return new HasOne(
			new $associationModel, $this,
			$parentForeignKey ?: $this->getForeignKeyName(),
			$parentPrimaryKey ?: $this->getPrimaryKeyName()
		);
	}

// ---------------------- 一对多 ----------------------
	/**
	 * 一对多 关系
	 * 
	 * 通过 父外键 查询 子
	 * 
	 * @param string $associationModel
	 * @param string|null $parentForeignKey
	 * @param string|null $parentPrimaryKey
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\HasMany
	 */
	public function hasMany(string $associationModel, string $parentForeignKey = null, string $parentPrimaryKey = null)
	{
		return new HasMany(
			new $associationModel, $this,
			$parentForeignKey ?: $this->getForeignKeyName(),
			$parentPrimaryKey ?: $this->getPrimaryKeyName()
		);
	}

// ---------------------- 一对一 远程 ----------------------
	/**
	 * 一对一 远程关系
	 * 
	 * 通过 父外键, 查询 子 下的 孙
	 * 
	 * @param string $associationModel
	 * @param string $middledModel
	 * @param string|null $parentForeignKey
	 * @param string|null $middledForeignKey
	 * @param string|null $parentPrimaryKey
	 * @param string|null $middledPrimaryKey
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\HasOneThrough
	 */
	public function hasOneThrough(
		string $associationModel, string $middledModel,
		string $parentForeignKey = null, string $middledForeignKey = null,
		string $parentPrimaryKey = null, string $middledPrimaryKey = null
	)
	{
		return new HasOneThrough(
			new $associationModel,
			$middledModelInstance = new $middledModel,
			$this,
			$parentForeignKey ?: $this->getForeignKeyName(),
			$middledForeignKey ?: $middledModelInstance->getForeignKeyName(),
			$parentPrimaryKey ?: $this->getPrimaryKeyName(),
			$middledPrimaryKey ?: $middledModelInstance->getPrimaryKeyName()
		);
	}

// ---------------------- 一对多 远程 ----------------------
	/**
	 * 一对多 远程关系
	 * 
	 * 通过 父外键, 查询 子 下的 孙
	 * 
	 * @param string $associationModel
	 * @param string $middledModel
	 * @param string|null $parentForeignKey
	 * @param string|null $middledForeignKey
	 * @param string|null $parentPrimaryKey
	 * @param string|null $middledPrimaryKey
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\hasManyThrough
	 */
	public function hasManyThrough(
		string $associationModel, string $middledModel,
		string $parentForeignKey = null, string $middledForeignKey = null,
		string $parentPrimaryKey = null, string $middledPrimaryKey = null
	)
	{
		return new hasManyThrough(
			new $associationModel,
			$middledModelInstance = new $middledModel,
			$this,
			$parentForeignKey ?: $this->getForeignKeyName(),
			$middledForeignKey ?: $middledModelInstance->getForeignKeyName(),
			$parentPrimaryKey ?: $this->getPrimaryKeyName(),
			$middledPrimaryKey ?: $middledModelInstance->getPrimaryKeyName()
		);
	}

// ---------------------- 属于 ----------------------
	/**
	 * 反向 属于关系
	 * 
	 * 通过 子 中 父外键, 查询 父
	 * 
	 * @param string $associationModel
	 * @param string|null $parentForeignKey
	 * @param string|null $parentPrimaryKey
	 * @param string|null $relationKey
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\BelongsTo
	 */
	public function belongsTo(string $associationModel, string $parentForeignKey = null, string $parentPrimaryKey = null, string $relationKey = null)
	{
		return new BelongsTo(
			$associationModel = new $associationModel,
			$this,
			$parentForeignKey ?: $associationModel->getForeignKeyName(),
			$parentPrimaryKey ?: $associationModel->getPrimaryKeyName(),
			$this->parseBelongsToRelationKeyName($relationKey)
		);
	}

	/**
	 * 解析 属于 关系键名
	 * 
	 * @param string $relationKeyName
	 * @return string
	 */
	protected function parseBelongsToRelationKeyName(string $relationKeyName = null)
	{
		if (is_null($relationKeyName)) {
			[$one, $two, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

			$relationKeyName = $caller['function'];
		}

		return $relationKeyName;
	}

// ---------------------- 多对多 ----------------------
	/**
	 * 多对多 关系
	 * 
	 * @param string $associationModel
	 * @param string $table
	 * @param string|null $parentForeignKey
	 * @param string|null $associationForeignKey
	 * @param string|null $parentPrimaryKey
	 * @param string|null $associationPrimaryKey
	 * @return \Xzb\Ci3\Database\Eloquent\Relations\BelongsToMany
	 */
	public function belongsToMany(
		string $associationModel, string $table = null,
		string $parentForeignKey = null, string $associationForeignKey = null,
		string $parentPrimaryKey = null, string $associationPrimaryKey = null
	)
	{
		$associationModel = new $associationModel;

		if (! $table) {
			$table = $this->joiningTable($associationModel);
		}

		return new BelongsToMany(
			$associationModel,
			$this,
			$table,
			$parentForeignKey ?: $this->getForeignKeyName(),
			$associationForeignKey ?: $associationModel->getForeignKeyName(),
			$parentPrimaryKey ?: $this->getPrimaryKeyName(),
			$associationPrimaryKey ?: $associationModel->getPrimaryKeyName()
		);
	}

	/**
	 * 多对多 联接表名称
	 * 
	 * @param  \Xzb\Ci\Database\Eloquent\Model $relatedInstance
	 * @return string
	 */
	public function joiningTable($relatedInstance)
	{
		$segments = [
			$relatedInstance->getJoiningTableSegment(),
			$this->getJoiningTableSegment(),
		];

		// 对值 升序排序
		sort($segments);

		return strtolower(implode('_', $segments));
	}


}