<?php


namespace invoice\payment;


use Yii;
use yii\web\Controller;

abstract class AbstractNotificationController extends Controller
{
    abstract function onPay($orderId, $amount);
    abstract function onFail($orderId);
    abstract function onRefund($orderId);

    public function actionNotify() {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        /** @var InvoiceConfig $config */
        $config = Yii::$app->get('invoice', false);


        $key = $config->api_key;

        if($notification == null or empty($notification)) return json_encode(["result" => "error"]);
        $type = $notification["notification_type"];
        $id = $notification["order"]["id"];

        if(!isset($notification['status'])) return json_encode(["result" => "error"]);
        if($notification['signature'] != $this->getSignature($notification['id'], $notification["status"], $key))
            return json_encode(["result" => "wrong signature"]);

        if($type == "pay") {
            switch ($notification['status']) {
                case "successful":
                    $this->onPay($id, $notification['order']['amount']);
                    break;
                case "failed":
                    $this->onFail($id);
                    break;
            }
        }

        if($type == "refund") {
            $this->onRefund($id);
        }

        return json_encode(["result" => "ok"]);
    }

    private function getSignature($id, $status, $key) {
        return md5($id.$status.$key);
    }

}