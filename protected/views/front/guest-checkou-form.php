<div class="row top10">
<div class="col-md-10">
  <?php echo CHtml::textField('first_name','',array(
   'class'=>'grey-fields full-width',
   'placeholder'=>Yii::t("default","First Name"),
   'data-validation'=>"required"
  ))?>
 </div> 
</div>

<div class="row top10">
<div class="col-md-10">
  <?php echo CHtml::textField('last_name','',array(
   'class'=>'grey-fields full-width',
   'placeholder'=>Yii::t("default","Last Name"),
   'data-validation'=>"required"
  ))?> 
 </div> 
</div>

<div class="row top10">
<div class="col-md-10">
<?php echo CHtml::textField('client_address',isset($client_info['client_address']),array(
   'class'=>'grey-fields full-width',
   'placeholder'=>Yii::t("default","Street"),
   'data-validation'=>"required"
  ))?> 
</div> 
</div>

<div class="row top10">
<div class="col-md-10">
<?php echo CHtml::textField('location_name',isset($client_info['location_name']),array(
   'class'=>'grey-fields full-width',
   'placeholder'=>Yii::t("default","Apartment suite, unit number, or company name"),   
  ))?>
</div> 
</div>

<div class="row top10">
<div class="col-md-10">
<?php echo CHtml::textField('contact_phone',isset($client_info['contact_phone']),array(
   'class'=>'grey-fields mobile_inputs full-width',
   'placeholder'=>Yii::t("default","Mobile Number"),
   'data-validation'=>"required"  
  ))?> 
</div> 
</div>

<div class="row top10">
<div class="col-md-10">
<?php echo CHtml::textField('delivery_instruction','',array(
  'class'=>'grey-fields full-width',
  'placeholder'=>Yii::t("default","Delivery instructions")   
))?> 
</div> 
</div>

<div class="row top10">
<div class="col-md-10">
<?php echo CHtml::textField('email_address','',array(
   'class'=>'grey-fields full-width',
   'placeholder'=>Yii::t("default","Email address"),   
  ))?> 
</div> 
</div>

<?php FunctionsV3::sectionHeader('Create Account')?>		  
<p class="text-muted text-small">***<?php echo t("Optional")?></p>

<div class="row top10">
<div class="col-md-10">
   <?php echo CHtml::passwordField('password','',array(
   'class'=>'grey-fields full-width',
   'placeholder'=>Yii::t("default","Password"),   
  ))?>
 </div> 
</div>


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