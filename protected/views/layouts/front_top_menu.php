<div class="header">
<nav class="navbar navbar-default navbar-modify" role="navigation">
    <div class="container-fluid container-limit">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle navbar-xy" data-toggle="collapse"
                    data-target="#example-navbar-collapse">
                <span class="sr-only">切换导航</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand navbar-pic" href="<?= Yii::app()->createUrl('/store/home')?>">
                <img src="<?=assetsURL()."/images/header/logo/jinwei_logo.png" ?>" alt="" class="img-responsive img-pic" />
            </a>
        </div>
        <div class="collapse navbar-collapse" id="example-navbar-collapse">
            <ul class="nav navbar-nav">

                <li><a href="<?= Yii::app()->createUrl('/store/home')?>">主页</a></li>
                <li><a href="<?= Yii::app()->createUrl('/store/browse')?>">浏览餐厅</a></li>
                <li><a href="<?= Yii::app()->createUrl('/store/merchantsignupselection')?>">餐厅注册</a></li>
                <li><a href="<?= Yii::app()->createUrl('/store/signup')?>">登录和注册</a></li>

            </ul>

        </div>
        <div class="pull-right soso ">
            <form class="bs-example bs-example-form"  id="forms-search" role="form"  action="<?php echo Yii::app()->createUrl('store/searcharea')?>">
                <div class="input-group soso-input-group">
                    <span class="input-group-addon span-input-group">
                        <img src="<?=assetsURL()."/images/header/soso/soso.png" ?>" class="" alt="" style="max-width: none"/>
                    </span>
                    <input type="text" name="s" id="s" autocomplete="off" required class="form-control input-form-control search-input" placeholder="请输入地址搜索附近美食">
                    <input type="submit" style="display:none"/>
                </div>
                <br>
            </form>
        </div>
    </div>
</nav>

</div>
<script>
    document.getElementById('s').onkeydown = function(e){
        if(e.keyCode == 13){
            // submit
            this.form.submit();
        }
    };
</script>