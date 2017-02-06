
<div class="search-wraps single-search">

  <h1><?php echo $home_search_text;?></h1>
<!--  <p>--><?php //echo $home_search_subtext;?><!--</p>-->

  <form method="GET" class="forms-search" id="forms-search" action="<?php echo Yii::app()->createUrl('store/searcharea')?>">
  
  <div class="search-input-wraps rounded30">
     <div class="row">
     
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
    $(".toggel-click").click(function(){
        $(".list-link").toggle();
    });
});
function getcityvalue(id)
{

	 var cityname = $("#cityval-"+id).html(); 
	  $("#s").val(cityname); 
	   $(".list-link").hide();

}

</script>
<?php $city_name =Yii::app()->functions->getCity(); 

?>
     	
     	<div class="search-field">
        &nbsp;
        	<!--<div class="search-select">
            	<a href="javascript:void(0)" class="toggel-click">城市</a>
               
            </div>-->
        	<div class="search-input" style="width: 85%"><?php echo CHtml::textField('s',$kr_search_adrress,array(
         'placeholder'=>$placholder_search,
        'required'=>true
        ))?>  </div>
            <div class="search-btn" style="float:right;"><button type="submit"><i class="ion-ios-search"></i></button> </div>
        </div>
        
               <ul class="list-link">
               <?php
			   $i = 0;
               foreach($city_name as $key => $city) { 
			  
			   $city = $city['city_name'];
			   
			   ?>
                	<li id="<?php echo $city;?>" class="city" ><a href="javascript:getcityvalue(<?php echo $i; ?>)" title="" class="link cityidd cityval-<?php echo $i; ?>" id="cityval-<?php echo $i; ?>"><?php echo $city;?></a></li>
		       <?php $i++; 
			    } ?>
			   
                </ul>
     
        <!--<div class=" border col-sm-11 col-xs-10">
        < ?php echo CHtml::textField('s',$kr_search_adrress,array(
         placeholder'=>$placholder_search,
        required'=>true
        ))?>       
        </div>        
        <div class=" relative border col-sm-1 col-xs-2">
          <button type="submit"><i class="ion-ios-search"></i></button>         
        </div>-->
     </div>
  </div> <!--search-input-wrap-->
  </form>
  
</div> <!--search-wrapper-->