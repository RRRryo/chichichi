<?php
//ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once ROOTPATH."/lib/WxPay.Api.php";
require_once ROOTPATH."/lib/WxPay.NativePay.php";



/**************************get functional params **************************/


//商户网站订单系统中唯一订单号，必填
$out_trade_no = $_POST['id'];

//订单名称 必填
$subject = '近味私厨';

//付款金额 必填
$total_fee = '';

//订单描述
$body = 'jinwei';
//商品展示地址
$show_url = '';//$_POST['WIDshow_url'];
//需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html

//防钓鱼时间戳
$anti_phishing_key = "";
//若要使用请调用类文件submit中的query_timestamp函数

//客户端的IP地址
$exter_invoke_ip = "";
//非局域网的外网IP地址，如：221.0.0.1

$merchant_id='';

$ok=false;

if ( $data=Yii::app()->functions->getOrder($out_trade_no)){
	$merchant_id=$data['merchant_id'];
	$json_details=!empty($data['json_details'])?json_decode($data['json_details'],true):false;

	if ( $json_details !=false){
		$p_arams=array(
			'merchant_id'=>$data['merchant_id'],
			'delivery_type'=>$data['trans_type']
		);
		Yii::app()->functions->displayOrderHTML($p_arams,$json_details,true);
		if ( Yii::app()->functions->code==1){
			$ok=true;
		}
	}
}
if ( $ok==TRUE){
	$data2=Yii::app()->functions->details['raw'];
	$euro_amount=isIsset(  normalPrettyPrice($data2['total']['total']) );
	$exchange_rate= Yii::app()->functions->getOptionAdmin('rmb_amount');
	$cny_amount = $euro_amount * $exchange_rate;
	$total_fee=prettyFormat($cny_amount,$merchant_id);

}

	$total_fee=0.01;







/**************************WX pay Core code**************************/

$notify = new NativePay();


/**
 * 流程：
 * 1、调用统一下单，取得code_url，生成二维码
 * 2、用户扫描二维码，进行支付
 * 3、支付完成之后，微信服务器会通知支付成功
 * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
 */
$input = new WxPayUnifiedOrder();
$input->SetBody($body);
$input->SetAttach("attach");
$input->SetOut_trade_no($out_trade_no);
$input->SetTotal_fee($total_fee);
$input->SetTime_start(date("YmdHis"));
//$input->SetTime_expire(date("YmdHis", strtotime("+30 minutes")));
$input->SetGoods_tag("good_tags");
$input->SetNotify_url("http://www.jinwei.info/wxpaycheckout");
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
	<h1><span style="color: #F60">&yen;<?=$total_fee?></span></h1>

	<div class="bottom30">
		<img class="img-responsive center-block" style="max-width: 480px;"  src="<?php echo assetsURL()."/images/paymentLogo/wxPayText.png"?>">
	</div>

</div>
