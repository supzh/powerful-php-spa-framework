<?php

namespace Application;

/**
 * @Verify 验证类
 *
 * @author zhangle
 * @date 2016-12-02
 */
class Verify
{	
	/**
	 * @isEmpty 字符串是否为空
	 *
	 * @param string $str 需要验证的参数
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isNotEmpty($str)
	{
		if (is_object($str) == false && is_array($str) == false) {
			$str = trim($str);
		}
		
		return empty($str) ? false : true;
	}
	
	/**
	 * @isNumFormat 是否为数字
	 *
	 * @param int $num 数字
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isNumFormat($num)
	{		
		$exp = '/^[0-9]+$/';
		
		return preg_match($exp, $num) ? true : false;
	}
	
	/**
	 * @isEmail 邮箱验证
	 *
	 * @param string $mail 邮箱
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isEmail($mail)
	{		
		$exp = '/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?/i';		
		return preg_match($exp, $mail) ? true : false;
	}
	
	/**
	 * @isMobile 手机号码
	 *
	 * @param string $mobile 手机号码
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isMobile($mobile)
	{		
		$exp = '/^13[0-9]{1}[0-9]{8}$|14[57]{1}[0-9]{8}$|15[012356789]{1}[0-9]{8}$|17[078]{1}[0-9]{8}$|18[012356789]{1}[0-9]{8}$/';
		
		return preg_match($exp, $mobile) ? true : false;
	}
	
	/**
	 * 验证长度.
	 *
	 * @checkLength 检测长度
	 *
	 * @param string $str     验证字符串
	 * @param int    $type    类型[1:匹配最小值,2:匹配最大值,3取中间]
	 * @param int    $min     最小值
	 * @param int    $max     最大值
	 * @param int    $charset 编码
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function checkLength($str, $type = 3, $min = 0, $max = 0, $charset = 'utf-8')
	{		
		$len = mb_strlen($str, $charset);
		switch ($type) {
			case 1:        //只匹配最小值
				return ($len >= $min) ? true : false;
				break;
			case 2:        //只匹配最大值
				return ($max >= $len) ? true : false;
				break;
			default:    ////min <= $str <= max
				return (($min <= $len) && ($len <= $max)) ? true : false;
				break;
		}
	}
	
	/**
	 * @isName 是否为用户名
	 *
	 * @param string $str  验证字符串
	 * @param int    $min  最小值
	 * @param int    $max  最大值
	 * @param string $flag 正则格式 [空 默认数字+字母+汉字]
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isName($str, $max = 20, $min = 2, $flag = '')
	{		
		$exp = '/^[\x80-\xffa-zA-Z0-9_'.$flag.']{'.$min.','.$max.'}$/';
		
		return preg_match($exp, $str) ? true : false;
	}
	
	/**
	 * @isMoneyFormat 是否为金钱格式
	 *
	 * @param float $val 金钱
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isMoneyFormat($val)
	{		
		if (preg_match('/^[0-9]{1,}$/', $val)) {
			return true;
		}
		if (preg_match('/^[0-9]{1,}\.[0-9]{1,2}$/', $val)) {
			return true;
		}
	}
	
	/**
	 * @isDateFormat 是否为日期格式 0000-00-00
	 *
	 * @param string $date 日期
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isDateFormat($date)
	{		
		$exp = '/^[0-9]{4}\-[0-9]{2}-[0-9]{2}$/';
		
		return preg_match($exp, $date) ? true : false;
	}
	
	/**
	 * @isTimeFormat 是否为时间格式 00:00:00
	 *
	 * @param string $time 时间
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isTimeFormat($time)
	{		
		$exp = '/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/';
		
		return preg_match($exp, $time) ? true : false;
	}
	
	/**
	 * @isDateTimeFormat 是否为日期时间格式 0000-00-00 00:00:00
	 *
	 * @param string $datetime 时间
	 *
	 * @author zhangle
	 * @date 2015-05-26
	 *
	 * @return bool
	 */
	public static function isDateTimeFormat($datetime)
	{		
		$exp = '/^[0-9]{4}\-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';
		
		return preg_match($exp, $datetime) ? true : false;
	}
	
	/**
	 * @isNumRange 数字范围
	 *
	 * @param int $type 类型[1:匹配最小值,2:匹配最大值,3取中间]
	 * @param int $min  最小值
	 * @param int $max  最大值
	 *
	 * @author zhangle
	 * @date 2015-01-28
	 *
	 * @return bool
	 */
	public static function isNumRange($val, $type = 3, $minNum = 0, $maxNum = 0)
	{				
		switch ($type) {
			case 1:        //只匹配最小值
				return ($val >= $minNum) ? true : false;
				break;
			case 2:        //只匹配最大值
				return ($maxNum >= $val) ? true : false;
				break;
			default:    ////minNum <= $val <= maxNum
				return (($minNum <= $val) && ($val <= $maxNum)) ? true : false;
				break;
		}
	}
	
	/**
	 * @escape
	 *
	 * @param string $input
	 *
	 * @return string $output
	 */
	public static function escape($input)
	{
		if (is_array($input)) {
			foreach ($input as $key => $value) {
				$input[$key] = self::escape($value);
			}
		} else {
			$input = trim(strip_tags($input));
		}
		
		return $input;
	}	
}
