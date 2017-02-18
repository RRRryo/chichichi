
<div class="container enter-address-wrap">

<div class="section-label">
    <a class="section-label-a">
      <span class="bold">
      <?php echo t("Enter the metro station below")?></span>
      <b></b>
    </a>     
</div>  

<form id="frm-modal-enter-metro" class="frm-modal-enter-metro" method="POST" onsubmit="return false;" >
<?php echo CHtml::hiddenField('action','setMetro');?>
<?php echo CHtml::hiddenField('web_session_id',
isset($this->data['web_session_id'])?$this->data['web_session_id']:''
);?>

<div class="row">
  <div class="col-md-12 ">
    <?php echo CHtml::textField('client_metro',
	 isset($_SESSION['kr_search_address'])?$_SESSION['kr_search_address']:''
	 ,array(
	 'class'=>"grey-inputs",
	 'data-validation'=>"required"
	 ))?>
  </div> 
</div> <!--row-->

<div class="row food-item-actions top10">
  <div class="col-md-5 "></div>
  <div class="col-md-3 ">
  <a href="javascript:$.fancybox.close();" class="orange-button inline center"><?php echo t("Close")?></a>
  </div>
  <div class="col-md-3 ">
     <input type="submit" class="green-button inline" style="line-height: inherit" value="<?php echo t("Submit")?>">
  </div>
</div>

 </form>

</div> <!--container-->

<script type="text/javascript">
$.validate({ 	
	language : jsLanguageValidator,
    form : '#frm-modal-enter-metro',
    onError : function() {      
    },
    onSuccess : function() {     
      form_submit('frm-modal-enter-metro');
      return false;
    }  
})

jQuery(document).ready(function() {
	var google_auto_address= $("#google_auto_address").val();	
	if ( google_auto_address =="yes") {		
	} else {
		$("#client_metro").geocomplete({
		    country: $("#admin_country_set").val()
		});	
	}
});
</script>
<?php
die();