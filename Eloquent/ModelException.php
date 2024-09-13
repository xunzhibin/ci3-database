<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent;

/**
 * 模型 异常类
 */
class ModelException extends \RuntimeException implements ModelExceptionInterface
{
	/**
	 * Eloquent 模型名称
	 * 
	 * @var string
	 */
	protected $model;

	/**
	 * 获取 Eloquent 模型名称
	 * 
	 * @return string
	 */
	public function getModel(): string
	{
		return $this->model;
	}

	/**
	 * 设置 Eloquent 模型名称
	 * 
	 * @param mixed $model
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = class_basename($model);

		return $this;
	}

}
