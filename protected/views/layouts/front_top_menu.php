<nav id="header" class="navbar navbar-fixed-top" style="height: 50px;">
<div id="header-container" class="container navbar-container">
<div class="top-menu-wrapper <?php echo "top-".$action;?>">

<div class="container border" >
  <div class="col-md-3 col-xs-3 border col-a">
    <?php if ( $theme_hide_logo<>2):?>
    <a class="logo-ancher" href="<?php echo Yii::app()->createUrl('/store/home')?>">
     <img src="<?php echo FunctionsV3::getDesktopLogo();?>" class="logo logo-desktop">
     <img src="<?php echo FunctionsV3::getMobileLogo();?>" class="logo logo-mobile">
    </a>
    <?php endif;?>
  </div>

  <div class="col-xs-1 menu-nav-mobile border relative">
     <a href="#"><i class="ion-android-menu"></i></a>
  </div> <!--menu-nav-mobile-->

  <?php if ( Yii::app()->controller->action->id =="menu"):?>
  <div class="col-xs-1 cart-mobile-handle border relative">
      <div class="badge cart_count"></div>
     <a href="javascript:">
       <i class="ion-ios-cart"></i>
     </a>
  </div> <!--cart-mobile-handle-->
  <?php endif;?>
  
  
  <div class="col-md-9 border col-b mobile-lang">
  <div class="lang_top-menu mobile-top-menu">

  <?php
//         Widgets::languageBar("store",true);
    ?>

    <?php $this->widget('zii.widgets.CMenu', FunctionsV3::getMenu() );?>

        </div>
    <div class="clear"></div>
  </div>




</div> <!--container-->

</div> <!--END top-menu-->
</div>
</nav>


<div class="menu-top-menu" style="background:#fff0cd">
    <?php $this->widget('zii.widgets.CMenu', FunctionsV3::getMenu('mobile-menu') );?>
    <div class="clear"></div>
</div> <!--menu-top-menu-->
<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>-->
<script>
$(document).ready(function(){

/**
 * This object controls the nav bar. Implement the add and remove
 * action over the elements of the nav bar that we want to change.
 *
 * @type {{flagAdd: boolean, elements: string[], add: Function, remove: Function}}
 */
var myNavBar = {

    flagAdd: true,

    elements: [],

    init: function (elements) {
        this.elements = elements;
    },

    add : function() {
        if(this.flagAdd) {
            for(var i=0; i < this.elements.length; i++) {
                document.getElementById(this.elements[i]).className += " fixed-theme";
            }
            this.flagAdd = false;
        }
    },

    remove: function() {
        for(var i=0; i < this.elements.length; i++) {
            document.getElementById(this.elements[i]).className =
                    document.getElementById(this.elements[i]).className.replace( /(?:^|\s)fixed-theme(?!\S)/g , '' );
        }
        this.flagAdd = true;
    }

};

/**
 * Init the object. Pass the object the array of elements
 * that we want to change when the scroll goes down
 */
myNavBar.init(  [
    "header",
    //"header-container",
    //"brand"
]);

/**
 * Function that manage the direction
 * of the scroll
 */
function offSetManager(){

    var yOffset = 0;
    var currYOffSet = window.pageYOffset;

    if(yOffset < currYOffSet) {
        myNavBar.add();
    }
    else if(currYOffSet == yOffset){
        myNavBar.remove();
    }

}

/**
 * bind to the document scroll detection
 */
window.onscroll = function(e) {
    offSetManager();
}

/**
 * We have to do a first detectation of offset because the page
 * could be load with scroll down set.
 */
offSetManager();
});
</script>