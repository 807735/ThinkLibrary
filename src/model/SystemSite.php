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

namespace think\admin\model;
use think\model\relation\HasOne;

/**
 * 站点数据
 * @class MallGoods
 * @package app\mall\model
 */
class SystemSite extends Abs
{
    public function user():HasOne{
        return $this->hasOne(SystemUser::class,'id','user_id');
    }

    /**
     * 字段属性处理
     * @param mixed $value
     * @return string
     */
    public function setOpenapiAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 字段属性处理
     * @param mixed $value
     * @return array
     */
    public function getOpenapiAttr($value): array
    {
        return empty($value) ? [] : (is_string($value) ? json_decode($value, true) : $value);
    }
    /**
     * 字段属性处理
     * @param mixed $value
     * @return string
     */
    public function setSmscfgAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 字段属性处理
     * @param mixed $value
     * @return array
     */
    public function getSmscfgAttr($value): array
    {
        return empty($value) ? [] : (is_string($value) ? json_decode($value, true) : $value);
    }


}