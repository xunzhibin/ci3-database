<?php

// 命名空间
namespace Xzb\Ci3\Database\Eloquent\Traits;

// 转换 辅助函数
use Xzb\Ci3\Helpers\Transform;

// 模型JSON编码失败 异常类
use Xzb\Ci3\Database\Exception\ModelJsonEncodingFailureException;

/**
 * 转换
 */
trait HasTransforms
{
// ---------------------- 属性值转换 ----------------------
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

	/**
	 * 属性值 转换为 JSON
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return string
	 * 
	 * @throws \Xzb\Ci3\Database\Exception\ModelJsonEncodingFailureException
	 */
	protected function transformAttributeValueToJson(string $key, $value): string
	{
		try {
			return Transform::toJson($value);
		}
		catch (\Throwable $e) {
			throw (new ModelJsonEncodingFailureException(
				"Unable to encode attribute [" . $key . "] for model [" . get_class($this) . "] to JSON: " . json_last_error_msg(),
				$e->getCode(),
				$e
			))->setModel(static::class);
		}
	}

	/**
	 * 转换 模型属性 值
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	protected function transformModelAttributeValue(string $key, $value)
	{
		// 是否有 属性访问器
		if ($this->hasGetAccessor($key)) {
			return $this->getAccessorAttributeValue($key, $value);
		}

		// 是否为 强制转换属性
		if ($this->isCastAttribute($key)) {
			return $this->transformCastAttributeValue($key, $value);
		}

		return $value;
	}

	/**
	 * 转换 强制转换属性的 属性值
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	protected function transformCastAttributeValue(string $key, $value)
	{
		// 强制转换 类型
		$castType = $this->getCastAttributeType($key);

		if (is_null($value)) {
			return $value;
		}

		// 检测 类型
		switch ($castType) {
			// 布尔类型
			case 'bool':
			case 'boolean':
				return (bool)$value;
			// 整形
			case 'int':
			case 'integer':
				return (int)$value;
			// 浮点型
			case 'real': // 实数 
			case 'float': // 浮点数
			case 'double': // 双精度数 
				return Transform::toFloat($value);
			// 小数
			case 'decimal':
				$decimals = explode(':', $this->getCastAttributes()[$key], 2)[1];
				$value = Transform::toFloat($value);
				return number_format($value, $decimals, '.', '');
			// 字符串
			case 'string':
				return (string)$value;
			// 数组
			case 'array':
				return Transform::toArray($value);
			// 对象
			case 'object':
				return Transform::toObject($value);
			// Unix时间戳
			case 'timestamp':
				return Transform::toCustomDateFormat($value, $format = 'U');
			// 日期
			case 'date':
				return Transform::toCustomDateFormat($value, $format = 'Y-m-d');
			// 日期时间
			case 'datetime':
				return Transform::toCustomDateFormat($value, $format = 'Y-m-d H:i:s');
			// 自定义 日期时间
			case 'custom_datetime':
				$format = explode(':', $this->getCastAttributes()[$key], 2)[1];
				return Transform::toCustomDateFormat($value, $format);
		}

		return $value;
	}

}
