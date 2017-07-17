
<!-- <div class="shadow">

        </div> -->
<!-- slider  start-->
<div class="banner container">
    <div class="swiper-container">
        <div class="swiper-wrapper">
            <div class="swiper-slide"><img src="<?=assetsURL()."/images/banner/jinwei_banner_02.jpg" ?>"></div>
            <div class="swiper-slide"><img src="<?=assetsURL()."/images/banner/jinwei_banner_02.jpg" ?>"></div>
            <div class="swiper-slide"><img src="<?=assetsURL()."/images/banner/jinwei_banner_02.jpg" ?>"></div>
        </div>
        <!-- Add Pagination -->
        <div class="swiper-pagination"></div>
    </div>
</div>

<!-- slider end -->
<!-- menu start -->
<div class="menu">
    <div class="container">

        <div class="row">
            <div class="col-lg-6 col-md-12" >
                <dl class="col-xs-3">
                    <dt><a href="<?= Yii::app()->createUrl('/cuisine?cateroy=18')?>"><img src="<?=assetsURL()."/images/navpic/beijingcai.png" ?>"></a></dt>
                    <dd>北京菜</dd>
                </dl>
                <dl class="col-xs-3">
                    <dt><a href="<?= Yii::app()->createUrl('/cuisine?cateroy=20')?>"><img src="<?=assetsURL()."/images/navpic/chuancai.png" ?>"></a></dt>
                    <dd>川菜</dd>
                </dl>
                <dl  class="col-xs-3">
                    <dt><a href="<?= Yii::app()->createUrl('/cuisine?cateroy=22')?>"><img src="<?=assetsURL()."/images/navpic/shanxicai.png" ?>"></a></dt>
                    <dd>陕西菜</dd>
                </dl>
                <dl  class="col-xs-3">
                    <dt><a href="<?= Yii::app()->createUrl('/cuisine?cateroy=23')?>"><img src="<?=assetsURL()."/images/navpic/zhejiangcai.png" ?>"></a></dt>
                    <dd>浙江菜</dd>
                </dl>
            </div>
            <div class="col-lg-6 col-md-12" >
                <dl class="col-xs-3">
                    <dt><a href="<?= Yii::app()->createUrl('/cuisine?cateroy=27')?>"><img src="<?=assetsURL()."/images/navpic/hanguocai.png" ?>"></a></dt>
                    <dd>韩国菜</dd>
                </dl>
                <dl class="col-xs-3">
                    <dt><a href="#"><img src="<?=assetsURL()."/images/navpic/ribencai.png" ?>"></a></dt>
                    <dd>日本菜</dd>
                </dl>
                <dl  class="col-xs-3">
                    <dt><a href="<?= Yii::app()->createUrl('/cuisine?cateroy=24')?>"><img src="<?=assetsURL()."/images/navpic/baolei.png" ?>"></a></dt>
                    <dd>煲类</dd>
                </dl>
                <dl  class="col-xs-3">
                    <dt><a href="#"><img src="<?=assetsURL()."/images/navpic/qita.png" ?>"></a></dt>
                    <dd>其他</dd>
                </dl>
            </div>
        </div>
    </div>
</div>


<!-- menu  end-->
<!-- 菜品推荐 start -->
<div class=" caipin">
    <div class="container">
        <div class="food-recom">
            <div class="title after">
                <img src="<?=assetsURL()."/images/caipintuijian/caipintuijian_title.png" ?>" alt="" />
            </div>
        </div>
        <div class="food-recom-content">
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/xianmingchi" ?>" >
                                <img src="<?=baseUrl()."/upload/1485039507-img_1182.jpg" ?>" alt="" />
                                <div class="caipin-text">经典腊汁肉夹馍</div>
                                <div class="caipin-money">€4.00</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?=baseUrl() . "/store/menu/merchant/weixiaobao" ?>" >
                                <img src="<?=baseUrl()."/upload/1488835023-IMG_0271.JPG" ?>" alt="" />
                                <div class="caipin-text">虫草老花鸭煲</div>
                                <div class="caipin-money">€35.00</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?=baseUrl() . "/store/menu/merchant/004" ?>" >
                                <img class="full-width" src="<?= baseUrl()."/upload/1485042669-img_1190.jpg" ?>" alt=""/>
                                <div class="caipin-text">郭氏夫妻肺片</div>
                                <div class="caipin-money">€10.80</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/005" ?>" >
                                <img src="<?=baseUrl()."/upload/1485189931-211.jpg" ?>" alt="" />
                                <div class="caipin-text">五花咸蛋黄肉粽</div>
                                <div class="caipin-money">€2.00</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/chengduchufang" ?>" >
                                <img src="<?=baseUrl()."/upload/1488561413-.jpg" ?>" alt="" />
                                <div class="caipin-text">私房小龙虾</div>
                                <div class="caipin-money">€21.00起</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/004" ?>" >
                                <img src="<?=baseUrl()."/upload/1485042132-img_1188.jpg" ?>" alt="" />
                                <div class="caipin-text">冷吃兔丁</div>
                                <div class="caipin-money">€12.80</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/jiujiajiu" ?>" >
                                <img src="<?=baseUrl()."/upload/niurouliangpi.jpg" ?>" alt="" />
                                <div class="caipin-text">牛肉凉皮</div>
                                <div class="caipin-money">€6.00</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/nanchangxiaopu" ?>" >
                                <img src="<?=baseUrl()."/upload/mizhifenzhengrou.jpg" ?>" alt="" />
                                <div class="caipin-text">秘制粉蒸肉</div>
                                <div class="caipin-money">€18.00</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/tianjinliuji" ?>" >
                                <img src="<?=baseUrl()."/upload/tiebanyouyu.jpg" ?>" alt="" />
                                <div class="caipin-text">铁板鱿鱼须</div>
                                <div class="caipin-money">€14.00</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/mingyuanxiaochao" ?>" >
                                <img src="<?=baseUrl()."/upload/koushuiji.jpg" ?>" alt="" />
                                <div class="caipin-text">铭源口水鸡</div>
                                <div class="caipin-money">€6.80</div>
                            </a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="caipin-pic">
                            <a href="<?= baseUrl() . "/store/menu/merchant/hanshiwaimai" ?>" >
                                <img src="<?=baseUrl()."/upload/youyubanfan.jpg" ?>" alt="" />
                                <div class="caipin-text">鱿鱼拌饭</div>
                                <div class="caipin-money">€12.00</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 菜品推荐 end -->
<!-- 商家推荐 start -->

<div class=" Merchant">
    <div class="container ">
        <div class="Merchant-title">
            <img src="<?=assetsURL()."/images/shangjiatuijian/shangjiatuijian_title.png" ?>" alt="" />
        </div>
        <div class="Merchant-content">
            <div class="row all-Merchant">
                <?php  ($res = Yii::app()->functions->getFeatureMerchant2()) ?>

                <?php foreach ($res as $val): //dump($val);?>

                    <div class="col-lg-6 col-sm-12 Merchant-list">
						<span class="Merchant-img lf">
                            <img  src="<?= FunctionsV3::getMerchantLogo($val['merchant_id']); ?>" alt="" style="max-height: 100%;" />
						</span>
                        <div class="Merchant-details lf">
                            <p class="after">
                                <span class="Merchant-name lf"><?=$val['restaurant_name']?></span>
								<span class="delivery-price rf">
                                    <?php $minimum_order=Yii::app()->functions->getOption('merchant_minimum_order', $val['merchant_id']); ?>
                                    <?php if (!empty($minimum_order)):?>
                                        <span class="price-num">€&nbsp;<?=$minimum_order?></span>
                                        起送
                                    <?php else:?>
                                        <span class="price-num">0€</span>起送
                                    <?php endif?>

								</span>
                            </p>
                            <p class="praise-stars">
                                <?php $ratings = Yii::app()->functions->getRatings($val['merchant_id']); ?>
                                <?php for  ($i = 0; $i < $ratings['ratings'] ; $i = $i+1 ) : ?>
                                    <span class="star-solid"></span>
                                <?php endfor ?>
                                <span class="star-solid"></span>
                                <span class="star-solid"></span>
                                <span class="star-solid"></span>
                                <span class="star-solid"></span>
                                <span class="star-solid"></span>
                                <span class="star-num">(<?=$val['merchant_id']['votes'] ?>)</span>
                            </p>
                            <p class="delivery">
                                <span class="delivery-scope">配送范围:<?=getOption($val['merchant_id'],'merchant_delivery_miles');?>公里</span>/
								<span class="delivery-time">
									配送时间:<?=FunctionsV3::getDeliveryEstimation( $val['merchant_id'])?>小时
								</span>
                            </p>
                            <a href="<?php echo baseUrl() . "/store/menu/merchant/" . $val['restaurant_slug'] ?>"
                               class="support-reservation">可预订</a>
                        </div>
                    </div>
                <?php endforeach; ?>



            </div>

        </div>
    </div>
</div>
<!-- 商家推荐 end -->
<script>
    $(function(){
        var numberList=0;
        var isPc=$(".caipin").width();
        if(isPc>768){
            numberList=5
        }else{
            numberList=3
        }
        // $("#example-navbar-collapse").css({"margin-left":"100vw","height":"100vh"});
        var mySwiper = new Swiper ('.banner .swiper-container', {
            direction: 'horizontal',
            loop: true,
            autoplay : 3000,
            // 如果需要分页器
            pagination: '.swiper-pagination'
        })

        var zdySwiper = new Swiper ('.food-recom-content .swiper-container', {
            slidesPerView : numberList
        })
        $(".Merchant-list:even").css({
            "margin-right":"9.83%"
        })
    })
    /*$(".navbar-header>button").click(function(){

     $("#example-navbar-collapse").animate({
     "margin-left":0,
     "height":"100vh",
     "display":"block"
     },500)
     })*/

</script>
