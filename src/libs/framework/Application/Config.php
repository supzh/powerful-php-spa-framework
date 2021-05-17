<?php 
namespace Application;

class Config
{
	public static $appConfigs;
	
	/**
	 * 加载配置文件.
	 *
	 * @param string $file    配置文件
	 * @param string $key     要获取的配置荐
	 * @param string $default 默认配置。当获取配置项目失败时该值发生作用。
	 * @param bool   $reload  强制重新加载。
	 */
	public static function get($file, $key = '', $default = '', $reload = false)
	{
		if (!$reload && isset(static::$appConfigs[$file])) {
			if (empty($key)) {
				return static::$appConfigs[$file];
			} elseif (isset(static::$appConfigs[$file][$key])) {
				return static::$appConfigs[$file][$key];
			} else {
				return $default;
			}
		}
		$path = appPath('/config/'.$file.'.php');
		if (file_exists($path)) {
			static::$appConfigs[$file] = include $path;
		}
		if (empty($key)) {
			return static::$appConfigs[$file];
		} elseif (isset(static::$appConfigs[$file][$key])) {
			return static::$appConfigs[$file][$key];
		} else {
			return $default;
		}
	}
	
	public static function set($file, $key, $value)
	{
		static::$appConfigs[$file][$key] = $value;
	}
}