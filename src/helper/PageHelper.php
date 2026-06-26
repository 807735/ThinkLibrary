<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ 7dcs.com ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\helper;

use app\admin\model\BaseExportFile;
use app\admin\model\SystemExportFile;
use think\admin\Helper;
use think\admin\Library;
use think\admin\service\AdminService;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\exception\HttpResponseException;
use think\Model;

/**
 * 列表处理管理器.
 * @class PageHelper
 */
class PageHelper extends Helper
{
    /**
     * 逻辑器初始化.
     * @param BaseQuery|Model|string $dbQuery
     * @param bool|int $page 是否分页或指定分页
     * @param bool $display 是否渲染模板
     * @param bool|int $total 集合分页记录数
     * @param int $limit 集合每页记录数
     * @param string $template 模板文件名称
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function init($dbQuery, $page = true, bool $display = true, $total = false, int $limit = 0, string $template = ''): array
    {

        $query = $this->autoSortQuery($dbQuery);
        if ($page !== false) {
            $get = $this->app->request->get();
            $limits = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200];
            if ($limit <= 1) {
                $limit = $get['limit'] ?? $this->app->cookie->get('limit', 20);
                if (in_array($limit, $limits) && ($get['not_cache_limit'] ?? 0) < 1) {
                    $this->app->cookie->set('limit', ($limit = intval($limit >= 5 ? $limit : 20)) . '');
                }
            }
            $inner = strpos($get['spm'] ?? '', 'm-') === 0;
            $prefix = $inner ? (sysuri('admin/index/index') . '#') : '';
            // 生成分页数据
            $config = ['list_rows' => $limit, 'query' => $get];
            if (is_numeric($page)) {
                $config['page'] = $page;
            }
            $data = ($paginate = $query->paginate($config, $this->getCount($query, $total)))->toArray();
            $result = ['page' => ['limit' => $data['per_page'], 'total' => $data['total'], 'pages' => $data['last_page'], 'current' => $data['current_page']], 'list' => $data['data']];
            // 分页跳转参数
            $select = "<select onchange='location.href=this.options[this.selectedIndex].value'>";
            if (in_array($limit, $limits)) {
                foreach ($limits as $num) {
                    $get = array_merge($get, ['limit' => $num, 'page' => 1]);
                    $url = $this->app->request->baseUrl() . '?' . http_build_query($get, '', '&', PHP_QUERY_RFC3986);
                    $select .= sprintf('<option data-num="%d" value="%s" %s>%d</option>', $num, $prefix . $url, $limit === $num ? 'selected' : '', $num);
                }
            } else {
                $select .= "<option selected>{$limit}</option>";
            }
            $html = lang('共 %s 条记录，每页显示 %s 条，共 %s 页当前显示第 %s 页。', [$data['total'], "{$select}</select>", $data['last_page'], $data['current_page']]);
            $link = $inner ? str_replace('<a href="', '<a data-open="' . $prefix, $paginate->render() ?: '') : ($paginate->render() ?: '');
            $this->class->assign('pagehtml', "<div class='pagination-container nowrap'><span>{$html}</span>{$link}</div>");
        } else {
            $result = ['list' => $query->select()->toArray()];
        }

        if ($this->class->callback('_page_filter', $result['list'], $result) !== false && $display) {
            if ($this->output === 'get.json') {
                $this->class->success('JSON-DATA', $result);
            } else {
                $this->class->fetch($template, $result);
            }
        }
        return $result;
    }

    /**
     * 组件 Layui.Table 处理.
     * @param BaseQuery|Model|string $dbQuery
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function layTable($dbQuery, string $template = ''): array
    {
        if ($this->output === 'get.json') {
            $get = $this->app->request->get();
            $query = static::buildQuery($dbQuery);
            // 根据参数排序
            if (isset($get['_field_'], $get['_order_'])) {
                $dbQuery->order("{$get['_field_']} {$get['_order_']}");
            }
            return PageHelper::instance()->init($query);
        }

        if ($this->output === 'get.export') {
            $fileparam = json_decode(input('get.__parameter'),true);
            if (empty($fileparam))  throw new HttpResponseException(json(['code' => 0,'info' => '参数错误','data' => []]));
            // 生成数据
            $code = SystemExportFile::mk()->getCode();
            $db = SystemExportFile::mk();
            $db->save([
                'code' => $code,
                'fileparam' => input('get.__parameter'),
                'filename' => input('get.__filename'),
                'table_name' => $dbQuery->getTable(),
                'model_class' => get_class($dbQuery->getModel()),
                'controller_class' => get_class($this->class),
                'controller_action' => $this->class->request->action(),
                'model_options' => array_merge($dbQuery->getOptions(), [
                    '_export_get' => $this->filterExportGet($this->app->request->get()),
                ]),
                'site_id' => sysvar('api_site_id'),
                'user_id' => session('user.id'),
                'username' => session('user.username'),
                'login_ip' => $this->app->request->ip(),
                'user_agent' => $this->app->request->server('HTTP_USER_AGENT'),
            ]);
            // 创建打包异步任务
            $queueCode = sysqueue(
                "【{$code}】数据自动打包任务",           // 任务标题
                'data:export:pack',            // 执行命令
                0,                        // 延时时间
                ['code' => $code],        // 任务数据
                1,                        // 允许重复创建
                0                         // 是否循环执行
            );
            $db->where(['code' => $code])->save(['queue_code' =>  $queueCode]);
            throw new HttpResponseException(json(['code' => 1,'info' => '打包任务已创建，请耐心等待','data' => []]));

        }

        if ($this->output === 'get.layui.table') {
            $get = $this->app->request->get();
            $query = $this->autoSortQuery($dbQuery);
            // 根据参数排序
            if (isset($get['_field_'], $get['_order_'])) {
                $query->order("{$get['_field_']} {$get['_order_']}");
            }
            // 数据分页处理
            if (empty($get['page']) || empty($get['limit'])) {
                $data = $query->select()->toArray();
                $result = ['msg' => '', 'code' => 0, 'count' => count($data), 'data' => $data];
            } else {
                $cfg = ['list_rows' => $get['limit'], 'query' => $get];
                $data = $query->paginate($cfg, static::getCount($query))->toArray();
                $result = ['msg' => '', 'code' => 0, 'count' => $data['total'], 'data' => $data['data']];
            }
            if ($this->class->callback('_page_filter', $result['data'], $result) !== false) {
                static::xssFilter($result['data']);
                throw new HttpResponseException(json($result));
            }
            return $result;
        }
        $this->class->fetch($template);
        return [];
    }

    /**
     * 绑定排序并返回操作对象
     * @param BaseQuery|Model|string $dbQuery
     * @param string $field 指定排序字段
     */
    public function autoSortQuery($dbQuery, string $field = 'sort'): Query
    {
        $query = static::buildQuery($dbQuery);
        if ($this->app->request->isPost() && $this->app->request->post('action') === 'sort') {
            AdminService::isLogin() or $this->class->error('请重新登录！');
            if (method_exists($query, 'getTableFields') && in_array($field, $query->getTableFields())) {
                if ($this->app->request->has($pk = $query->getPk() ?: 'id', 'post')) {
                    $map = [$pk => $this->app->request->post($pk, 0)];
                    $data = [$field => intval($this->app->request->post($field, 0))];
                    try {
                        $query->newQuery()->where($map)->update($data);
                    } catch (\Throwable $exception) {
                        $this->class->error('列表排序失败！');
                    }
                    $this->class->success('列表排序成功！', '');
                }
            }
            $this->class->error('列表排序失败！');
        }
        return $query;
    }

    /**
     * 输出 XSS 过滤处理.
     */
    private static function xssFilter(array &$items)
    {
        foreach ($items as &$item) {
            if (is_array($item)) {
                static::xssFilter($item);
            } elseif (is_string($item)) {
                $item = htmlspecialchars($item, ENT_QUOTES);
            }
        }
    }

    /**
     * 过滤导出任务需要回放的搜索参数
     * @param array $get
     * @return array
     */
    protected function filterExportGet(array $get): array
    {
        foreach (['output', 'export', '__parameter', '__filename', 'spm', 'not_cache_limit', 'page', 'limit', '_field_', '_order_'] as $key) {
            unset($get[$key]);
        }
        return $get;
    }

    /**
     * 查询对象数量统计
     * @param BaseQuery|Query $query
     * @param bool|int $total
     * @return bool|int|string
     * @throws DbException
     */
    private static function getCount($query, $total = false)
    {
        if ($total === true || is_numeric($total)) {
            return $total;
        }
        [$query, $options] = [clone $query, $query->getOptions()];
        if (isset($options['order'])) {
            $query->removeOption('order');
        }
        Library::$sapp->db->trigger('think_before_page_count', $query);
        if (empty($options['union'])) {
            return $query->count();
        }
        $table = [$query->buildSql() => '_union_count_'];
        return $query->newQuery()->table($table)->count();
    }
}
