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

namespace think\admin\contract;


/**
 * 通用短信接口类
 * @class MessageInterface
 * @package think\admin\contract
 */
interface MessageInterface
{
    /**
     * 初始化短信通道
     * @return static
     * @throws \think\admin\Exception
     */
    public function init(array $config = []): MessageInterface;

    /**
     * 发送短信内容
     * @param string $code 短信模板CODE| 短信内容
     * @param string $mobile 接收手机号码
     * @param array $params 短信模板变量
     * @param array $options 其他配置参数
     * @return array
     * @throws \think\admin\Exception
     */
    public function send(string $code, string $mobile, array $params = [], array $options = []): array;

    /**
     * 发送短信验证码
     * @param string $scene 业务场景
     * @param string $mobile 手机号码
     * @param array $params 模板变量
     * @param array $options 其他配置
     * @return array
     */
    public function verify(string $scene, string $mobile, array $params = [], array $options = []): array;

    /**
     * 获取区域配置
     * @return array
     */
    public static function regions(): array;
}