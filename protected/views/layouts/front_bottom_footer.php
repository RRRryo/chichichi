
<?php if ( getOptionA('disabled_subscription') == ""):?>
<!--<form method="POST" id="frm-subscribe" class="frm-subscribe" onsubmit="return false;">
< ?php echo CHtml::hiddenField('action','subscribeNewsletter')?>
<div class="sections section-subcribe">
  <div class="container">
      <h2><  ?php echo t("Subscribe to our newsletter") ?></h2>
      <div class="subscribe-footer">
          <div class="row border">
             <div class="col-md-3 border col-md-offset-4 ">
               < ?php echo CHtml::textField('subscriber_email','',array(
                 'placeholder'=>t("E-mail"),
                 'required'=>true,
                 'class'=>"email"
               ))?>
             </div>
             <div class="col-md-2 border">
               <button class="green-button rounded">
                < ?php echo t("Subscribe")?>
               </button>               
             </div>
          </div>
      </div>
  </div>
  

<img src="< ?php echo assetsURL()."/images/divider.png"?>" class="footer-divider">
  
</div> <!--section-browse-resto-->
<!--</form>-->
<?php endif;?>


<div class="sections section-footer">
  <div class="container">
      <div class="row">
          <div class="col-md-6 col-xs-6">
              <div class="pull-right">
                  <img src="<?php echo assetsURL()."/images/client_contact_qr.jpg"?>">
                  <div class="center">近味客服</div>
              </div>
          </div>
          <div class="col-md-6 col-xs-6">
              <div class="pull-left">
                  <img src="<?php echo assetsURL()."/images/commerce_contact_qr.jpg"?>">
                  <div class="center">近味商服</div>
              </div>
          </div>
      </div>
      <div class="row top10">
          <div class="col-md-6 col-xs-12">
              <div class="center">
                深圳市南山区南山大道中兴之家E座616
              </div>
          </div>
          <div class="col-md-2 col-xs-4">
              <div class="center">
              成为家厨
              </div>
          </div>
          <div class="col-md-2 col-xs-4">
              <div class="center">
              关于我们
              </div>
          </div>
          <div class="col-md-2 col-xs-4">
              <div class="center">
              加入我们
              </div>
          </div>
      </div>


      <p class="center">
          <a href="http://www.miitbeian.gov.cn/">粤ICP备16107051号</a>
      </p>
  </div> <!--container-->
</div> <!--section-footer-->