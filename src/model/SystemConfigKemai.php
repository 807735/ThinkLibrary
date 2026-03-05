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

use app\account\model\Abs;
use app\account\model\AccountUser;
use app\member\model\MemberUser;
use think\model\relation\HasOne;

class SystemConfigKemai extends Abs
{

    public function setItemclsAttr($value): string
    {
        $value = is_string($value) ? str2arr($value) : [];
        return arr2str($value ?? []);
    }

    public function getItemclsAttr($value): array
    {
        return str2arr($value ?? '');
    }

    public function setPayclsAttr($value): string
    {
        return $this->setItemclsAttr($value);
    }

    public function getPayclsAttr($value): array
    {
        return $this->getItemclsAttr($value);
    }

    public function setVipclsAttr($value): string
    {
        return $this->setItemclsAttr($value);
    }

    public function getVipclsAttr($value): array
    {
        return $this->getItemclsAttr($value);
    }

    public function setNotItemsAttr($value): string
    {
        return $this->setItemclsAttr($value);
    }

    public function getNotItemsAttr($value): array
    {
        return $this->getItemclsAttr($value);
    }

}