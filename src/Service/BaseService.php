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
        foreach ($filter['param'] as $field => $conditionData) {
            [$op, $condition] = $conditionData;
            $expr = '';
            switch ($op) {
                case '-eq':
                    $expr = ' = ' . $condition;
                    break;
                case '-neq':
                    $expr = ' <> ' . $condition;
                    break;
                case '-gt':
                    $expr = ' > ' . $condition;
                    break;
                case '-egt':
                    $expr = ' >= ' . $condition;
                    break;
                case '-lt':
                    $expr = ' < ' . $condition;
                    break;
                case '-elt':
                    $expr = ' <= ' . $condition;
                    break;
                case '-lk':
                    $expr = ' LIKE "%' . $condition . '%"';
                    break;
                case '-not-lk':
                    $expr = ' NOT LIKE "%' . $condition . '%"';
                    break;
                case '-bw':
                    $expr = ' BETWEEN ' . $condition[0] . ' AND ' . $condition[1];
                    break;
                case '-not-bw':
                    $expr = ' NOT BETWEEN ' . $condition[0] . ' AND ' . $condition[1];
                    break;
                case '-in':
                    $expr = ' IN ' . join(',', $condition);
                    break;
                case '-not-in':
                    $expr = ' NOT IN ' . join(',', $condition);
                    break;
                case '-find_in_set':
                    $expr = Db::raw("FIND_IN_SET({$condition},{$field})");
                    break;
            }

            $queryFilter[$field] = $expr;
        }

        $engine = new \StringTemplate\Engine;
        $sqlStr = $engine->render($filter['rule'], $queryFilter);

        $db = $db->whereRaw($sqlStr);
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
