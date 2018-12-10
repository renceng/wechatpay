<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 定义时区
ini_set('date.timezone', 'Asia/Shanghai');

class Wechatpay
{
    /*
    配置参数
     */
    private $config = array(
        'appid'   => "", /*微信开放平台上的应用id*/
        'mch_id'  => "", /*微信申请成功之后邮件中的商户id*/
        'api_key' => "", /*在微信商户平台上自己设定的api密钥 32位*/
    );

    public function getPrePayOrder($body, $out_trade_no, $total_fee, $notify_url)
    {
        $dataArr = array(
            'appid'            => $this->config['appid'],
            'mch_id'           => $this->config['mch_id'],
            'nonce_str'        => $this->getNonceStr(),
            'body'             => $body,
            'attach'           => 'text1208',
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $total_fee,
            'spbill_create_ip' => get_client_ip(),
            'notify_url'       => $notify_url,
            'trade_type'       => 'APP',
        );
        $sign            = $this->MakeSign($dataArr); //签名生成
        $dataArr['sign'] = $sign;
        $xmlStr          = $this->createXML('xml', $dataArr); //统一下单xml数据生成

        $reArr = explode('?>', $xmlStr);
        $reArr = end($reArr);
        $xml   = $this->curl('https://api.mch.weixin.qq.com/pay/unifiedorder', $reArr); //发送请求 统一下单数据
        //解析返回的xml字符串
        $re = $this->xmlToObject($xml);
        //判断统一下单是否成功
        if ($re['result_code'] == 'SUCCESS') {
            //支付请求数据
            $payData = array(
                'appid'     => $re['appid'],
                'partnerid' => $re['mch_id'],
                'prepayid'  => $re['prepay_id'],
                'noncestr'  => $this->getNonceStr(),
                'package'   => 'Sign=WXPay',
                'timestamp' => time(),
            );
            //生成支付请求的签名
            $paySign         = $this->MakeSign($payData);
            $payData['sign'] = $paySign;
            //拼接成APICLOUD所需要支付数据请求
            $payDatas = array(
                'apiKey'    => $re['appid'],
                'orderId'   => $re['prepay_id'],
                'mchId'     => $re['mch_id'],
                'nonceStr'  => $payData['noncestr'],
                'package'   => 'Sign=WXPay',
                'timeStamp' => $payData['timestamp'],
                'sign'      => $paySign,
            );
            echo json_encode($payDatas);
        } else {
            $re['payData'] = "error";
            echo json_encode($re);
        }

    }

    //转XML格式
    public function createXML($rootNode, $arr)
    {
        //创建一个文档，文档时xml的，版本号为1.0，编码格式utf-8
        $xmlObj = new \DOMDocument('1.0', 'UTF-8');
        //创建根节点
        $Node = $xmlObj->createElement($rootNode);
        //把创建好的节点加到文档中
        $root = $xmlObj->appendChild($Node);
        //开始把数组中的数据加入文档
        foreach ($arr as $key => $value) {
            //如果是$value是一个数组
            if (is_array($value)) {
                //先创建一个节点
                $childNode = $xmlObj->createElement($key);
                //将节点添加到$root中
                $root->appendChild($childNode);
                //循环添加数据
                foreach ($value as $key2 => $val2) {
                    //创建节点的同时添加数据
                    $childNode2 = $xmlObj->createElement($key2, $val2);
                    //将节点添加到$childNode
                    $childNode->appendChild($childNode2);
                }
            } else {
                //创建一个节点，根据键和值
                $childNode = $xmlObj->createElement($key, $value);
                //把节点加到根节点
                $root->appendChild($childNode);
            }
        }
        //把创建好的xml保存到本地
        $xmlObj->save('xml/log.xml');
        $str = $xmlObj->saveXML();
//        echo $str;
        //返回xml字符串
        return $str;
    }

//封装签名算法
    public function MakeSign($arr)
    {
        //签名步骤一：按字典序排序参数
        ksort($arr);
        $string = $this->ToUrlParams($arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->config['api_key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

/**
 * 格式化参数格式化成url参数
 */
    public function ToUrlParams($arr)
    {
        $buff = "";
        foreach ($arr as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

//随机字符串(不长于32位)
    public function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function curl($url, $post_data)
    {

        $headerArray = array(
            'Accept:application/json, text/javascript, */*',
            'Content-Type:application/x-www-form-urlencoded',
            'Referer:https://mp.weixin.qq.com/',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //关闭直接输出
        curl_setopt($ch, CURLOPT_POST, 1); //使用post提交数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); //设置 post提交的数据
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69 Safari/537.36'); //设置用户代理
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray); //设置头信息

        $loginData = curl_exec($ch); //这里会返回token，需要处理一下。

        return $loginData;

        $token = array_pop($token);
        curl_close($ch);

    }

    public function getVerifySign($data, $key)
    {
        $String = $this->formatParameters($data, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($String);
        return $result;
    }
    public function formatParameters($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($k == "sign") {
                continue;
            }
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

/**
 * 解析xml文档，转化为对象
 * @param  String $xmlStr xml文档
 * @return Object         返回Obj对象
 */
    public function xmlToObject($xmlStr)
    {
        if (!is_string($xmlStr) || empty($xmlStr)) {
            return false;
        }
        // 由于解析xml的时候，即使被解析的变量为空，依然不会报错，会返回一个空的对象，所以，我们这里做了处理，当被解析的变量不是字符串，或者该变量为空，直接返回false
        libxml_disable_entity_loader(true);
        $postObj = json_decode(json_encode(simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        //将xml数据转换成对象返回
        return $postObj;
    }

    /**
     * XML转数组
     * @param unknown $xml
     * @return mixed
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     * 异步通知信息验证
     * @return boolean|mixed
     */
    public function verifyNotify()
    {
        $xml = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
        if (!$xml) {
            return false;
        }
        $wx_back = $this->xmlToArray($xml);
        if (empty($wx_back)) {
            return false;
        }
        $checkSign = $this->getVerifySign($wx_back, $this->config['api_key']);
        if ($checkSign = $wx_back['sign']) {
            return $wx_back;
        } else {
            return false;
        }
    }

}
