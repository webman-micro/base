<?php
/**
 * Here is your custom functions.
 */
// 响应RPC数据
if (!function_exists('response_rpc_json')) {
    /**
     * @desc: 响应RPC数据
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return false|string
     * @author Tinywan(ShaoBo Wan)
     */
    function response_rpc_json(int $code = 0, string $msg = 'success', array $data = []): bool|string
    {
        //return json_encode(['code' => $code, 'msg' => $msg, 'data' => $data],JSON_UNESCAPED_UNICODE);
        return MessagePack\MessagePack::pack(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }
}


/**
 * Here is your custom functions.
 */
/**
 * Here is your custom functions.
 */
if (!function_exists('success_response')) {
    /**
     * 成功返回数据
     * @param string $message
     * @param array $data
     * @param int $status
     * @return array
     */
    function success_response(string $message = '', array $data = [], int $status = 0): array
    {
        return ["code" => $status, "msg" => $message, "data" => $data, 'APP_VERSION' => '1.0'];
    }
}


if (!function_exists('throw_http_exception')) {
    /**
     * 抛出http请求异常异常
     * @param $msg
     * @param int $code
     * @param array $data
     * @throws exception
     */
    function throw_http_exception($msg, int $code = -400000, array $data = [])
    {
        $errorData = [
            "code" => $code,
            "msg" => $msg,
            "data" => $data
        ];
        throw new \app\exception\HttpResponseException(json_encode($errorData, JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('create_pass')) {
    /**
     * 加密密码
     * @param string $password
     * @return bool|string
     */
    function create_pass(string $password = ""): bool|string
    {
        if (!empty($password)) {
            $options = [
                'cost' => 4,
            ];
            return password_hash($password, PASSWORD_BCRYPT, $options);
        } else {
            return '';
        }
    }
}

if (!function_exists('camelize')) {
    /**
     *  下划线转驼峰
     * 思路:
     * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
     * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
     * @param $unCamelizeWords
     * @param string $separator
     * @return string
     */
    function camelize($unCamelizeWords, string $separator = '_'): string
    {
        $unCamelizeWords = $separator . str_replace($separator, " ", strtolower($unCamelizeWords));
        return str_replace(" ", "", ucwords(ltrim($unCamelizeWords, $separator)));
    }
}

if (!function_exists('create_sms_code')) {
    /**
     * 生成短信验证码
     * @param int $length
     * @return int
     */
    function create_sms_code(int $length = 6): int
    {
        $min = pow(10, ($length - 1));
        $max = pow(10, $length) - 1;
        return rand($min, $max);
    }
}
