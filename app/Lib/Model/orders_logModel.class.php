<?php
/**
 * 订单记录操作
 */
class orders_logModel extends Model
{
	
    public function addLog($orderId, $orderStatus, $userId = 0,$logType =0) {
        if(empty($orderStatus)){
        	return;
        }
        $data['orderId'] = $orderId;
        $data['logTime'] = time();
        $data['logUserId'] = $userId;
        $data['logType'] = $logType;
        if($orderStatus == 1){
        	$data['logContent'] = '下单成功';
        }elseif($orderStatus == 2){
        	$data['logContent'] = '支付成功';
        }elseif($orderStatus == 3){
        	$data['logContent'] = '商家已受理订单';
        }elseif($orderStatus == 4){
        	$data['logContent'] = '商家已发货';
        }
        $this->add($data);
    }
}