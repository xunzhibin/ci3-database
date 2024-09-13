<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// 字符串 辅助函数
use Xzb\Ci3\Helpers\Str;

// 支点 特性
use Traits\AsPivot;
// Eloquent 模型
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 多对多
 */
class BelongsToMany extends Relation
{
	use Traits\PivotTable;

	/**
	 * 父模型 外键
	 * 
	 * @var string
	 */
	protected $parentForeignKey;

	/**
	 * 父模型 主键
	 * 
	 * @var string
	 */
	protected $parentPrimaryKey;

	/**
	 * 关联模型 外键
	 * 
	 * @var string
	 */
	protected $relatedForeignKey;

	/**
	 * 关联模型 主键
	 * 
	 * @var string
	 */
	protected $relatedPrimaryKey;

	/**
	 * 中间表 关系的 访问器名
	 * 
	 * @var string
	 */
	protected $accessor = 'pivot';

	/**
	 * 构造函数
	 */
	public function __construct(
		Model $parent, Model $related, string $table,
		string $parentForeignKey, string $relatedForeignKey,
		string $parentPrimaryKey, string $relatedPrimaryKey
	)
	{
		$this->parentForeignKey = $parentForeignKey;
		$this->parentPrimaryKey = $parentPrimaryKey;
	
		$this->relatedForeignKey = $relatedForeignKey;
		$this->relatedPrimaryKey = $relatedPrimaryKey;


		$this->table = $this->resolveTableName($table);

		parent::__construct($parent, $related);
	}

	/**
	 * 添加 基本约束
	 */
	public function addConstraints()
	{
		// 关联 中间表
		$this->query->join(
			$this->table,
			$this->relatedQualifyColumn($this->relatedPrimaryKey) . ' = ' . $this->pivotQualifyColumn($this->relatedForeignKey)
		);

		// 查询条件
		$this->query->where(
			$this->pivotQualifyColumn($this->parentForeignKey),
			$this->parent->{$this->parentPrimaryKey}
		);
	}

	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	public function getResults()
	{
		if (is_null($this->parent->{$this->parentPrimaryKey})) {
			return $this->getDefault();
		}

		return $this->get();
	}

	/**
	 * 获取 默认结果
	 * 
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function getDefault()
	{
		return parent::getDefault() ?: $this->related->newCollection();
	}

	/**
	 * 设置 中间表 关系的访问器名
	 * 
	 * @param string $accessor
	 * @return $this
	 */
	public function as(string $accessor)
	{
		$this->accessor = $accessor;

		return $this;
	}

	/**
	 * 读取 记录
	 * 
	 * @param array $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Collection
	 */
	public function get(array $columns = ['*'])
	{
		$models = $this->query->getModels(
			$this->mergeSelect($columns)
		);

		$this->mergePivotReltaion($models);

		return $this->related->newCollection($models);
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
	public function offsetPaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
	{
		$paginator = $this->query->offsetPaginate($perPage, $this->mergeSelect($columns), $pageName, $page);

		$this->mergePivotReltaion($paginator->items());

		return $paginator;
	}

	/**
	 * 合并 查询列
	 * 
	 * @param array $columns
	 * @return array
	 */
	protected function mergeSelect(array $columns = ['*'])
	{
		if ($columns == ['*']) {
			$columns = [ $this->related->getTable() . '.*'];
		}

		return array_merge($columns, $this->getPivotColumns());
	}

	/**
	 * 合并中间表关系
	 * 
	 * @param array $models
	 * @return void
	 */
	protected function mergePivotReltaion(array $models)
	{
		foreach ($models as $model) {
			$pivotModel = $this->newExistingPivotInstance(
				$this->migratePivotAttributes($model)
			);

			$model->setRelation($this->accessor, $pivotModel);
		}
	}

	/**
	 * 迁移 中间表 属性
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Model
	 * @return array
	 */
	protected function migratePivotAttributes(Model $model)
	{
		$values = [];

		foreach ($model->getAttributes() as $key => $value) {
			if (Str::startsWith($key, $needle = 'pivot_')) {
				$values[substr($key, strlen($needle))] = $value;

				unset($model->$key);
			}
		}

		return $values;
	}

// ---------------------- 关联模型 操作 ----------------------
	/**
	 * 创建 关联模型
	 * 
	 * @param array $attributes
	 * @param array $pivotAttributes
	 * @return 
	 */
	public function create(array $attributes = [], array $pivotAttributes = [])
	{
		$instance = $this->related->newInstance($attributes);

		$instance->save();

		$this->attach($instance, $pivotAttributes);

		return $instance;
	}

}