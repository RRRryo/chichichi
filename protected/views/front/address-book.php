
<div class="box-grey rounded section-address-book" style="margin-top:0;">

<?php
$do=isset($_GET['do'])?$_GET['do']:'';
?>

<?php if ( $do==="add" && $tabs==2 ) :?>

<form id="frm-addressbook" class="frm-addressbook" onsubmit="return false;">
<?php echo CHtml::hiddenField('action','addAddressBook')?>
<?php echo CHtml::hiddenField('currentController','store')?>  
<?php if (isset($_GET['id'])):?>
<?php echo CHtml::hiddenField('id',$_GET['id'])?>
<?php else :?>
<?php echo CHtml::hiddenField('redirect',createUrl("/store/profile/tab/2/do/add"))?>
<?php endif;?>

<div class="row bottom10">
  <div class="col-md-6">
    <p class="text-small"><?php echo t("Address")?></p>
    <?php 
	  echo CHtml::textField('client_address',
      isset($data['street'])?$data['street']:''
      ,array(
       'class'=>'grey-fields full-width',
       'data-validation'=>"required"  
      ))?>	  
  </div>
    <div class="col-md-6">
        <p class="text-small"><?php echo t("Location Name")?></p>
        <?php echo CHtml::textField('location_name',
            isset($data['location_name'])?$data['location_name']:''
            ,array(
                'class'=>'grey-fields full-width',
            ))?>
    </div>
</div> <!--row-->



<div class="row bottom10">
<div class="col-md-6">
<?php 
      echo CHtml::checkBox('as_default',
      $data['as_default']==2?true:false
      ,array('class'=>"icheck",'value'=>2));
      echo " ".t("Default");
      ?>
</div>
</div>

<div class="row top10">
  <div class="col-md-2">
  <input type="submit" value="<?php echo t("Submit")?>" class="green-button medium inline">
  </div>
  <div class="col-md-5">
    <a class="green-text top10 block" href="<?php echo Yii::app()->createUrl('/store/profile/?tab=2')?>">
	<i class="ion-ios-arrow-thin-left"></i> <?php echo t("Back")?>
	</a>
  </div>
</div>

</form>

<?php else :?>

<div class="bottom10 top10">
<!--<a class="green-button inline rounded" href="<?php /*echo Yii::app()->createUrl('/store/profile/?tab=2&do=add')*/?>">
<?php /*echo t("Add New")*/?>
</a>-->
</div>

<form id="frm_table_list" method="POST" >
<input type="hidden" name="action" id="action" value="addressBook">
<?php echo CHtml::hiddenField('currentController','store')?>

<input type="hidden" name="tbl" id="tbl" value="address_book">
<input type="hidden" name="clear_tbl"  id="clear_tbl" value="clear_tbl">
<input type="hidden" name="whereid"  id="whereid" value="id">
<input type="hidden" name="slug" id="slug" value="store/addressbook">

<table id="table_list" class="table table-striped">
  <thead>
  <tr>
   <th width="40%" ><?php echo Yii::t("default","Address")?></th>
   <th width="40%"><?php echo Yii::t("default","Location Name")?></th>
   <th width="10%"><?php echo Yii::t("default","Default")?></th>
  </tr>
  </thead>
</table>  
<div class="clear"></div>
</form>

<?php endif;?>

</div> <!--box-grey-->


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