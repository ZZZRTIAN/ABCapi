<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Db;
/**
 * 设备或配置系统参数
 * @param string $name 参数名称
 * @param bool $value 默认是false为获取值，否则为更新
 * @return string|bool
 */
function sysconf($name, $value = false) {
    static $config = [];
    if ($value !== false) {
        $config = [];
        $data = ['name' => $name, 'value' => $value];
        return DataService::save('SystemConfig', $data, 'name');
    }
    if (empty($config)) {
        foreach (Db::name('SystemConfig')->select() as $vo) {
            $config[$vo['name']] = $vo['value'];
        }
    }
    return isset($config[$name]) ? $config[$name] : '';
}