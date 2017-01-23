<?php
$kr_search_adrress = FunctionsV3::getSessionAddress();

$home_search_text = Yii::app()->functions->getOptionAdmin('home_search_text');
if (empty($home_search_text)) {
    $home_search_text = Yii::t("default", "Find restaurants near you");
}

$home_search_subtext = Yii::app()->functions->getOptionAdmin('home_search_subtext');
if (empty($home_search_subtext)) {
    $home_search_subtext = Yii::t("default", "Order Delivery Food Online From Local Restaurants");
}

$home_search_mode = Yii::app()->functions->getOptionAdmin('home_search_mode');
$placholder_search = Yii::t("default", "Street Address,City,State");
if ($home_search_mode == "postcode") {
    $placholder_search = Yii::t("default", "Enter your postcode");
}
$placholder_search = Yii::t("default", $placholder_search);
?>

<img class="mobile-home-banner" src="<?php echo assetsURL() . "/images/banner.jpg" ?>">

<div id="parallax-wrap" class="parallax-container parallax-home"
     data-parallax="scroll" data-position="top" data-bleed="10"
     data-image-src="<?php echo assetsURL() . "/images/banner.jpg" ?>">

    <?php
    if ($home_search_mode == "address" || $home_search_mode == "") {
        if ($enabled_advance_search == "yes") {
            $this->renderPartial('/front/advance_search', array(
                'home_search_text' => $home_search_text,
                'kr_search_adrress' => $kr_search_adrress,
                'placholder_search' => $placholder_search,
                'home_search_subtext' => $home_search_subtext,
                'theme_search_merchant_name' => getOptionA('theme_search_merchant_name'),
                'theme_search_street_name' => getOptionA('theme_search_street_name'),
                'theme_search_cuisine' => getOptionA('theme_search_cuisine'),
                'theme_search_foodname' => getOptionA('theme_search_foodname'),
                'theme_search_merchant_address' => getOptionA('theme_search_merchant_address'),
            ));
        } else $this->renderPartial('/front/single_search', array(
            'home_search_text' => $home_search_text,
            'kr_search_adrress' => $kr_search_adrress,
            'placholder_search' => $placholder_search,
            'home_search_subtext' => $home_search_subtext
        ));
    } else {
        $this->renderPartial('/front/search_postcode', array(
            'home_search_text' => $home_search_text,
            'placholder_search' => $placholder_search,
            'home_search_subtext' => t("Enter your post code")
        ));
    }
    ?>

</div> <!--parallax-container-->

<?php if ($theme_hide_how_works <> 2): ?>
    <!--HOW IT WORKS SECTIONS-->
    <!--<div class="sections section-how-it-works">
    <div class="container">
     <h2>< ?php echo t("How it works")?></h2>
     <p class="center">< ?php echo t("Get your favourite food in 4 simple steps")?></p>

     <div class="row">
       <div class="col-md-3 col-sm-3 center">
          <div class="steps step1-icon">
            <img src="< ?php echo assetsURL()."/images/step1.png"?>">
          </div>
          <h3>< ?php echo t("Search")?></h3>
          <p>< ?php echo t("Find all restaurants available near you")?></p>
       </div>
       <div class="col-md-3 col-sm-3 center">
          <div class="steps step2-icon">
             <img src="< ?php echo assetsURL()."/images/step2.png"?>">
          </div>
          <h3>< ?php echo t("Choose")?></h3>
          <p>< ?php echo t("Browse hundreds of menus to find the food you like")?></p>
       </div>
       <div class="col-md-3 col-sm-3  center">
          <div class="steps step2-icon">
            <img src="< ?php echo assetsURL()."/images/step3.png"?>">
          </div>
          <h3>< ?php echo t("Pay")?></h3>
          <p>< ?php echo t("It's quick, secure and easy")?></p>
       </div>
       <div class="col-md-3 col-sm-3  center">
         <div class="steps step2-icon">
           <img src="< ?php echo assetsURL()."/images/step4.png"?>">
         </div>
          <h3>< ?php echo t("Enjoy")?></h3>
          <p>< ?php echo t("Food is prepared & delivered to your door")?></p>
       </div>
     </div>

     </div> <!--container-->
    <!--</div>--> <!--section-how-it-works-->
<?php endif; ?>


<!--FEATURED RESTAURANT SECIONS-->
<?php if ($disabled_featured_merchant == ""): ?>
    <?php if (getOptionA('disabled_featured_merchant') != "yes"): ?>
        <?php if ($res = Yii::app()->functions->getFeatureMerchant2()): ?>
            <div class="sections section-feature-resto">
                <div class="container">

                    <script>
                        $(document).ready(function () {
                            var owl = $("#owl-demo");
                            owl.owlCarousel({
                                itemsCustom: [
                                    [0, 2],
                                    [450, 2],
                                    [600, 3],
                                    [700, 4],
                                    [1000, 5],
                                    [1200, 5],
                                    [1400, 5],
                                    [1600, 5]
                                ],
                                navigation: true,
                                pagination: false
                            });
                        });
                    </script>
                    <h2 class="hed-sec"><?php echo t("Featured Restaurants") ?></h2>

                    <div class=" restourant-slider">
                        <div id="demo">
                            <div id="owl-demo" class="owl-carousel">
                                <?php foreach ($res as $val): //dump($val);?>
                                    <div class="item">
                                        <a href="<?php echo baseUrl() . "/store/menu/merchant/" . $val['restaurant_slug'] ?>"
                                           class="slider-img">
                                            <img src="<?php echo FunctionsV3::getMerchantLogo($val['merchant_id']); ?>"
                                                 alt=""/>
                                            <?php echo clearString($val['restaurant_name']); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>


                            </div>
                        </div>
                    </div>
                </div> <!--container-->
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
<!--END FEATURED RESTAURANT SECIONS-->

<script>
    $(document).ready(function () {
        var owl = $("#owl-demo2");
        owl.owlCarousel({
            itemsCustom: [
                [0, 2],
                [450, 2],
                [600, 3],
                [700, 4],
                [1000, 5],
                [1200, 5],
                [1400, 5],
                [1600, 5]
            ],
            navigation: true,
            pagination: false
        });
    });
</script>
<!--featured-food S-->
<div class="container">
    <div class="feature-box">
        <h2 class="hed-sec"><?php echo t("Featured Food") ?></h2>

        <div id="demo">
            <div id="owl-demo2" class="owl-carousel">
                <div class="item">
                    <a href="<?php echo baseUrl() . "/menu-11" ?>" class="slider-img">

                        <div class="iner-li">
                            <div class="food-img">

                                <img src="upload/1481148194-3.png" alt=""/>

                            </div>
                            <div class="food-detail">
                                <h3>Food Name </h3>
                                <p> $25</p>

                            </div>
                        </div>
                    </a>

                </div>
                <div class="item">

                    <a href="<?php echo baseUrl() . "/menu-11" ?>" class="slider-img">
                        <div class="iner-li">
                            <div class="food-img">

                                <img src="upload/1481324697-5.png" alt=""/></div>
                            <div class="food-detail">
                                <h3>Food Name </h3>
                                <p> $25</p>

                            </div>
                        </div>
                    </a>

                </div>
                <div class="item">

                    <a href="<?php echo baseUrl() . "/menu-11" ?>" class="slider-img">
                        <div class="iner-li">
                            <div class="food-img">

                                <img src="upload/1481580406-2.png" alt=""/></div>
                            <div class="food-detail">
                                <h3>Food Name </h3>
                                <p> $25</p>

                            </div>
                        </div>
                    </a>

                </div>
                <div class="item">
                    <a href="<?php echo baseUrl() . "/menu-11" ?>" class="slider-img">
                        <div class="iner-li">
                            <div class="food-img">

                                <img src="upload/1481604155-edadd4eagw1evr1s0z0lgj20kg0kp0xl.jpg" alt=""/></div>
                            <div class="food-detail">
                                <h3>Food Name </h3>
                                <p> $25</p>

                            </div>
                        </div>
                    </a>

                </div>
                <div class="item">
                    <a href="<?php echo baseUrl() . "/menu-11" ?>" class="slider-img">
                        <div class="iner-li">
                            <div class="food-img">

                                <img src="upload/1481580959-6.png" alt=""/></div>
                            <div class="food-detail">
                                <h3>Food Name </h3>
                                <p> $25</p>

                            </div>
                        </div>
                    </a>

                </div>

                <div class="item">
                    <a href="<?php echo baseUrl() . "/menu-11" ?>" class="slider-img">
                        <div class="iner-li">
                            <div class="food-img">

                                <img src="upload/1481580959-6.png" alt=""/></div>
                            <div class="food-detail">
                                <h3>Food Name </h3>
                                <p> $25</p>

                            </div>
                        </div>
                    </a>

                </div>


            </div>
        </div>

    </div>
</div>
<!--featured-food E-->

<!--local discount E-->
<div class="sections section-feature-resto">
    <div class="container">

        <script>
            $(document).ready(function () {
                var owl = $("#owl-demo1");
                owl.owlCarousel({
                    itemsCustom: [
                        [0, 2],
                        [450, 2],
                        [600, 3],
                        [700, 4],
                        [1000, 5],
                        [1200, 5],
                        [1400, 5],
                        [1600, 5]
                    ],
                    navigation: true,
                    pagination: false
                });
            });
        </script>
        <h2 class="hed-sec"><?php echo t("Local Discount") ?></h2>

        <div class=" restourant-slider">
            <div id="demo">
                <div id="owl-demo1" class="owl-carousel">

                    <div class="item">
                        <!--              --><?php //echo baseUrl()."/menu-11"?>
                        <a href="javascript: void(0)" class="slider-img"><img src="upload/1481148194-3.png" alt=""/></a>
                    </div>
                    <div class="item">
                        <a href="javascript: void(0)" class="slider-img"><img src="upload/1481324697-5.png" alt=""/></a>
                    </div>
                    <div class="item">
                        <a href="javascript: void(0)" class="slider-img"><img src="upload/1481580406-2.png" alt=""/></a>
                    </div>
                    <div class="item">
                        <a href="javascript: void(0)" class="slider-img"><img
                                src="upload/1481604155-edadd4eagw1evr1s0z0lgj20kg0kp0xl.jpg" alt=""/></a>
                    </div>
                    <div class="item">
                        <a href="javascript: void(0)" class="slider-img"><img src="upload/1481580959-6.png" alt=""/></a>
                    </div>
                    <div class="item">
                        <a href="javascript: void(0)" class="slider-img"><img src="upload/1481146615-1.png" alt=""/></a>
                    </div>


                </div>
            </div>
        </div>


    </div> <!--container-->
</div>

<!--local discount E-->


<?php if ($theme_hide_cuisine <> 2): ?>
    <!--CUISINE SECTIONS-->
    <?php if ($list = FunctionsV3::getCuisine()): ?>
        <div class="sections section-cuisine">
            <div class="container  nopad">

                <!--<div class="col-md-3 nopad">
<img src="<?php /*echo assetsURL()."/images/cuisine.png"*/ ?>" class="img-cuisine">
</div>-->

                <div>
                    <!--    class="col-md-9  nopad"-->
                    <h2><?php echo t("Browse by cuisine") ?></h2>
                    <p class="sub-text center"><?php echo t("choose from your favorite cuisine") ?></p>

                    <div class="row">
                        <?php $x = 1; ?>
                        <?php foreach ($list as $val): ?>
                            <div class="col-md-4 col-sm-4 indent-5percent nopad">
                                <a href="<?php echo Yii::app()->createUrl('/store/cuisine', array("category" => $val['cuisine_id'])) ?>"
                                   class="<?php echo ($x % 2) ? "even" : 'odd' ?>">
                                    <?php
                                    $cuisine_json['cuisine_name_trans'] = !empty($val['cuisine_name_trans']) ? json_decode($val['cuisine_name_trans'], true) : '';
                                    echo qTranslate($val['cuisine_name'], 'cuisine_name', $cuisine_json);
                                    /*if($val['total']>0){
                                        echo "<span>(".$val['total'].")</span>";
                                    }*/
                                    ?>
                                </a>
                            </div>
                            <?php $x++; ?>
                        <?php endforeach; ?>
                    </div>

                </div>

            </div> <!--container-->
        </div> <!--section-cuisine-->
    <?php endif; ?>
<?php endif; ?>


<?php if ($theme_show_app == 2): ?>
    <!--MOBILE APP SECTION-->
    <div id="mobile-app-sections" class="container">
        <div class="container-medium">
            <div class="row">
                <div class="col-xs-5 into-row border app-image-wrap">
                    <img class="app-phone" src="<?php echo assetsURL() . "/images/getapp-2.jpg" ?>">
                </div> <!--col-->
                <div class="col-xs-7 into-row border">
                    <h2><?php echo getOptionA('website_title') . " " . t("in your mobile") ?>! </h2>
                    <h3 class="green-text"><?php echo t("Get our app, it's the fastest way to order food on the go") ?>
                        .</h3>

                    <div class="row border" id="getapp-wrap">
                        <?php if (!empty($theme_app_ios) && $theme_app_ios != "http://"): ?>
                            <div class="col-xs-4 border">
                                <a href="<?php echo $theme_app_ios ?>" target="_blank">
                                    <img class="get-app" src="<?php echo assetsURL() . "/images/get-app-store.png" ?>">
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($theme_app_android) && $theme_app_android != "http://"): ?>
                            <div class="col-xs-4 border">
                                <a href="<?php echo $theme_app_android ?>" target="_blank">
                                    <img class="get-app"
                                         src="<?php echo assetsURL() . "/images/get-google-play.png" ?>">
                                </a>
                            </div>
                        <?php endif; ?>

                    </div> <!--row-->

                </div> <!--col-->
            </div> <!--row-->
        </div> <!--container-medium-->

        <div class="mytable border" id="getapp-wrap2">
            <?php if (!empty($theme_app_ios) && $theme_app_ios != "http://"): ?>
                <div class="mycol border">
                    <a href="<?php echo $theme_app_ios ?>" target="_blank">
                        <img class="get-app" src="<?php echo assetsURL() . "/images/get-app-store.png" ?>">
                    </a>
                </div> <!--col-->
            <?php endif; ?>
            <?php if (!empty($theme_app_android) && $theme_app_android != "http://"): ?>
                <div class="mycol border">
                    <a href="<?php echo $theme_app_android ?>" target="_blank">
                        <img class="get-app" src="<?php echo assetsURL() . "/images/get-google-play.png" ?>">
                    </a>
                </div> <!--col-->
            <?php endif; ?>
        </div> <!--mytable-->


    </div> <!--container-->
    <!--END MOBILE APP SECTION-->
<?php endif; ?>


 