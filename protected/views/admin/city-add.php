
<div class="uk-width-1">
<a href="<?php echo Yii::app()->request->baseUrl; ?>/admin/city/Do/Add" class="uk-button"><i class="fa fa-plus"></i> <?php echo Yii::t("default","Add New")?></a>
<a href="<?php echo Yii::app()->request->baseUrl; ?>/admin/city" class="uk-button"><i class="fa fa-list"></i> <?php echo Yii::t("default","List")?></a>
</div>

<form class="uk-form uk-form-horizontal forms" id="forms">

<?php echo CHtml::hiddenField('action','addCity')?>
<?php echo CHtml::hiddenField('id',isset($_GET['id'])?$_GET['id']:"");?>
<?php if (!isset($_GET['id'])):?>
<?php echo CHtml::hiddenField("redirect",Yii::app()->request->baseUrl."/admin/city/Do/Add")?>
<?php endif;?>
<?php 
if (isset($_GET['id'])){
	if (!$data=Yii::app()->functions->getCity($_GET['id'])){
		echo "<div class=\"uk-alert uk-alert-danger\">".
		Yii::t("default","Sorry but we cannot find what your are looking for.")."</div>";
		return ;
	} 	
	
}
?>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","City Name")?></label>
  <?php echo CHtml::textField('city_name',$data['city_name'],
  array(
    'data-validation'=>'required' ,
    'class'=>"city_name"
  ))?>
</div>
<div class="uk-form-row">
<label class="uk-form-label"></label>
<input type="submit" value="<?php echo Yii::t("default","Add City")?>" class="uk-button uk-form-width-medium uk-button-success">
</div>

</form>