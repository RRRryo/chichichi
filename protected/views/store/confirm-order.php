<?php
$this->renderPartial('/front/default-header', array(
    'h1' => t("Payment Option"),
    'sub_text' => "接受订单"
));
?>
<div class="container">
  <div class="top25">
    <p><?php echo $data?></p>
  </div>

</div>