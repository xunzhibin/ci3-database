<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// Eloquent 模型类
use Xzb\Ci3\Database\Eloquent\Model;
// 软删除 特性
use Xzb\Ci3\Database\Eloquent\SoftDeletes;

/**
 * 一对一 或 一对多 远程 抽象类
 */
abstract class HasOneOrManyThrough extends Relation
{
	/**
	 * 中间直通 模型实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $through;

	/**
	 * 构造函数
	 * 
	 * @return void
	 */
	public function __construct(
		Model $parent, Model $related, Model $through,
		string $parentForeignKey, string $throughForeignKey,
		string $parentPrimaryKey, string $throughPrimaryKey
	)
	{
		$this->through = $through;

		$this->parentForeignKey = $parentForeignKey;
		$this->throughForeignKey = $throughForeignKey;

		$this->parentPrimaryKey = $parentPrimaryKey;
		$this->throughPrimaryKey = $throughPrimaryKey;

		parent::__construct($parent, $related);
	}

	/**
	 * 添加 基本约束
	 * 
	 * @return void
	 */
	public function addConstraints()
	{
		$query = $this->getRelationQuery();

		// 关联 中间直通 模型
		$query->join(
			$this->through->getTable(),
			$this->relatedQualifyColumn($this->throughForeignKey) . ' = ' . $this->throughQualifyColumn($this->throughPrimaryKey)
		);

		if ($this->throughSoftDeletes()) {
			$query->withGlobalScope('SoftDeletableHasManyThrough', function ($query) {
				$query->where($this->throughQualifyColumn($this->through->getDeletedAtColumn()));
			});
		}

		$query->where(
			$this->throughQualifyColumn($this->parentForeignKey),
			$this->parent[$this->parentPrimaryKey]
		);
	}

	/**
	 * 读取 记录
	 * 
	 * @param array $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function get(array $columns = ['*'])
	{
		return $this->query->get($this->throughQualifyColumns($columns));
	}

	/**
	 * 第一条 记录
	 * 
	 * @param array $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function first(array $columns = ['*'])
	{
		return $this->get($columns)->first();
	}

	/**
	 * 偏移量 分页
	 * 
	 * @param int|null $perPage
	 * @param array $columns
	 * @param string $pageName
	 * @param int|null $page
	 * @return \Xzb\Ci3\Database\Eloquent\Paginator
	 */
	public function offsetPaginate($perPage = null, array $columns = ['*'], $pageName = 'page', $page = null)
	{
		return $this->query->offsetPaginate($perPage, $this->throughQualifyColumns($columns), $pageName, $page);
	}

// ---------------------- 中间直通模型 ----------------------
	/**
	 * 中间直通模型 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function throughQualifyColumn(string $column): string
	{
		return $this->through->qualifyColumn($column);
	}

	/**
	 * 中间直通模型 限定列
	 * 
	 * @param array $columns
	 * @return array
	 */
	public function throughQualifyColumns(array $columns): array
	{
		return array_map(function ($column) {
			return $this->through->qualifyColumn($column);
		}, $columns);
	}

	/**
	 * 中间直通模型 是否使用 软删除
	 * 
	 * @return bool
	 */
	public function throughSoftDeletes()
	{
		return in_array(SoftDeletes::class, class_traits($this->through));
	}

}
