
	<?php
	$this->renderPartial('/front/default-header', array(
		'h1' => t("Payment Option"),
		'sub_text' => t("choose your payment")
	));


	/* *
     * 功能：即时到账交易接口接入页
     * 版本：3.3
     * 修改日期：2012-07-23
     * 说明：
     * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
     * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

     *************************注意*************************
     * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
     * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
     * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
     * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
     * 如果不想使用扩展功能请把扩展功能参数赋空值。
     */

	require_once(ROOTPATH . '/vendor/autoload.php');
	require_once(ROOTPATH . "/lib/alipay_submit.class.php");
	require_once(ROOTPATH . "/protected/config/alipay_config.php");


	/**************************请求参数**************************/

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

//	$total_fee=0.01;

	/************************************************************/

	$detector = new Detection\MobileDetect();
	$is_mobile = $detector->isMobile();
	//建立请求
	$alipay = new mytharcher\sdk\alipay\Alipay($alipay_config, $is_mobile);

	if ($is_mobile) {

//构造要请求的参数数组，无需改动
		$parameter = array(
			"service"       => $alipay_config['service'],
			"partner"       => $alipay_config['partner'],
			"seller_id"  => $alipay_config['seller_id'],
			"payment_type"	=> $alipay_config['payment_type'],
			"notify_url"	=> $alipay_config['notify_url'],
			"return_url"	=> $alipay_config['return_url'],
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
			"out_trade_no"	=> $out_trade_no,
			"subject"	=> $subject,
			"total_fee"	=> $total_fee,
			"show_url"	=> $show_url,
			"app_pay"	=> "Y",//启用此参数能唤起钱包APP支付宝
			"body"	=> $body,
			//其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.2Z6TSk&treeId=60&articleId=103693&docType=1
			//如"参数名"	=> "参数值"   注：上一个参数末尾需要“,”逗号。

		);
//建立请求
		$alipaySubmit = new AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
		echo $html_text;
	} else {
		echo $alipay->buildRequestFormHTML(array(
			"service"       => "create_direct_pay_by_user",
			"partner"       => trim($alipay_config['partner']),
			"payment_type"  => $alipay_config['payment_type'],
			"notify_url"    => $alipay_config['notify_url'],
			"return_url"    => $alipay_config['return_url'],
			"seller_id"     => $alipay_config['partner'],
			"out_trade_no"  => $out_trade_no,
			"subject"       => $subject,
			"total_fee"     => $total_fee,
			"body"          => $body,
			"show_url"      => $show_url,
			"anti_phishing_key" => $anti_phishing_key,
			"exter_invoke_ip"   => $exter_invoke_ip,
			"_input_charset"    => trim(strtolower($alipay_config['input_charset']))
		), "post");
	}




	?>