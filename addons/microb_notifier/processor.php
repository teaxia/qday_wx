<?php
/**
 */
defined('IN_IA') or exit('Access Denied');

require 'inc/MicrobCore.class.php';
class Microb_NotifierModuleProcessor extends WeModuleProcessor {
    private $core = null;

    public function respond() {
        global $_W;
        if($this->message['event'] == 'merchant_order' && $this->message['orderstatus'] == '2') {
            $this->core = new MicrobCore($_W['account']);
            $this->submitNotify();
        }
        return '';
    }

    private function submitNotify() {
        $access = $this->core->fetchAccess();
        $pars = array();
        $pars['order_id'] = $this->message['orderid'];
        $url = "https://api.weixin.qq.com/merchant/order/getbyid?access_token={$access}";
        $content = ihttp_post($url, json_encode($pars));
        if(!is_error($content)) {
            $trade = @json_decode($content['content'], true);
            if(is_array($trade) && $trade['errcode'] == '40001') {
                $access = $this->core->fetchAccess(true);
                $url = "https://api.weixin.qq.com/merchant/order/getbyid?access_token={$access}";
                $content = ihttp_post($url, json_encode($pars));
                if(!is_error($content)) {
                    $trade = @json_decode($content['content'], true);
                }
            }
            if(is_array($trade) && $trade['errcode'] == '0') {
                $trade = $this->convertTrade($trade['order']);
                $ret = $this->core->submitNotify($this->message['from'], $trade);
                print_r($ret);
            }
        }
    }

    private function convertTrade($trade) {
        $t = array();
        $t['buyer'] = $trade['buyer_nick'];
        $t['details'] = array();
        $t['details'][] = array(
            'title'		=> $trade['product_name'],
            'price'		=> $trade['product_price'] / 100
        );
        return $t;
    }
}
