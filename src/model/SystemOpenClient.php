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
class SystemOpenClient extends Abs
{

    public function setResponseAttr($value): string
    {
        return $this->setExtraAttr($value);
    }
    public function setRequestAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    public function getResponseAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function getRequestAttr($value): array
    {
        return $this->getExtraAttr($value);
    }
}