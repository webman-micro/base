<?php

namespace WebmanMicro\Base\Service;

use think\facade\Db;
use WebmanMicro\Base\support\ErrorCode;

class BaseService
{
    /**
     * 判断记录是否存在通过
     * @param $tableName
     * @param $filter
     * @param string $checkType
     * @return void
     * @throws \exception
     */
    protected function verifyDataWhetherExist($tableName, $filter, string $checkType = 'exist'): void
    {
        $options = Db::table($tableName)->where($filter)->findOrEmpty();

        if ($checkType == 'exist') {
            if (!empty($options)) {
                // 当前数据存在
                throw_http_exception('Data exists', ErrorCode::DATA_EXISTS);
            }
        } else {
            // 不存在记录
            if (empty($options)) {
                throw_http_exception('No records exist', ErrorCode::NO_RECORDS_EXIST);
            }
        }
    }

    /**
     * 判断记录是否唯一
     * @param $tableName
     * @param $id
     * @param $uniqueFields
     * @param $data
     * @return void
     * @throws \exception
     */
    protected function verifyDataWhetherUnique($tableName, $id, $uniqueFields, $data): void
    {
        $uniqueFieldArr = explode(',', $uniqueFields);
        $model = Db::table($tableName);
        $existValue = false;
        foreach ($uniqueFieldArr as $uniqueField) {
            if (!empty($data[$uniqueField])) {
                $model = $model->where($uniqueField, '=', $data[$uniqueField]);
                $existValue = true;
            }
        }

        if ($existValue) {
            $optionsCode = $model->field('id')->find();
            if (!empty($optionsCode) && $optionsCode['id'] != $id) {
                // 唯一记录已经存在
                throw_http_exception("The {$uniqueFields} already exists", ErrorCode::CODE_ALREADY_EXISTS);
            }
        }

    }

    /**
     * 生成DB查询条件
     * @param $db
     * @param $filter
     * @return void
     */
    protected function generateDbFilter(&$db, $filter): void
    {
        $queryFilter = [];
        foreach ($filter as $field => $conditionData) {
            if ($field === '_logic') {
                continue;
            }
            [$op, $condition] = $conditionData;
            switch ($op) {
                case '-eq':
                    $queryFilter[] = [$field, '=', $condition];
                    break;
                case '-neq':
                    $queryFilter[] = [$field, '<>', $condition];
                    break;
                case '-gt':
                    $queryFilter[] = [$field, '>', $condition];
                    break;
                case '-egt':
                    $queryFilter[] = [$field, '>=', $condition];
                    break;
                case '-lt':
                    $queryFilter[] = [$field, '<', $condition];
                    break;
                case '-elt':
                    $queryFilter[] = [$field, '<=', $condition];
                    break;
                case '-lk':
                    $queryFilter[] = [$field, 'LIKE', "%{$condition}%"];
                    break;
                case '-not-lk':
                    $queryFilter[] = [$field, 'NOT LIKE', "%{$condition}%"];
                    break;
                case '-bw':
                    $queryFilter[] = [$field, 'BETWEEN', $condition];
                    break;
                case '-not-bw':
                    $queryFilter[] = [$field, 'NOT BETWEEN', $condition];
                    break;
                case '-in':
                    $queryFilter[] = [$field, 'IN', $condition];
                    break;
                case '-not-in':
                    $queryFilter[] = [$field, 'NOT IN', $condition];
                    break;
                case '-find_in_set':
                    $queryFilter[] = ['', 'exp', Db::raw("FIND_IN_SET({$condition},{$field})")];
                    break;
            }
        }

        if (array_key_exists('_logic', $filter) && $filter['_logic'] === '-or') {
            $db = $db->whereOr($queryFilter);
        } else {
            $db = $db->where($queryFilter);
        }
    }

    /**
     * 生成DB过滤条件
     * @param $db
     * @param $order
     * @return void
     */
    protected function generateDbOrder(&$db, $order): void
    {
        if (!empty($order)) {
            foreach ($order as $condition) {
                $db->order($condition['field'], $condition['order']);
            }
        }
    }

    /**
     * 组装过滤DB
     * @param $tableName
     * @param $fields
     * @param $filter
     * @param array $order
     * @return mixed
     * @throws \exception
     */
    protected function assembleFilterSelectDb($tableName, $fields, $filter, array $order = []): mixed
    {
        //4.生成DB查询
        $modelName = 'app\\model\\' . camelize($tableName) . 'Model';
        $model = new $modelName;

        if (!empty($fields)) {
            $model = $model->field($fields);
        }

        //4.1生成DB过滤条件
        $this->generateDbFilter($model, $filter);

        //4.2生成DB排序
        $this->generateDbOrder($model, $order);

        return $model;
    }
}
