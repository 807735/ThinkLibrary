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
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-common
// | github 代码仓库：https://github.com/zoujingli/think-plugs-common
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace think\admin\model;


/**
 * 账号短信验证模型
 * @class AccountMsms
 * @package app\manage\model
 */
class SystemMsms extends Abs
{
    
    public function getSendTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    public function setSendTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    public function getPlanTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    public function setPlanTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }


    public function setParamsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }
    public function setResultAttr($value): string
    {
        return $this->setExtraAttr($value);
    }


    public function getParamsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function getResultAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

}