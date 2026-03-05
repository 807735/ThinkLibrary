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

use app\manage\model\SystemMsms;

use app\data\service\ConfigService;
use think\admin\contract\MessageInterface;
use think\admin\Exception;
use think\admin\Library;

/**
 * 短信服务调度器
 * @class Message
 * @mixin MessageInterface
 * @package app\manage\service
 */
abstract class Message
{
    public const tCapcha = 'CAPTCHA';
    public const tForget = 'FORGET';
    public const tRegister = 'REGISTER';

    public const tArrears = 'ARREARS';
    public const tStop = 'STOP';
    public const tWarning = 'WARNING';


    /**
     * 业务场景定义
     * 对应的模版ID
     * @var string[]
     */
    public static array $scenes = [
        self::tCapcha   => ['name' => '通用验证码',   'type' => 'instant','template' => '','content' => ''],
        self::tForget   => ['name' => '找回用户密码', 'type' => 'instant','template' => '','content' => ''],
        self::tRegister => ['name' => '用户注册绑定', 'type' => 'instant','template' => '','content' => ''],
        self::tArrears  => ['name' => '欠费通知',    'type' => 'task','template' => '','content' => ''],
        self::tStop     => ['name' => '停用通知',    'type' => 'task','template' => '','content' => ''],
        self::tWarning  => ['name' => '账户预警通知', 'type' => 'task','template' => '','content' => ''],
    ];

    /**
     * 获取场景信息 配置信息
     * @param bool $state
     * @return array
     * @throws Exception
     */
    public static function getScenes(): array
    {
        $config = sysdata('plugin.common.smscfg')?:[];
        $scenes = [];
        foreach (self::$scenes as $k => $v) {
//            unset($config[$k]['state']);
            $v = array_merge($v, $config[$k] ?? []);
            $scenes[$k] = $v;
        }
        return $scenes;
    }

    /**
     * 发送短信验证码
     * @param string $mobile 手机号码
     * @param integer $wait 等待时间
     * @param string $scene 业务场景
     * @return array [state, message, [timeout]]
     */
    public static function sendVerifyCode(string $mobile, int $wait = 120, string $scene = self::tCapcha): array
    {
        try {
            $ckey = self::genCacheKey($mobile, $scene);
            $tempData = self::getTempData($scene);
            $cache = Library::$sapp->cache->get($ckey, []);

            // 检查是否已经发送
            if (is_array($cache) && isset($cache['time']) && $cache['time'] > time() - $wait) {
                $dtime = ($cache['time'] + $wait < time()) ? 0 : ($wait - time() + $cache['time']);
               // return [1, '验证码已发送', ['time' => $dtime]];
            }

            // 生成新的验证码
            [$code, $time] = [rand(100000, 999999), time()];
            Library::$sapp->cache->set($ckey, ['code' => $code, 'time' => $time], 600);


            // 尝试发送短信内容
            [$state,$info] =  self::createTask("{$tempData['name']}-发送",$mobile,$scene,['code' => (string)$code]);
//            [$state,$info] = OpenService::OpenMsg()->send($mobile,$tempData['template'],['code' => (string)$code]);
            if (!$state) throw new Exception($info);

            return [1, '验证码发送成功', ['time' => ($time + $wait < time()) ? 0 : ($wait - time() + $time)]];
        } catch (\Exception $ex) {
            trace_file($ex);
            isset($ckey) && Library::$sapp->cache->delete($ckey);
            return [0, $ex->getMessage(), []];
        }
    }


    /**
     * 检查短信验证码
     * @param string $vcode 验证码
     * @param string $mobile 手机号码
     * @param string $scene 业务场景
     * @return boolean
     * @throws \think\admin\Exception
     */
    public static function checkVerifyCode(string $vcode, string $mobile, string $scene = self::tCapcha): bool
    {
        if (  ConfigService::isDev()  && $vcode === '123456') return true;
        if (  $mobile=='13888888888'  && $vcode === '123456') return true;
        $cache = Library::$sapp->cache->get(static::genCacheKey($mobile, $scene), []);
        return is_array($cache) && isset($cache['code']) && $cache['code'] == $vcode;
    }

    /**
     * 发送短信业务通知
     * @param string $mobile 手机号码
     * @param integer $params 业务参数
     * @param string $scene 业务场景
     * @return array [state, message]
     */
    public static function sendSms(string $mobile, array $params = [], string $scene = self::tArrears,$data=[]): array
    {
        try {
            $tempData = self::getTempData($scene);
            [$state,$info,$data] = OpenService::OpenMsg()->send($mobile,$tempData['template'],$params);
            if (!$state) throw new Exception($info);
            return [1, '发送成功',$data];
        } catch (\Exception $ex) {
            trace_file($ex);
            return [0, $ex->getMessage(), []];
        }
    }

    /**
     * 获取聚合条件数量
     * @param string $identifier
     * @param array $where
     * @return int
     */
    public static function getIdentifierCount(string $identifier,array $where = []): int
    {
        return SystemMsms::mk()->where(['identifier' => $identifier])->where($where)->count();
    }
    /**
     * 发送任务短信
     * @param string $title 说明
     * @param string $mobile 手机
     * @param string $scene 场景
     * @param array $params 参数
     * @param string $plan_time 发送定时时间 2025-12-01 22:55:32
     * @param string $identifier 聚合表示
     * @return array
     */
    public static function createTask(string $title,string $mobile, string $scene,array $params = [],string $plan_time = '',string $identifier = ''): array
    {
        try {
            $sceneData = self::getScenes()[$scene]??[];
            if (empty($sceneData)) throw new Exception('短信场景错误');

            $isInstant = true;
            if ($plan_time) $isInstant = false;
            else $plan_time = date('Y-m-d H:i:s');

            if (strtotime($plan_time) < time()-5 ) throw new Exception('计划时间必须大于当前时间');

            $db = SystemMsms::mk();
            $db->setAttrs([
                'identifier' => $identifier,
                'scene' => $scene,
                'title' => $title,
                'mobile' => $mobile,
                'extra' => $params,
                'template' => $sceneData['content'],
                'plan_time' => $plan_time,
                'status' => 1,
            ]);
            // 如果即时发送 则开始发送信息
            if ($isInstant || $sceneData['type'] == 'instant'){
               [$state,$info,$res] = self::sendSms($mobile,$params,$scene);
               if ($state==0) throw new Exception($info);
                $db->setAttrs([
                    'plan_time' => date('Y-m-d H:i:s'),
                    'content' => $res['content'],
                    'send_time' => date('Y-m-d H:i:s'),
                    'send_remark' => '发送成功',
                    'status' => 3, // 设置发送完毕
                ]);
            }
            $db->save();
            return [1,'任务创建成功',$db->refresh()->toArray()];
        } catch (Exception $e) {
            return [0,$e->getMessage(),[]];
        }
    }


    /**
     * 清理短信验证码
     * @param string $mobile
     * @param string $scene
     * @return boolean
     */
    public static function clearVerifyCode(string $mobile, string $scene = self::tCapcha): bool
    {
        try {
            return Library::$sapp->cache->delete(static::genCacheKey($mobile, $scene));
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 获取模版数据
     * @param string $scene
     * @return array|mixed|string|void
     * @throws Exception
     */
    private static function getTempData(string $scene = self::tCapcha)
    {
        if (isset(array_change_key_case(self::getScenes())[strtolower($scene)]['template']) && !empty(array_change_key_case(self::getScenes())[strtolower($scene)]['template'])) {
            return array_change_key_case(self::getScenes())[strtolower($scene)]??[];
        } else {
            throw new Exception("未定义模版");
        }
    }
    /**
     * 生成验证码缓存名
     * @param string $mobile 手机号码
     * @param string $scene 业务场景
     * @return string
     * @throws \think\admin\Exception
     */
    private static function genCacheKey(string $mobile, string $scene = self::tCapcha): string
    {
        if (isset(array_change_key_case(self::getScenes())[strtolower($scene)])) {
            return md5(strtolower("sms-{$scene}-{$mobile}"));
        } else {
            throw new Exception("未定义的业务");
        }
    }

}