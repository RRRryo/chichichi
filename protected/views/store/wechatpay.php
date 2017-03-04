<?php
//ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once ROOTPATH."/lib/WxPay.Api.php";
require_once ROOTPATH."/lib/WxPay.NativePay.php";


$notify = new NativePay();

//模式二
/**
 * 流程：
 * 1、调用统一下单，取得code_url，生成二维码
 * 2、用户扫描二维码，进行支付
 * 3、支付完成之后，微信服务器会通知支付成功
 * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
 */
$input = new WxPayUnifiedOrder();
$input->SetBody("test");
$input->SetAttach("test");
$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
$input->SetTotal_fee("1");
$input->SetTime_start(date("YmdHis"));
//$input->SetTime_expire(date("YmdHis", strtotime("+30 minutes")));
$input->SetGoods_tag("test");
$input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
$input->SetTrade_type("NATIVE");
$input->SetProduct_id("123456789");
$result = $notify->GetPayUrl($input);
$url2 = $result["code_url"];

$this->renderPartial('/front/default-header', array(
	'h1' => t("Payment Option"),
	'sub_text' => t("choose your payment")
));



?>

<div class="center top30">
<img class="img-thumbnail center-block"  src="http://paysdk.weixin.qq.com/example/qrcode.php?data=<?php echo urlencode($url2);?>" style="width:240px;height:auto"/>
	<h1><span style="color: #F60">&yen;180.00</span></h1>

	<div class="bottom30">
		<img class="img-responsive center-block" style="max-width: 480px;"  src="<?php echo assetsURL()."/images/paymentLogo/wxPayText.png"?>">
	</div>

</div>
