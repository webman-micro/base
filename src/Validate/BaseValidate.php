<?php

namespace WebmanMicro\Base;

use support\ErrorCode;
use taoser\Validate;

class BaseValidate extends Validate
{
    protected $message = [
        'data.updateEnsureOne' => '更新数据至少要存在一个字段数据',
        'filter.queryFilter' => '查询条件参数格式不合规',
        'order.queryOrder' => '查询排序参数格式不合规',
        'page.queryPage' => '查询分页数参数式不合规'
    ];

    /**
     * 更新数据至少存在一个字段数据验证规则
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected function updateEnsureOne($value, $rule, $data): bool
    {
        if (is_array($value) && count($value) >= 2) {
            // 因为update数据id为必填字段，所以更新字段数据必须大于等于2
            return true;
        }
        return false;
    }

    /**
     * 查询过滤条件验证
     * 仅支持一维数组，支持OR查询
     * @param $filter
     * @param $rule
     * @param $data
     * @return bool
     * @throws \exception
     */
    protected function queryFilter($filter, $rule, $data): bool
    {
        if (is_array($filter)) {
            foreach ($filter as $field => $conditionData) {

                if ($field === '_logic') {
                    if (is_string($conditionData) && in_array($conditionData, ['-or', '-and'])) {
                        continue;
                    } else {
                        throw_http_exception("The filtering condition query logic can only be -or or -and.", ErrorCode::ILLEGAL_QUERY);
                    }
                }

                if (count($conditionData) != 2) {
                    // 参数必须是两位长度
                    throw_http_exception("The format of {$field} field query criteria is incorrect.", ErrorCode::ILLEGAL_QUERY);
                }

                // 验证字段条件
                [$op, $condition] = $conditionData;

                // 验证字段数值
                switch ($op) {
                    case '-gt':
                    case '-egt':
                    case '-lt':
                    case '-elt':
                        // 查询参数必须为数字
                        if (!is_numeric($condition)) {
                            throw_http_exception("The {$field} field query value must be a number.", ErrorCode::ILLEGAL_QUERY);
                        }
                        break;
                    case '-lk':
                    case '-not-lk':
                    case '-eq':
                    case '-neq':
                    case '-find_in_set':
                        // 查询参数必须为数字或者字符串
                        if (!(is_numeric($condition) || is_string($condition))) {
                            throw_http_exception("The {$field} field query value must be a number or string.", ErrorCode::ILLEGAL_QUERY);
                        }
                        break;
                    case '-bw':
                    case '-not-bw':
                        // 查询参数必须为数组且长度等于2
                        if (!(is_array($condition) && count($condition) == 2)) {
                            throw_http_exception("The {$field} field query parameter must be an array with a length equal to 2.", ErrorCode::ILLEGAL_QUERY);
                        }
                        break;
                    case '-in':
                    case '-not-in':
                        // 查询参数必须为字符串且用逗号隔开
                        if (!(is_string($condition) && strpos($condition, ',') >= 0)) {
                            throw_http_exception("The {$field} field query parameters must be strings separated by commas.", ErrorCode::ILLEGAL_QUERY);
                        }
                        break;
                    default:
                        // 查询条件仅支持 -gt,-egt,-lt,-elt,-lk,-not-lk,-eq,-neq,-bw,-not-bw,-in,-not-in,-find_in_set
                        throw_http_exception("The query condition only support: -gt,-egt,-lt,-elt,-lk,-not-lk,-eq,-neq,-bw,-not-bw,-in,-not-in,-find_in_set.", ErrorCode::ILLEGAL_QUERY);
                        break;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 查询排序规则参数验证
     * @param $order
     * @param $rule
     * @param $data
     * @return bool
     * @throws \exception
     */
    protected function queryOrder($order, $rule, $data): bool
    {
        if (!is_array($order)) {
            throw_http_exception('The sorting parameter format must be [{"field": "xxx", "order": "asc"}], and the order parameter can only be asc or desc.', ErrorCode::ILLEGAL_QUERY);
        }

        if (count($order) > 5) {
            // 单次排序规则不能超过5个字段
            throw_http_exception('The single sorting rule cannot exceed 5 fields.', ErrorCode::ILLEGAL_QUERY);
        }

        if (!empty($order)) {
            foreach ($order as $condition) {
                if (!empty($condition['field']) && !empty($condition['order']) && in_array(strtolower($condition['order']), ['asc', 'desc'])) {
                    continue;
                } else {
                    throw_http_exception('The sorting parameter format must be [{"field": "xxx", "order": "asc"}], and the order parameter can only be asc or desc.', ErrorCode::ILLEGAL_QUERY);
                }
            }
        }

        return true;
    }

    /**
     * 查询分页参数验证
     * @param $page
     * @param $rule
     * @param $data
     * @return bool
     * @throws \exception
     */
    protected function queryPage($page, $rule, $data): bool
    {
        if (is_array($page) && count($page) === 2) {
            [$pageNum, $pageSize] = $page;
            if (is_numeric($pageNum) && is_numeric($pageSize)) {
                if ($pageSize > 1000) {
                    // 单次分页数据不能超过 1000 条
                    throw_http_exception('Single page data cannot exceed 1000 entries.', ErrorCode::ILLEGAL_QUERY);
                }
                return true;
            }
        }

        throw_http_exception('The page parameter format must be [page_number,page_size].', ErrorCode::ILLEGAL_QUERY);
    }
}
