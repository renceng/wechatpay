<?php
namespace App\Controller;

use Common\Controller\BaseController;
/**
*微信异步回调
*/
public function notify(){
    Vendor('Weixinpay.Wechatpay');
    $wxpayandroid  = new \Wechatpay; //实例化微信支付类
    $verify_result = $wxpayandroid->verifyNotify();
    
    \Think\Log::write(json_encode($verify_result));
    if($verify_result['return_code']=='SUCCESS' && $verify_result['result_code']=='SUCCESS'){

        // 逻辑
        exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
    }else{
        exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[ERROR]]></return_msg></xml>');
    }
}
