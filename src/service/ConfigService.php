<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace think\admin\service;

use think\admin\Library;

/**
 * 配置服务
 * @class ConfigService
 * @package app\mall\service
 */
abstract class ConfigService
{

    /**
     * 配置缓存名
     * @var string
     */
    private static $skey = 'plugin.base.config';
    
    /**
     * 页面类型配置
     * @var string[]
     */
    public static $pageTypes = [
        'user_privacy'   => '用户隐私政策',
        'user_agreement' => '用户使用协议',
    ];

    /**
     * 是否开发模式
     * @return bool
     */
    public static function isDev():bool{
        $domainList = array_filter(self::myDomain(),function ($vo){
            return $vo['isDev'] == 1;
        });
        return count(array_filter(array_column($domainList,'domain'),function ($var){
                return stripos(Library::$sapp->request->domain(), $var) !== false;
        })) > 0;
    }

    public static function myDomain():array{
        return [
            ['domain' => 'help.pcnsos.com','isDev' => 0], // 正式版本地址
            ['domain' => 'www.rescue.com','isDev' => 1],
            ['domain' => '192.168.3.8','isDev' => 1],
            ['domain' => '192.168.3.5','isDev' => 1],
            ['domain' => 'dev.rescue.com','isDev' => 1],
            ['domain' => 'help.zsh88.cn','isDev' => 1],
            ['domain' => 'rescue.zhenshihuishop.net','isDev' => 1],
        ];
    }

    /**
     * 读取配置参数
     * @param string|null $name
     * @param $default
     * @return array|mixed|null
     * @throws \think\admin\Exception
     */
    public static function get(?string $name = null, $default = null)
    {
        $syscfg = sysvar(self::$skey) ?: sysvar(self::$skey, sysdata(self::$skey));
        if (empty($syscfg['base_domain'])) $syscfg['base_domain'] = sysconf('base.site_host') . '/h5';
        return is_null($name) ? $syscfg : ($syscfg[$name] ?? $default);
    }

    /**
     * 保存配置参数
     * @param array $data
     * @return mixed
     * @throws \think\admin\Exception
     */
    public static function set(array $data)
    {

        return sysdata(self::$skey, $data);
    }

    /**
     * 设置页面数据
     * @param string $code 页面编码
     * @param array $data 页面内容
     * @return mixed
     * @throws \think\admin\Exception
     */
    public static function setPage(string $code, array $data)
    {
        return sysdata("page.{$code}", $data);
    }

    /**
     * 获取页面内容
     * @param string $code
     * @return array
     * @throws \think\admin\Exception
     */
    public static function getPage(string $code): array
    {
        return sysdata("page.{$code}");
    }
}