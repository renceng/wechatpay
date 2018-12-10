<?php
namespace App\Controller;

use Common\Controller\AppBaseController;
/**
 * app 模拟支付
 */

class OrderController extends AppBaseController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function wx_pay()
    {
        Vendor('Weixinpay.Wechatpay');
        $wx_pay = new \Wechatpay();
        $body = '测试支付';
        $out_trade_no =$this->neworderNum();
        $total_fee = 1;
        $notify_url = "";
        $res = $wx_pay->getPrePayOrder($body,$out_trade_no,$total_fee,$notify_url);
    }
  
    public function neworderNum()
    {
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $orderSn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 999999));
        return $orderSn;
    }


}
