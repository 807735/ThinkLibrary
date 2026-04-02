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
use app\data\model\DataConfigCompany;
use app\service\model\ServiceAuth;
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

    public function wechatAuth(){
        return $this->hasOne(ServiceAuth::class,'site_id','id')->where(['deleted' => 0]);
    }

    /**
     * 获取站点ID
     * @return string
     */
    public function getSiteId(){
        $prefix = date('Ymd');
        $count = self::mk()->whereRaw("id like CONCAT('{$prefix}', '%')")->count()+1;
        return $prefix . str_pad((string)$count, 4, "0", STR_PAD_LEFT);
    }


    public function setOpenapiAttr($value): string   {
        return $this->setExtraAttr($value);
    }

    public function getOpenapiAttr($value): array   {
        return $this->getExtraAttr($value);
    }

    public function setSmscfgAttr($value): string  {
        return $this->setExtraAttr($value);
    }

    public function getSmscfgAttr($value): array  {
        return $this->getExtraAttr($value);
    }

    public function setAgreementcfgAttr($value):string {
        return $this->setExtraAttr($value); ;
    }

    public function getAgreementcfgAttr($value): array    {
        return $this->getExtraAttr($value);
    }

    public function setOrdercfgAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getOrdercfgAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setAppcfgAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getAppcfgAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setPagecfgAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getPagecfgAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setMaterialcfgAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getMaterialcfgAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setContractcfgAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getContractcfgAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setAccountcfgAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getAccountcfgAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setMallapiAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getMallapiAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setExpressRegionNosAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getExpressRegionNosAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setWechatcfgAttr($value):string{
        return $this->setExtraAttr($value); ;
    }

    public function getWechatcfgAttr($value): array
    {
        return $this->getExtraAttr($value);
    }



}