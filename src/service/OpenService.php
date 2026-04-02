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

namespace think\admin\service;
 
use app\data\model\DataFamily;
use app\member\model\MemberUser;
use OpenClient\Exceptions\InvalidArgumentException;
use think\admin\Exception;
use think\admin\extend\HttpExtend;
use think\admin\model\SystemSncData;
use think\admin\model\SystemConfigKemai;
use think\admin\Service;
use think\exception\HttpResponseException;

/**
 * Class OpenService
 * @package app\manage\service
 *
 * ----- ThinkService -----
 * @method \OpenClient\App OpenApp() static APP网关
 * @method \OpenClient\Msg OpenMsg() static 短信网关
 * @method \OpenClient\Km OpenKm() static 科脉网关
 * @method \OpenClient\Help OpenHelp() static 帮扶计划网关
 * @method \OpenClient\Bank OpenBank() static 银行网关
 * @method \OpenClient\Notify OpenNotify() static 异步回调
 * @method \OpenClient\IdentityCard OpenIdentityCard() static 身份证网关
 */
class OpenService extends Service
{
    protected array $config = [];

    public static function __callStatic($name, $arguments){
        [$type, $class, $classname] = self::paraseName($name);
        if ("{$type}{$class}" !== $name) {
            throw new \think\Exception("抱歉，实例 {$name} 不在符合规则！");
        }
        try {
            return new $classname(self::instance()->getConfig());
        }catch (Exception|InvalidArgumentException $exception){
            throw new \think\Exception($exception->getMessage());
        }
    }

    private static function paraseName($name)
    {
        foreach (['Open' => 'OpenClient'] as $type => $na) {
            if (strpos($name, $type) === 0) {
                list(, $class) = explode($type, $name);
                return [$type, $class, "\\{$na}\\{$class}"];
            }
        }
        return ['-', '-', $name];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getConfig()
    { 
        $config = AdminService::getSite('openapi');
        return [
            'site_id'           => AdminService::getSite('id',0),
            'app_path'          => $config['app_path']??'',
            'app_code'          => $config['app_code']??'',
            'appsecret'         => $config['appsecret']??'',
        ];
    }

    /**
     * @param $retailId
     * @param string $family_code
     * @return SystemSncData
     * @throws Exception
     */
    public function synRetail($order,string $family_code = ''): SystemSncData
    {
        [$retail,$entry,$pays] = [$order['Retail'],$order['Entry'],$order['Pay']];

        $config = SystemConfigKemai::mk()->where(['branch_id' => $retail['BranchId']])->findOrEmpty();
        if ($config->isEmpty())   throw new Exception('下单门店不符合');
        if ($config->getAttr('status') == 0)   throw new Exception("门店【{$config['branch_name']}】未开通积分兑换功能");
        $ratio = $config['ratio'];
        if ($ratio == 0)  throw new Exception("未定义转换比率");


        // 获取同步数据是否存在
        $injectionInfo = SystemSncData::mk()->where(['code' => $retail['Id']])->whereIn('status',[1,2,3])->findOrEmpty();
        if ($injectionInfo->isExists())  throw new Exception("单据[{$retail['Id']}]已被使用");
        // 单据类型
        if (!in_array($retail['SellWay'],['A','B'])) throw new Exception("单据类型错误");
        if ($retail['SellWay'] == 'A'){
            // 单据判断
            if (empty($retail['VipId']) || empty($retail['IdentityCardId'])) throw new Exception("单据的下单会员并未实名");
            // 会员类别判断
            if (!empty($config['vipcls']) && !in_array($retail['VipclsId'],$config['vipcls']) ){
                throw new Exception("下单会员类别不符");
            }
            // 商品类别判断
            if ($config['itemcls']){
                if (count(array_intersect(array_column($entry,'ItemClsId'),$config['itemcls'])) <= 0 ){
                    throw new Exception("下单产品类别不符合");
                }else{
                    // 筛选符合条件的产品
                    $entry = array_filter($entry,function ($vo) use ($config){
                        return  in_array($vo['ItemClsId'],$config['itemcls']);
                    });
                }
            }
            empty($entry) && throw new Exception("没有符合条件的产品");
            // 下单支付方式判断
            if ($config['paycls'] && count(array_intersect(array_column($pays,'PayWay'),$config['paycls'])) <= 0 ){
                throw new Exception("订单支付方式类别不符合");
            }
            // 包含此商品的订单将不给积分
            if ($config->getAttr('not_items') && count(array_intersect(array_column($entry,'ItemId'),$config->getAttr('not_items'))) > 0){
                throw new Exception("此订单不符合赠送积分标准");
            }

            /**
             * 家庭编号
             * 如果家庭编号未指定的情况，则根据零售单的身份证进行查询家庭组编号
             */
            if (empty($family_code)){
                $family = MemberUser::mk()->alias('a')
                    ->where(['idcard' => $retail['IdentityCardId']])
                    ->join('data_family b',"a.family_code = b.code and b.status in (3,4,5) and b.cancel_status=0 and b.deleted_status = 0")
                    ->field('b.code')->findOrEmpty();
                $family->isEmpty() && throw new Exception("用户并未绑定家庭组");
                $family_code = $family->getAttr('code');
            }


            //===================== 获取对应的用户信息 =====================
            $starDate = $config['stardate']?:'2025-01-01'; // 科脉产生数据的起始日期
            // 获取当前家庭组内所有成员身份证
            $member = MemberUser::mk()->alias('a')
                ->join('data_family b',"a.family_code = b.code and b.status in (3,4,5) and b.cancel_status=0 and b.deleted_status = 0")
                ->where(['a.family_code' => $family_code ,'idcard' => $retail['IdentityCardId'] ])
                ->field('a.id,a.username,a.mobile,a.idcard,a.family_code,b.payment_time')->findOrEmpty();
            if ($member->isEmpty()) throw new Exception("符合条件用户不存在");

            // 时间限制
            $starDate = date('Y-m-d H:i:s',max(strtotime($member->getAttr('payment_time')),strtotime($starDate)));
            if (strtotime($retail['OperDate']) < strtotime($starDate))  throw new Exception("订单小于限定时间");

        }else if ($retail['SellWay'] == 'B'){
            $base = SystemSncData::mk()->where(['code' => $retail['VoucherId']])->whereIn('status',[1,2,3])->findOrEmpty();
            if ($base->isEmpty()) throw new Exception("退款对应的订单数据不存在");
            if (in_array($base->getAttr('status'),[1,2])) throw new Exception("退款对应的订单状态错误");
            // 获取已经发放的家庭
            $extra = $base->getAttr('extra');
            $ratio = $extra['ratio'];
            $member = [
                'id' => $extra['nuid'],
                'family_code' => $extra['familyCode'],
                'username' => $extra['username'],
                'mobile' => $extra['mobile'],
            ];
            // 筛选 退款产品
            $entry = array_filter($entry,function ($v) use ($extra){
                return in_array($v['ItemId'],array_column($extra['entry'],'ItemId'));
            });
            empty($entry) && throw new Exception("退款的产品与下单的产品不符合");
        }


        $SubAmt = array_sum(array_column($entry,'SubAmt'));
        $data['extra'] =[
            'SheetId' => $retail['Id']??'',
            'SellWay' => $retail['SellWay'],
            'BranchName' => $retail['BranchName']??'',
            'nuid' => $member['id']??'',
            'familyCode' => $member['family_code']??'',
            'username' => $member['username']??'',
            'mobile' => $member['mobile']??'',
            'ratio' => $ratio,
            'retail' => $retail,
            'entry' => $entry,
            'pays' => $pays,
            'SubAmt' =>  round( (FLOAT)$SubAmt ,4),
            'Amount' =>  round( (FLOAT)$SubAmt * $ratio ,4),
        ];

        $data['code'] = $retail['Id']??'';
        $data['family_code'] = $member['family_code']??'';
        $data['type'] = 'km';
        $data['way'] = ['A' => 0,'B' => 1][$retail['SellWay']]??0;

        // 创建单据
        [$state,$info,$res] = OpenService::OpenHelp()->create([
            'retail_id' => $retail['Id'],
            'out_trade_no' => $data['family_code']
        ]);
        if ($state==0)  throw new Exception($info);

        $data['transaction_id'] = $res['id'];

        $injectionInfo->setAttrs($data);

        return $injectionInfo;
    }

}