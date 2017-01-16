
<div class="uk-width-1">
<a href="<?php echo Yii::app()->request->baseUrl; ?>/admin/city/Do/Add" class="uk-button"><i class="fa fa-plus"></i> <?php echo Yii::t("default","Add New")?></a>
<a href="<?php echo Yii::app()->request->baseUrl; ?>/admin/city" class="uk-button"><i class="fa fa-list"></i> <?php echo Yii::t("default","List")?></a>
</div>

<form id="frm_table_list" method="POST" >
<input type="hidden" name="action" id="action" value="listCity">
<input type="hidden" name="tbl" id="tbl" value="city">
<input type="hidden" name="clear_tbl"  id="clear_tbl" value="clear_tbl" /> 
<input type="hidden" name="whereid"  id="whereid" value="id">
<input type="hidden" name="slug" id="slug" value="city/Do/Add">
<table id="table_list" class="uk-table uk-table-hover uk-table-striped uk-table-condensed">
  <!--<caption>Merchant List</caption>-->
   <thead>
        <tr>
			 <th width="5%"><?php echo Yii::t('default',"ID")?></th>			 		
			 <th width="5%"><?php echo Yii::t('default',"City")?></th>
             <th width="5%"><?php echo Yii::t('default',"Date Created")?></th>
        </tr>
    </thead>
    <tbody> 
   <!-- < ?php 
	$stmt="SELECT id,city_name,date_created from {{city}}";   
			$connection=Yii::app()->db;
    	    $rows=$connection->createCommand($stmt)->queryAll();     	        	        	        	    
		  if ($rows){
    	    	foreach ($rows as $ket => $val) {  
				 $date=FormatDateTime($val['date_created']); ?>
				  <tr>
                     <th width="5%">< ?php echo $val['id']; ?></th>			 		
                     <th width="5%">< ?php echo $val['city_name'];?></th>
                     <th width="5%">< ?php echo $date;?></th>
                  </tr>
				< ?php  
				}
			}
	?>  -->
    </tbody>
</table>
<div class="clear"></div>
</form>

<div>

</div>