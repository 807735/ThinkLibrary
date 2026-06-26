<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace think\admin\model;

use think\admin\Model;
use think\model\relation\HasOne;

/**
 * 模型抽象类
 * @class Abs
 * @package app\account\model
 */
abstract class Abs extends Model
{
    public function site():HasOne{
        return $this->hasOne(SystemSite::class,'id','site_id');
    }

    public function formSite():HasOne{
        return $this->hasOne(SystemSite::class,'id','form_site_id');
    }
    /**
     * 格式化输出时间
     * @param mixed $value
     * @return string
     */
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 格式化输出时间
     * @param mixed $value
     * @return string
     */
    public function getUpdateTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 时间写入格式化
     * @param mixed $value
     * @return string
     */
    public function setCreateTimeAttr($value): string
    {
        return is_string($value) ? str_replace(['年', '月', '日'], ['-', '-', ''], $value) : $value;
    }

    /**
     * 时间写入格式化
     * @param mixed $value
     * @return string
     */
    public function setUpdateTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     * 字段属性处理
     * @param mixed $value
     * @return string
     */
    public function setExtraAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 字段属性处理
     * @param mixed $value
     * @return array
     */
    public function getExtraAttr($value): array
    {
        return empty($value) ? [] : (is_string($value) ? json_decode($value, true) : $value);
    }

    public function setAuditRemarkAttr($value)
    {
        [$flow,$user,$audit_time] = [$this->getAttr('audit_flow'),session('user'),date('Y-m-d H:i:s')];
        array_unshift($flow, [
            'audit_userid' => $user['id']??'0',
            'audit_user' => $user['username']??'',
            'audit_name' => $user['nickname'],
            'audit_time' => $audit_time,
            'audit_remark' => $value,
        ]);
        $this->set('audit_user', $user['username']??''  );
        $this->set('audit_time', $audit_time  );
        $this->set('audit_flow', $this->setExtraAttr($flow)  );
        return $value;
    }

    public function getAuditFlowAttr($value): array
    {
        return $this->getExtraAttr($value);
    }
}