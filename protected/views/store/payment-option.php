<?php
$this->renderPartial('/front/default-header', array(
    'h1' => t("Payment Option"),
    'sub_text' => t("choose your payment")
)); ?>

<?php
$this->renderPartial('/front/order-progress-bar', array(
    'step' => 4,
    'show_bar' => true
));

$s = $_SESSION;
$continue = false;
$lines=NULL;
$merchant_address = '';
$merchant_id = isset($s['kr_merchant_id'])? $s['kr_merchant_id']:NULL;
$free_delivery= isset($_SESSION['free_delivery'])?$_SESSION['free_delivery']:NULL;
$show_address_block="none";
if (!$address_book || !empty($is_guest_checkout) && $is_guest_checkout) {
    $show_address_block = "block";
}

if (isset($merchant_id) && $merchant_info = Yii::app()->functions->getMerchant($merchant_id)) {
    $merchant_address = $merchant_info['street'] . " " . $merchant_info['city'] . " " . $merchant_info['state'];
    $merchant_address .= " " . $merchant_info['post_code'];
}

$client_info = '';
$kr_search_address = isset($s['kr_search_address'])? $s['kr_search_address'] : NULL;

if (isset($is_guest_checkout)) {
    $continue = true;
} else {
    $client_info = Yii::app()->functions->getClientInfo(Yii::app()->functions->getClientId());
    if (isset($kr_search_address)) {
        $client_info['street']=$kr_search_address;
    }

    if (isset($merchant_id) && Yii::app()->functions->isClientLogin() && is_array($merchant_info)) {
        $continue = true;
    }
}
echo CHtml::hiddenField('mobile_country_code', Yii::app()->functions->getAdminCountrySet(true));

echo CHtml::hiddenField('admin_currency_set', getCurrencyCode());

echo CHtml::hiddenField('admin_currency_position',
    Yii::app()->functions->getOptionAdmin("admin_currency_position"));
?>


<div class="sections section-grey2 section-payment-option">
    <div class="container">

        <?php if ($continue == TRUE): ?>
            <?php
            echo CHtml::hiddenField('merchant_id', $merchant_id);
            ?>
            <div class="col-md-7 border">

                <div class="box-grey rounded">

                    <!-- BEGIN PICKUP-->
                        <?php if ($s['kr_delivery_options']['delivery_type'] == "pickup"): ?>

                        <form id="frm-delivery" class="frm-delivery" method="POST" onsubmit="return false;">
                            <?php
                            echo CHtml::hiddenField('action', 'placeOrder');
                            echo CHtml::hiddenField('country_code', $merchant_info['country_code']);
                            echo CHtml::hiddenField('currentController', 'store');
                            echo CHtml::hiddenField('delivery_type', $s['kr_delivery_options']['delivery_type']);
                            echo CHtml::hiddenField('cart_tip_percentage', '');
                            echo CHtml::hiddenField('cart_tip_value', '');
                            echo CHtml::hiddenField('client_order_sms_code');
                            echo CHtml::hiddenField('client_order_session');
                            if (isset($is_guest_checkout)) {
                                echo CHtml::hiddenField('is_guest_checkout', 2);
                            }
                            ?>


                        <?php  FunctionsV3::sectionHeader('Pickup Information')?>
                            <p class="uk-text-bold"><?php echo $merchant_address; ?></p>

                            <?php if (!isset($is_guest_checkout)): ?>
                                <?php if (getOptionA('mechant_sms_enabled') == ""): ?>
                                    <?php if (getOption($merchant_id, 'order_verification') == 2): ?>
                                        <?php $sms_balance = Yii::app()->functions->getMerchantSMSCredit($merchant_id); ?>
                                        <?php if ($sms_balance >= 1): ?>

                                            <div class="row top10">
                                                <div class="col-md-10">
                                                    <?php echo CHtml::textField('contact_phone', isIsset($client_info['contact_phone']), array(
                                                        'class' => 'mobile_inputs grey-fields',
                                                        'placeholder' => Yii::t("default", "Mobile Number"),
                                                        'data-validation' => "required"
                                                    )) ?>
                                                </div>
                                            </div>

                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>


                            <?php if (isset($is_guest_checkout)): ?> <!--PICKUP GUEST-->
                                <?php
                                $this->renderPartial('/front/guest-checkou-form', array(
                                    'merchant_id' => $merchant_id,
                                ));
                                ?>
                            <?php endif; ?>  <!--PICKUP GUEST-->

                        <!-- END PICKUP-->
                        <!-- BEGIN METRO-->
                        <?php elseif ($s['kr_delivery_options']['delivery_type'] == "metro") : ?>

                            <?php FunctionsV3::sectionHeader('Delivery Information') ?>
                            <p>
                                <?php echo clearString(ucwords($merchant_info['restaurant_name'])) ?><?php echo Yii::t("default", "Restaurant") ?>
                                <?php echo "<span class='bold'>" . Yii::t("default", ucwords($s['kr_delivery_options']['delivery_type'])) . "</span> ";
                                echo '<span class="bold">' . Yii::app()->functions->translateDate(date("M d Y", strtotime($s['kr_delivery_options']['delivery_date']))) .
                                    " " . t("at") . " " . $s['kr_delivery_options']['delivery_time'] . "</span> " . t("to");
                                ?>
                            </p>

                            <form id="frm-modal-enter-address" class="frm-modal-enter-address" method="POST" onsubmit="return false;" >
                                <?php echo CHtml::hiddenField('action','setMetro');?>

                                <div class="row top10">
                                    <div class="col-md-6 ">
                                        <?php echo CHtml::textField('client_metro',$kr_search_address , array(
                                            'class' => 'grey-fields full-width',
                                            'placeholder' => Yii::t("default", "Metro station name"),
                                            'data-validation' => "required"
                                        )) ?>

                                    </div>
                                    <!--<div class="col-md-1 top8">
                                        <?php /*if (isset($kr_search_address)): */?>
                                            <span style="color: green"><i class="ion-checkmark"></i> </span>
                                        <?php /*endif; */?>
                                   </div>-->
                                    <?php /*echo t('correspondence info').': ';
                                            if(isset($_SESSION['client_location']['lines'])) {
                                                foreach($_SESSION['client_location']['lines'] as $line) {
                                                    echo $line.' ';
                                                    $lines.=$line.' ';
                                                }
                                    }
                                     */?>
                                    <div class="col-md-4">
                                        <input type="submit" class="calculate_shipment_fee  green-button block medium full-width " value="确认">
                                    </div>

                                    <div class="col-md-5 col-xs-5 top8">
                                        <p class="right" >
                                           <!-- <?php /*if ($free_delivery): */?>
                                                <?php /*echo t("delivery fee").': '.t('free') */?>
                                            <?php /*else: */?>

                                                <?php /*echo t("delivery fee").': '. baseCurrency() . prettyFormat($shipping_fee, $merchant_id).$free_delivery */?>
                                            --><?php /*endif ;*/?>
                                        </p>
                                    </div>
                                </div>
                            </form>
                            <form id="frm-delivery" class="frm-delivery" method="POST" onsubmit="return false;">
                                <?php
                                echo CHtml::hiddenField('action', 'placeOrder');
                                echo CHtml::hiddenField('country_code', $merchant_info['country_code']);
                                echo CHtml::hiddenField('currentController', 'store');
                                echo CHtml::hiddenField('delivery_type', $s['kr_delivery_options']['delivery_type']);
                                echo CHtml::hiddenField('cart_tip_percentage', '');
                                echo CHtml::hiddenField('cart_tip_value', '');
                                echo CHtml::hiddenField('client_order_sms_code');
                                echo CHtml::hiddenField('client_order_session');
                                echo CHtml::hiddenField('client_address', $kr_search_address/*.';'.$lines*/);

                                if (isset($is_guest_checkout)) {
                                    echo CHtml::hiddenField('is_guest_checkout', 2);
                                }
                                ?>


                                <div class="top10">

                                <?php if (isset($is_guest_checkout)): ?>
                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::textField('first_name', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "First Name"),
                                                'data-validation' => "required"
                                            )) ?>
                                        </div>
                                    </div>

                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::textField('last_name', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "Last Name"),
                                                'data-validation' => "required"
                                            )) ?>
                                        </div>
                                    </div>
                                <?php endif; ?> <!--$is_guest_checkout-->

                                <?php if ($website_enabled_map_address == 2): ?>
                                    <div class="top10">
                                        <?php Widgets::AddressByMap() ?>
                                    </div>
                                <?php endif; ?>

                                <div class="row top10">
                                    <div class="col-md-3 top8">
                                        请选择取餐出口：
                                    </div>
                                    <div class="col-md-3">
                                        <?php

                                        $exitNumber = array(
                                            "地铁出口 1" => "地铁出口 1" ,"地铁出口 2" =>"地铁出口 2", "地铁出口 3" => "地铁出口 3",
                                            "地铁出口 4"=>"地铁出口 4", "地铁出口 5"=>"地铁出口 5", "地铁出口 6"=>"地铁出口 6",
                                            "地铁出口 7"=>"地铁出口 7", "地铁出口 8"=>"地铁出口 8", "地铁出口 9"=>"地铁出口 9");
                                        echo CHtml::dropDownList('delivery_instruction', '' ,$exitNumber, array(
                                            'class' => 'grey-fields full-width',
                                            'data-validation' => "required",
                                        )) ;

                                        ?>
                                    </div>
                                </div>

                                <div class="row top10">
                                    <div class="col-md-10">
                                        <?php echo CHtml::textField('contact_phone',
                                            isset($client_info['contact_phone']) ? $client_info['contact_phone'] : ''
                                            , array(
                                                'class' => 'grey-fields mobile_inputs full-width',
                                                'placeholder' => Yii::t("default", "Mobile Number"),
                                                'data-validation' => "required"
                                            )) ?>
                                    </div>
                                </div>


                                <?php if (isset($is_guest_checkout)): ?>
                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::textField('email_address', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "Email address"),
                                            )) ?>
                                        </div>
                                    </div>

                                <?php endif; ?>


                                <?php if (isset($is_guest_checkout)): ?>
                                    <?php FunctionsV3::sectionHeader('Optional') ?>
                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::passwordField('password', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "Password"),
                                            )) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div> <!--top10-->
                        <!-- END METRO-->
                        <?php else : ?> <!-- DELIVERY-->

                            <?php FunctionsV3::sectionHeader('Delivery Information') ?>
                                <p>
                                    <?php echo clearString(ucwords($merchant_info['restaurant_name'])) ?><?php echo Yii::t("default", "Restaurant") ?>
                                    <?php echo "<span>" . Yii::t("default", "will at") . "</span> ";
                                    if ($s['kr_delivery_options']['delivery_asap'] == 1) {
                                        echo $s['kr_delivery_options']['delivery_date'] . " " . Yii::t("default", "ASAP") .' '. t("to");
                                    } else {
                                        /*echo '<span class="bold">'.date("M d Y",strtotime($s['kr_delivery_options']['delivery_date'])).
                                        " ".t("at"). " ". $s['kr_delivery_options']['delivery_time']."</span> ".t("to");*/

                                        echo '<span class="bold">' . Yii::app()->functions->translateDate(date("M d Y", strtotime($s['kr_delivery_options']['delivery_date']))) .
                                            " " . t("at") . " " . $s['kr_delivery_options']['delivery_time'] . "</span> " . t("delivery to");
                                    }
                                    ?>
                                </p>
                                <form id="frm-modal-enter-address" class="frm-modal-enter-address" method="POST" onsubmit="return false;" >
                                    <?php echo CHtml::hiddenField('action','setAddress');?>



                                    <div class="row  address-block" style="display: <?=$show_address_block?>">
                                        <div class="col-md-10 top10">

                                            <?php echo CHtml::textField('client_address', $kr_search_address, array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "please enter your address"),
//                                                'data-validation' => "required"
                                            )) ?>

                                        </div>
                                        <div class="col-md-10 top10">
                                            <?php echo CHtml::textField('location_name',
                                                isset($client_info['location_name']) ? $client_info['location_name'] : ''
                                                , array(
                                                    'class' => 'grey-fields full-width',
                                                    'placeholder' => Yii::t("default", "Apartment suite, unit number, or company name")
                                                )) ?>

                                        </div>
                                        <div class="col-md-10 top10">
                                            <div class="">
                                                <input type="submit" class="calculate_shipment_fee  green-button  inline " value=" <?php echo t("deliver to this address") ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($address_book): ?>
                                        <div class="address_book_wrap">
                                            <div class="row top10">
                                                <div class="col-md-10">
                                                    <?php

                                                    echo CHtml::dropDownList('address_book_id', $_SESSION['address_book_id'],
                                                        (array)$address_list, array(
                                                            'class' => "grey-fields full-width",
                                                            'onchange' => "$('#frm-modal-enter-address').submit()",
                                                            'onload' => "alert(123)"
                                                        ));
                                                    ?>
                                                </div>
                                                <div class="col-md-3 col-xs-5">
                                                    <a href="javascript:;" class="edit_address_book block top10">
                                                        <i class="ion-document"></i> <?="使用新地址" ?>
                                                    </a>
                                                </div>
                                                <div class="col-md-3 col-xs-5">
                                                    <a href="<?=Yii::app()->request->baseUrl.'/profile' ?>" target="_blank" class="block top10">
                                                        <i class="ion-compose"></i> <?= "管理地址簿" ?>
                                                    </a>
                                                </div>

                                            </div>
                                        </div> <!--address_book_wrap-->
                                    <?php endif; ?>

                                    <?php if (!isset($is_guest_checkout)): ?>
                                    <div class="row top10 address-block" style="display: <?=$show_address_block?>">
                                        <div class="col-md-3 col-xs-5">
                                            <a href="javascript:;" class="show_address_book block top10">
                                                <i class="ion-compose"></i> <?php echo t("Show address book") ?>
                                            </a>
                                        </div>
                                        <div class="col-md-3 col-xs-5">
                                            <a href="<?=dirname($_SERVER['REQUEST_URI']).'/profile' ?>" target="_blank" class="block top10">
                                                <i class="ion-compose"></i> <?= "管理地址簿" ?>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            <form id="frm-delivery" class="frm-delivery" method="POST" onsubmit="return false;">
                                <?php
                                echo CHtml::hiddenField('action', 'placeOrder');
                                echo CHtml::hiddenField('country_code', $merchant_info['country_code']);
                                echo CHtml::hiddenField('currentController', 'store');
                                echo CHtml::hiddenField('delivery_type', $s['kr_delivery_options']['delivery_type']);
                                echo CHtml::hiddenField('cart_tip_percentage', '');
                                echo CHtml::hiddenField('cart_tip_value', '');
                                echo CHtml::hiddenField('client_order_sms_code');
                                echo CHtml::hiddenField('client_order_session');
                                echo CHtml::hiddenField('client_address', $kr_search_address);

                                if (isset($is_guest_checkout)) {
                                    echo CHtml::hiddenField('is_guest_checkout', 2);
                                }
                                ?>

                            <div class="top10">



                                <?php if (isset($is_guest_checkout)): ?>
                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::textField('first_name', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "First Name"),
                                                'data-validation' => "required"
                                            )) ?>
                                        </div>
                                    </div>

                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::textField('last_name', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "Last Name"),
                                                'data-validation' => "required"
                                            )) ?>
                                        </div>
                                    </div>
                                <?php endif; ?> <!--$is_guest_checkout-->

                                <?php if ($website_enabled_map_address == 2): ?>
                                    <div class="top10">
                                        <?php Widgets::AddressByMap() ?>
                                    </div>
                                <?php endif; ?>



                                <div class="row top10">
                                    <div class="col-md-10">
                                        <?php echo CHtml::textField('contact_phone',
                                            isset($client_info['contact_phone']) ? $client_info['contact_phone'] : ''
                                            , array(
                                                'class' => 'grey-fields mobile_inputs full-width',
                                                'placeholder' => Yii::t("default", "Mobile Number"),
                                                'data-validation' => "required"
                                            )) ?>
                                    </div>
                                </div>

                                <div class="row top10">
                                    <div class="col-md-10">
                                        <?php echo CHtml::textField('delivery_instruction', '', array(
                                            'class' => 'grey-fields full-width',
                                            'placeholder' => Yii::t("default", "Delivery instructions")
                                        )) ?>
                                    </div>
                                </div>

                                <!--<div class="row top10">
                                    <div class="col-md-10">
                                        <?php
/*                                        echo CHtml::checkBox('saved_address', false, array('class' => "icheck", 'value' => 2));
                                        echo " " . t("Save to my address book");
                                        */?>
                                    </div>
                                </div>-->

                                <?php if (isset($is_guest_checkout)): ?>
                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::textField('email_address', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "Email address"),
                                            )) ?>
                                        </div>
                                    </div>

                                <?php endif; ?>


                                <?php if (isset($is_guest_checkout)): ?>
                                    <?php FunctionsV3::sectionHeader('Optional') ?>
                                    <div class="row top10">
                                        <div class="col-md-10">
                                            <?php echo CHtml::passwordField('password', '', array(
                                                'class' => 'grey-fields full-width',
                                                'placeholder' => Yii::t("default", "Password"),
                                            )) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div> <!--top10-->

                        <?php endif; ?> <!-- ENDIF DELIVERY-->


                        <div class="top25">
                            <?php
                            $this->renderPartial('/front/payment-list', array(
                                'merchant_id' => $merchant_id,
                                'payment_list' => FunctionsV3::getMerchantPaymentList($merchant_id)
                            ));
                            ?>
                        </div>

                        <!--TIPS-->
                        <?php if (Yii::app()->functions->getOption("merchant_enabled_tip", $merchant_id) == 2): ?>
                            <?php
                            $merchant_tip_default = Yii::app()->functions->getOption("merchant_tip_default", $merchant_id);
                            if (!empty($merchant_tip_default)) {
                                echo CHtml::hiddenField('default_tip', $merchant_tip_default);
                            }
                            $FunctionsK = new FunctionsK();
                            $tips = $FunctionsK->tipsList();
                            ?>
                            <div class="section-label top25">
                                <a class="section-label-a">
	      <span class="bold">
	        <?php echo t("Tip Amount") ?> (<span class="tip_percentage">0%</span>)
	      </span>
                                    <b></b>
                                </a>
                            </div>

                            <div class="uk-panel uk-panel-box">
                                <ul class="tip-wrapper">
                                    <?php foreach ($tips as $tip_key => $tip_val): ?>
                                        <li>
                                            <a class="tips" href="javascript:;" data-type="tip"
                                               data-tip="<?php echo $tip_key ?>">
                                                <?php echo $tip_val ?>
                                            </a>

                                        </li>
                                    <?php endforeach; ?>
                                    <li><a class="tips" href="javascript:;" data-type="cash"
                                           data-tip="0"><?php echo t("Tip cash") ?></a></li>
                                    <li><?php echo CHtml::textField('tip_value', '', array(
                                            'class' => "numeric_only grey-fields",
                                            'style' => "width:70px;"
                                        )); ?></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <!--END TIPS-->

                    </form>

                    <!--CREDIT CART-->
                    <?php
                    $this->renderPartial('/front/credit-card', array(
                        'merchant_id' => $merchant_id
                    ));
                    ?>
                    <!--END CREDIT CART-->

                </div> <!--box rounded-->

            </div> <!--left content-->

            <div class="col-md-5 border sticky-div"><!-- RIGHT CONTENT STARTS HERE-->

                <div class="box-grey rounded  relative top-line-green">

                    <i class="order-icon your-order-icon"></i>

                    <div class="order-list-wrap">

                        <p class="bold center"><?php echo t("Your Order") ?></p>
                        <div class="item-order-wrap-with-delivery-fee"></div>

                        <!--VOUCHER STARTS HERE-->
                        <?php Widgets::applyVoucher($merchant_id); ?>
                        <!--VOUCHER STARTS HERE-->

                        <?php
                        if (FunctionsV3::hasModuleAddon("pointsprogram")) {
                            /*POINTS PROGRAM*/
                            PointsProgram::redeemForm();
                        }
                        ?>

                        <?php
                        $minimum_order = Yii::app()->functions->getOption('merchant_minimum_order', $merchant_id);
                        $maximum_order = getOption($merchant_id, 'merchant_maximum_order');
                        if ($s['kr_delivery_options']['delivery_type'] == "pickup") {
                            $minimum_order = Yii::app()->functions->getOption('merchant_minimum_order_pickup', $merchant_id);
                            $maximum_order = getOption($merchant_id, 'merchant_maximum_order_pickup');
                        }
                        ?>

                        <?php
                        if (!empty($minimum_order)) {
                            echo CHtml::hiddenField('minimum_order', unPrettyPrice($minimum_order));
                            echo CHtml::hiddenField('minimum_order_pretty', baseCurrency() . prettyFormat($minimum_order));
                            ?>
                            <p class="small center"><?php echo t("Subtotal must exceed") ?>
                                <?php echo baseCurrency() . prettyFormat($minimum_order, $merchant_id) ?>
                            </p>
                            <?php
                        }
                        if ($maximum_order > 0) {
                            echo CHtml::hiddenField('maximum_order', unPrettyPrice($maximum_order));
                            echo CHtml::hiddenField('maximum_order_pretty', baseCurrency() . prettyFormat($maximum_order));
                        }
                        ?>

                        <?php if (getOptionA('captcha_order') == 2 || getOptionA('captcha_customer_signup') == 2): ?>
                            <div class="top10 capcha-wrapper">
                                <?php //GoogleCaptcha::displayCaptcha()?>
                                <div id="kapcha-1"></div>
                            </div>
                        <?php endif; ?>

                        <!--SMS Order verification-->
                        <?php if (getOptionA('mechant_sms_enabled') == ""): ?>
                            <?php if (getOption($merchant_id, 'order_verification') == 2): ?>
                                <?php $sms_balance = Yii::app()->functions->getMerchantSMSCredit($merchant_id); ?>
                                <?php if ($sms_balance >= 1): ?>
                                    <?php $sms_order_session = Yii::app()->functions->generateCode(50); ?>
                                    <p class="top20 center">
                                        <?php echo t("This merchant has required SMS verification") ?><br/>
                                        <?php echo t("before you can place your order") ?>.<br/>
                                        <?php echo t("Click") ?> <a href="javascript:;" class="send-order-sms-code"
                                                                    data-session="<?php echo $sms_order_session; ?>">
                                            <?php echo t("here") ?></a>
                                        <?php echo t("receive your order sms code") ?>
                                    </p>
                                    <div class="top10 text-center">
                                        <?php
                                        echo CHtml::textField('order_sms_code', '', array(
                                            'placeholder' => t("SMS Code"),
                                            'maxlength' => 8,
                                            'class' => 'grey-fields text-center'
                                        ));
                                        ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <!--END SMS Order verification-->

                        <div class="text-center top25">
                            <a href="javascript:;" class="place_order orange-button medium inline block">
                                <?php echo t("Place Order") ?>
                            </a>
                        </div>

                    </div> <!-- order-list-wrap-->
                </div> <!--box-grey-->

            </div> <!--right content-->

        <?php else : ?>
            <div class="box-grey rounded">
                <p class="text-danger">
                    <?php echo t("Something went wrong Either your visiting the page directly or your session has expired.") ?></p>
            </div>
        <?php endif; ?>

    </div>  <!--container-->
</div> <!--section-payment-option-->

<script type="text/javascript">

    jQuery(document).ready(function() {
        var google_auto_address= $("#google_auto_address").val();
        if ( google_auto_address =="yes") {
        } else {
            $("#client_address").geocomplete({
                country: $("#admin_country_set").val()
            });
        }
    });


</script>