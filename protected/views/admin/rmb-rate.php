<form class="uk-form uk-form-horizontal forms" id="forms">
<?php echo CHtml::hiddenField('action','addRMBrate')?>
<?php
$euro_amount= Yii::app()->functions->getOptionAdmin('euro_amount');
$rmb_amount= Yii::app()->functions->getOptionAdmin('rmb_amount');
?>
<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Euro Amount")?></label>
  <?php 
  echo CHtml::textField('euro_amount',
  isset($euro_amount)?$euro_amount:""
  ,array('class'=>"uk-form-width-large",'data-validation'=>"required" ,'readonly'=>'true'))
  ?>
</div>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","RMB Amount")?></label>
  <?php 
  echo CHtml::textField('rmb_amount',
  isset($rmb_amount)?$rmb_amount:""
  ,array('class'=>"uk-form-width-large",'data-validation'=>"required"))
  ?>
</div>

<div class="uk-form-row">
<label class="uk-form-label"></label>
<input type="submit" value="<?php echo Yii::t("default","Save")?>" class="uk-button uk-form-width-medium uk-button-success">
</div>

</form>