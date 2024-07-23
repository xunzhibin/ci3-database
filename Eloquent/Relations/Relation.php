<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Relations;

// 模型 Eloquent 查询构造器类
use Xzb\Ci3\Database\Eloquent\Builder;
// 模型实例
use Xzb\Ci3\Database\Eloquent\Model;

/**
 * 关系 抽象类
 */
abstract class Relation
{
	/**
	 * 关联模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $associationModel;

	/**
	 * 关联模型 主键
	 * 
	 * @var string
	 */
	protected $associationModelPrimaryKey;

	/**
	 * 关联模型 外键
	 * 
	 * @var string
	 */
	protected $associationModelForeignKey;

	/**
	 * 父模型 实例
	 * 
	 * @var \Xzb\Ci3\Database\Eloquent\Model
	 */
	protected $parentModel;

	/**
	 * 父模型 主键
	 * 
	 * @var string
	 */
	protected $parentModelPrimaryKey;

	/**
	 * 父模型 外键
	 * 
	 * @var string
	 */
	protected $parentModelForeignKey;

	/**
	 * 查询扩展
	 * 
	 * @var array
	 */
	protected $queryExtensions = [];

	/**
	 * 构造函数
	 * 
	 * @param \Xzb\Ci3\Database\Eloquent\Builder
	 * @param \Xzb\Ci3\Database\Eloquent\Model
	 * @return void
	 */
	public function __construct(Model $associationModel, Model $parentModel)
	{
		$this->associationModel = $associationModel;
		$this->parentModel = $parentModel;

		$this->addConstraints();
	}

	/**
	 * 获取 结果
	 * 
	 * @return mixed
	 */
	abstract public function getResults();

	/**
	 * 设置 关系查询的 基本约束
	 * 
	 * @return void
	 */
	abstract protected function addConstraints();

	/**
	 * 获取 关系 默认值
	 * 
	 * @return mixed
	 */
	protected function getDefaultFor()
	{
		return ;
	}

// ---------------------- 关联模型 ----------------------
	/**
	 * 获取 关联模型 Eloquent 查询构造器
	 * 
	 * @return Xzb\Ci3\Database\Eloquent\Builder;
	 */
	public function getQueryBuilder()
	{
		return $this->associationModel->newQueryBuilder();
	}

	/**
	 * 获取 关联模型 限定列
	 * 
	 * @param string $column
	 * @return string
	 */
	public function getAssociationModelQualifyColumn(string $column): string
	{
		return $this->associationModel->qualifyColumn($column);
	}

	/**
	 * 获取 关联模型 限定列
	 * 
	 * @param array $columns
	 * @return array
	 */
	public function getAssociationModelQualifyColumns(array $columns): array
	{
		return $this->associationModel->qualifyColumns($columns);
	}

	/**
	 * 转换为 关联模型 限定列
	 * 
	 * @param Xzb\Ci3\Database\Eloquent\Builder|null $builder
	 * @param array $columns
	 * @return array
	 */
	protected function convertToAssociationModelQualifyColumns(array $columns = ['*'], $builder = null): array
	{
		if ($builder) {
			$columns = $builder->getQueryPropertyValue('select') ? [] : $columns;
		}

		if ($columns == ['*']) {
			$columns = [ $this->getAssociationModelQualifyColumn('*') ];
		}

		return $columns;
	}

// ---------------------- 父模型 ----------------------
	/**
	 * 获取 父模型 主键值
	 * 
	 * @return mixed
	 */
	public function getParentModelPrimaryKeyValue()
	{
		return $this->parentModel->getAttribute($this->parentModelPrimaryKey);
	}

// ---------------------- 读取操作 ----------------------
	/**
	 * 读取 记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Eloquent\Conllection
	 */
	public function get($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		return $this->performQueryExtension($builder = $this->getQueryBuilder())
						->get($this->convertToAssociationModelQualifyColumns($columns, $builder));
	}

	/**
	 * 读取 查询结果的第一条记录
	 * 
	 * @param array|string $columns
	 * @return \Xzb\Ci3\Database\Model|null
	 */
	public function first($columns = ['*'])
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		return $this->performQueryExtension($builder = $this->getQueryBuilder())
						->first($this->convertToAssociationModelQualifyColumns($columns, $builder));
	}

// ---------------------- 查询扩展 ----------------------
	/**
	 * 设置 查询扩展
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return $this
	 */
	public function setQueryExtension(string $method, array $parameters)
	{
		$this->queryExtensions[$method] = array_merge(
			$this->queryExtensions[$method] ?? [],
			$parameters
		);

		return $this;
	}

	/**
	 * 执行 查询扩展
	 * 
	 * @param Xzb\Ci3\Database\Eloquent\Builder $builder
	 * @return Xzb\Ci3\Database\Eloquent\Builder
	 */
	public function performQueryExtension(Builder $builder)
	{
		// 执行 查询 扩展
		foreach ($this->queryExtensions as $method => $parameters) {
			$builder->{$method}(...$parameters);
		}

		return $builder;
	}

// ---------------------- 魔术方法 ----------------------
	/**
	 * 处理调用 不可访问 方法
	 * 
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$this->setQueryExtension($method, $parameters);

		return $this;
	}

}