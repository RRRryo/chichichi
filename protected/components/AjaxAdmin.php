<?php

/*******************************************

@author : bastikikang 

@author email: basti@codemywebapps.com

@author website : http://codemywebapps.com

*******************************************/



if (!isset($_SESSION)) { session_start(); }
if (!class_exists('AjaxAdmin'))

{

	class AjaxAdmin extends DbExt

	{

		public $data;

		public $code=2;

		public $msg;

		public $details;

		

		public function __construct()

		{

			// set website timezone

			$website_timezone=Yii::app()->functions->getOptionAdmin("website_timezone");		 

		    if (!empty($website_timezone)){

		 	   Yii::app()->timeZone=$website_timezone;

		    }		 

			

			$mtid=Yii::app()->functions->getMerchantID();		 		    

		    $mt_timezone=Yii::app()->functions->getOption("merchant_timezone",$mtid);	   	   	    	

    	    if (!empty($mt_timezone)){    	 	
    		   Yii::app()->timeZone=$mt_timezone;
    	    }		     	
		}	

		

		public function otableNodata()

		{

			if (isset($_GET['sEcho'])){

				$feed_data['sEcho']=$_GET['sEcho'];

			} else $feed_data['sEcho']=1;	   

			     

	        $feed_data['iTotalRecords']=0;

	        $feed_data['iTotalDisplayRecords']=0;

	        $feed_data['aaData']=array();		

	        echo json_encode($feed_data);

	    	die();

		}

    

		public function otableOutput($feed_data='')

		{

    	  echo json_encode($feed_data);

    	  die();

        }

    

		public function output($debug=FALSE)

		{

    	    $resp=array('code'=>$this->code,'msg'=>$this->msg,'details'=>$this->details);

    	    if ($debug){

    		    dump($resp);

    	    }

    	    return json_encode($resp);    	    

		}

		

		public function login()

		{			



			/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			}  			

			

            /** check if admin has enabled the google captcha*/    	    	

	    	if ( getOptionA('captcha_admin_login')==2){

	    		if ( GoogleCaptcha::checkCredentials()){

	    			if ( !GoogleCaptcha::validateCaptcha()){

	    				$this->msg=GoogleCaptcha::$message;

	    				return false;

	    			}	    		

	    		}	    	

	    	} 

	    	

			$DbExt=new DbExt;

			$stmt="SELECT * FROM

			       {{admin_user}}

			       WHERE

			       username=".Yii::app()->db->quoteValue($this->data['username'])."

			       AND

			       password=".Yii::app()->db->quoteValue(md5($this->data['password']))."

			       LIMIT 0,1

			";

			if ( $res=$DbExt->rst($stmt)){								

				//Yii::app()->request->cookies['kr_user'] = new CHttpCookie('kr_user', json_encode($res));  		

				

				//dump($res);

				$_SESSION['kr_user']=json_encode($res);

				//dump($_SESSION['kr_user']);

				$this->code=1;												

				

				$session_token=Yii::app()->functions->generateRandomKey().md5($_SERVER['REMOTE_ADDR']);				

				$params=array(

				  'session_token'=>$session_token,

				  'last_login'=>date('c')

				);

				$this->updateData("{{admin_user}}",$params,'admin_id',$res[0]['admin_id']);

								

				$_SESSION['kr_user_session']=$session_token;			

				

	    		$this->msg=Yii::t("default","Login Successful");

			} else $this->msg=Yii::t("default","Either username or password is invalid.");

		}

		

		public function addMerchant()

		{			

			if (empty($this->data['id'])){				

				if ( empty($this->data['username']) && empty($this->data['password'])){

					$this->msg=Yii::t("default","username & password is required");

					return ;

				} else {

					$params['username']=$this->data['username'];

					$params['password']=md5($this->data['password']);

				}

			} else {

				if (!empty($this->data['password'])){

					$params['username']=$this->data['username'];

					$params['password']=md5($this->data['password']);

				}

			}					



			

			if (empty($this->data['id'])){					

			    if (Yii::app()->functions->isMerchantExist($this->data['contact_email'])){

					$this->msg=Yii::t("default","Sorry you input email address that is already registered in our records.");

					return ;

				}		

				if (!isset($this->data['package_id'])){

					$this->msg=Yii::t("default","ERROR: Missing package id");

					return ;

				}	

				

				if ( !$package=Yii::app()->functions->getPackagesById($this->data['package_id'])){

					$this->msg=Yii::t("default","ERROR: Package information not found");

					return ;

				}		

								

				if ( $t=Yii::app()->functions->validateUsername($this->data['username']) ){				

					$this->msg=Yii::t("default","Merchant Username is already been taken");

					return ;

				}			

			 } else {

			 	 if ( !empty($this->data['password'])){

			 	 if ( Yii::app()->functions->validateUsername($this->data['username'],$this->data['id']) ){

			 	 	$this->msg=Yii::t("default","Merchant Username is already been taken");

					return ;

			 	 }			 

			 	 }


				 if ( Yii::app()->functions->validateMerchantEmail($this->data['contact_email'],$this->data['id']) ){

				     $this->msg=Yii::t("default","Merchant Email address is already been taken");

				      return ;

			     }	    

			 }		



			if (!empty($this->data['restaurant_slug'])){

				$params['restaurant_slug']=FunctionsV3::verifyMerchantSlug(

				  Yii::app()->functions->seo_friendly_url($this->data['restaurant_slug']),

				  $this->data['id']

				);

			} else {	

			    $params['restaurant_slug']=Yii::app()->functions->createSlug($this->data['restaurant_name']);

			}

			$params['restaurant_name']=addslashes($this->data['restaurant_name']);

			$params['restaurant_phone']=addslashes($this->data['restaurant_phone']);

			$params['contact_name']=addslashes($this->data['contact_name']);

			$params['contact_phone']=$this->data['contact_phone'];

			$params['contact_email']=$this->data['contact_email'];

			$params['country_code']=$this->data['country_code'];

			$params['country_code']=Yii::app()->functions->adminSetCounryCode();

			$params['street']=$this->data['street'];

			$params['city']=$this->data['city'];

			$params['post_code']=$this->data['post_code'];

			$params['cuisine']=json_encode($this->data['cuisine']);

			$params['service']=$this->data['service'];

			$params['status']=$this->data['status'];

		    $params['date_created']=date('c');

		    $params['ip_address']=$_SERVER['REMOTE_ADDR'];

		    $params['membership_expired']=isset($this->data['membership_expired'])?$this->data['membership_expired']:'';

		    $params['is_featured']=isset($this->data['is_featured'])?$this->data['is_featured']:1;

		    $params['package_id']=isset($this->data['package_id'])?$this->data['package_id']:"";

		    

		    $params['state']=isset($this->data['state'])?$this->data['state']:'';		    

		    $params['is_commission']=isset($this->data['is_commission'])?$this->data['is_commission']:1;

		    $params['percent_commision']=isset($this->data['percent_commision'])?$this->data['percent_commision']:0;

		    

		    $params['abn']=isset($this->data['abn'])?$this->data['abn']:'';		    

		    		    

		    $params['commision_type']=isset($this->data['commision_type'])?$this->data['commision_type']:'';		    

  	    

		    if (isset($this->data['package_id'])){

		    	if ($package=Yii::app()->functions->getPackagesById($this->data['package_id'])){		    		

		    		if ($package['promo_price']>=1){

		    			$params['package_price']=$package['promo_price'];

		    		} else $params['package_price']=$package['price'];		    	

		    	}		    

		    }		

		   

		    $params['is_ready']=isset($this->data['is_ready'])?$this->data['is_ready']:1;

		    

		    if (empty($this->data['id'])){	

		    	if ( $this->insertData("{{merchant}}",$params)){

		    		$this->details=Yii::app()->db->getLastInsertID();

		    		/*special category*/

		    		//ClassCategory::autoInsertCategory($this->details);		    		

		    		$this->code=1;

		    		$this->msg=Yii::t("default","Successful");		    		
		    		
		    		$mtid=$this->details;

	                Yii::app()->functions->updateOption("merchant_switch_master_cod",

	    	        isset($this->data['merchant_switch_master_cod'])?$this->data['merchant_switch_master_cod']:''

	    	        ,$mtid);

	    	        

	    	        Yii::app()->functions->updateOption("merchant_switch_master_ccr",

	    	        isset($this->data['merchant_switch_master_ccr'])?$this->data['merchant_switch_master_ccr']:''

	    	        ,$mtid);

	    	        

	    	        Yii::app()->functions->updateOption("merchant_switch_master_pyr",

	    	        isset($this->data['merchant_switch_master_pyr'])?$this->data['merchant_switch_master_pyr']:''

	    	        ,$mtid);

	    	        

				    Yii::app()->functions->updateOption("merchant_latitude",

			    	isset($this->data['merchant_latitude'])?$this->data['merchant_latitude']:''

			    	,$mtid);

			    	

			    	Yii::app()->functions->updateOption("merchant_longtitude",

			    	isset($this->data['merchant_longtitude'])?$this->data['merchant_longtitude']:''

			    	,$mtid);

	    	        	    	        

		    	}

		    } else {		    	

		    	unset($params['date_created']);

				$params['date_modified']=date('c');		

				$params['restaurant_slug']=FunctionsV3::verifyMerchantSlug($params['restaurant_slug'],$this->data['id']);

						

				$res = $this->updateData('{{merchant}}' , $params ,'merchant_id',$this->data['id']);

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Merchant updated.');  

	                

	                $mtid=$this->data['id'];

	                Yii::app()->functions->updateOption("merchant_switch_master_cod",

	    	        isset($this->data['merchant_switch_master_cod'])?$this->data['merchant_switch_master_cod']:''

	    	        ,$mtid);

	    	        

	    	        Yii::app()->functions->updateOption("merchant_switch_master_ccr",

	    	        isset($this->data['merchant_switch_master_ccr'])?$this->data['merchant_switch_master_ccr']:''

	    	        ,$mtid);

	    	        

	    	        Yii::app()->functions->updateOption("merchant_switch_master_pyr",

	    	        isset($this->data['merchant_switch_master_pyr'])?$this->data['merchant_switch_master_pyr']:''

	    	        ,$mtid);

	    	        

	    	         Yii::app()->functions->updateOption("merchant_latitude",

			    	isset($this->data['merchant_latitude'])?$this->data['merchant_latitude']:''

			    	,$this->data['id']);

			    	

			    	Yii::app()->functions->updateOption("merchant_longtitude",

			    	isset($this->data['merchant_longtitude'])?$this->data['merchant_longtitude']:''

			    	,$this->data['id']);

	                	    	        

				} else $this->msg=Yii::t("default","ERROR: cannot update");

		    }	

		}

		

		public function merchantListx()

		{

			$slug=$this->data['slug'];

			$stmt="SELECT a.*,

			(

			select title

			from

			{{packages}}

			where

			package_id = a.package_id

			) as package_name

			

			 FROM

			{{merchant}} a	

			ORDER BY merchant_id DESC

			";

			if ( $res=$this->rst($stmt)){

				foreach ($res as $val) {	

					$date=date('M d,Y G:i:s',strtotime($val['date_created']));

					$date=Yii::app()->functions->translateDate($date);

					

					$action="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[merchant_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[merchant_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

					

					$val['package_name']=isset($val['package_name'])?$val['package_name']:'';

					

					if ($val['status']=="expired"){

					   $class='uk-badge-danger';

					} elseif ( $val['status']=="pending"){

						$class='';

					} elseif ($val['status']=="active"){

						$class='uk-badge-success';

					}				

					$membershipdate=prettyDate($val['membership_expired']);

					$membershipdate=Yii::app()->functions->translateDate($membershipdate);					

					

					$url_login=baseUrl()."/merchant/autologin/id/".$val['merchant_id']."/token/".$val['password'];

					$link_login='<br/><br/>

					<a target="_blank" href="'.$url_login.'"><div class="uk-badge">'.t("AutoLogin").'</div></a>

					';

					

					$feed_data['aaData'][]=array(

					  $val['merchant_id'],stripslashes($val['restaurant_name']).$action,

					  $val['street'],

					  $val['city'],

					  $val['country_code'],

					  $val['restaurant_phone']." / ".$val['contact_phone'],

					  $val['package_name']."<br/>".$membershipdate,

					  $val['activation_key'],

					  membershipType($val['is_commission']),

					  $date."<br/><div class=\"uk-badge $class\">".strtoupper(Yii::t("default",$val['status']))."</div>".$link_login

					);

				}

				$this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function rowDelete()

		{			

			if (!isset($this->data['tbl']))

			{	

				$this->msg=Yii::t("default","Missing parameters");

				return ;		 				 

			}			

												

			if ($this->data['tbl']=="merchantSponsoredList"){

				$params=array('is_sponsored'=>1,'date_modified'=>date('c'));

				if ($this->updateData("{{merchant}}",$params,'merchant_id',$this->data['row_id'])){

					$this->code=1;

					$this->msg=Yii::t("default","Successfully remove.");

				} else $this->msg=Yii::t("default","ERROR: cannot execute query.");			

				return ;

			}		

						

			if ($this->data['tbl']=="merchant"){

				$functionk=new FunctionsK();

				if ( $functionk->getMerchantOrders($this->data['row_id'])){

					$this->msg=t("Sorry but you cannot delete this merchant it has reference on order tables");

					return ;

				}			

			}

			

			$whereid=$this->data['whereid'];

			$tbl=Yii::app()->db->tablePrefix.$this->data['tbl'];

			$query = "DElETE FROM $tbl WHERE $whereid=". Yii::app()->db->quoteValue($this->data['row_id']) ." "; 

			if (Yii::app()->db->createCommand($query)->query()){

			     $this->msg=Yii::t("default","Successfully deleted.");

                 $this->code=1;	        

                 

                 if ($this->data['tbl']=="merchant"){

                 	$stmt_del="DELETE  FROM {{option}}

                 	WHERE

                 	merchant_id=".Yii::app()->db->quoteValue($this->data['row_id'])."

                 	 ";

                 	if ( $this->data['row_id'] >=1){

                 	    Yii::app()->db->createCommand($stmt_del)->query();

                 	}

                 }			

                 

                 /*special category*/

				 if($this->data['tbl']=="category"){

					//ClassCategory::deleteCategory($this->data['row_id']);

				 }	

                             			 

			} else $this->msg=Yii::t("default","ERROR: cannot execute query.");

		}

		

		public function merchantLogin()

		{			

			

            /** check if admin has enabled the google captcha*/    	    	

	    	if ( getOptionA('captcha_merchant_login')==2){

	    		if ( GoogleCaptcha::checkCredentials()){

	    			if ( !GoogleCaptcha::validateCaptcha()){

	    				$this->msg=GoogleCaptcha::$message;

	    				return false;

	    			}	    		

	    		}	    	

	    	} 

			

			Yii::app()->functions->updateMerchantSponsored();

		    Yii::app()->functions->updateMerchantExpired();

		

			$stmt="SELECT * FROM

			       {{merchant}}

			       WHERE

			       username=".Yii::app()->db->quoteValue($this->data['username'])."

			       AND

			       password=".Yii::app()->db->quoteValue(md5($this->data['password']))."

			       LIMIT 0,1

			";							

			if ( $res=$this->rst($stmt)){	

				if ($res[0]['status']=="active" || $res[0]['status']=="expired"){

					//Yii::app()->request->cookies['kr_merchant_user'] = new CHttpCookie('kr_merchant_user', json_encode($res));

					$_SESSION['kr_merchant_user']=json_encode($res);

										

                     $session_token=Yii::app()->functions->generateRandomKey().md5($_SERVER['REMOTE_ADDR']);				

					 $params=array(

					  'session_token'=>$session_token,

					  'last_login'=>date('c')

					 );

					 $this->updateData("{{merchant}}",$params,'merchant_id',$res[0]['merchant_id']);

					 

					 $_SESSION['kr_merchant_user_session']=$session_token;

					 $_SESSION['kr_merchant_user_type']='admin';

					

					$this->code=1;

					$this->code=1;

		    		$this->msg=Yii::t("default","Login Successful");

				} else $this->msg=Yii::t("default","Login Failed. You account status is ".$res[0]['status']);

			} else {

				//$this->msg=Yii::t("default","Either username or password is invalid.");

				$this->merchantUserLogin();

			}		

		}

		

		public function merchantUserLogin()

		{

		   $stmt="SELECT a.*,

		          (

		            select restaurant_name

		            from

		            {{merchant}}

		            where

		            merchant_id=a.merchant_id

		          ) as restaurant_name,

		          (

		            select restaurant_slug

		            from

		            {{merchant}}

		            where

		            merchant_id=a.merchant_id

		          ) as restaurant_slug

		          

		           FROM

			       {{merchant_user}} a

			       WHERE

			       username=".Yii::app()->db->quoteValue($this->data['username'])."

			       AND

			       password=".Yii::app()->db->quoteValue(md5($this->data['password']))."

			       AND

			       status='active'

			       LIMIT 0,1

			";				   

			if ( $res=$this->rst($stmt)){	

				//dump($res);

				if ($res[0]['status']=="active" || $res[0]['status']=="expired"){

					//Yii::app()->request->cookies['kr_merchant_user'] = new CHttpCookie('kr_merchant_user', json_encode($res));					

					$mt_id=$res[0]['merchant_id'];					

					if ($merchant_info=Yii::app()->functions->getMerchant($mt_id)){						

						$res[0]['is_commission']=$merchant_info['is_commission'];

					}				

					

					$_SESSION['kr_merchant_user']=json_encode($res);

					

					$this->code=1;					

		    		$this->msg=Yii::t("default","Login Successful");

		    		

		    		 $session_token=Yii::app()->functions->generateRandomKey().md5($_SERVER['REMOTE_ADDR']);						 							    		 

					 $_SESSION['kr_merchant_user_session']=$session_token;

					 $_SESSION['kr_merchant_user_type']='merchant_user';

		    		

		    		$params=array(

		    		  'last_login'=>date('c'),

		    	 	  'ip_address'=>$_SERVER['REMOTE_ADDR'],

		    		  'session_token'=>$session_token

		    		);

		    		$this->updateData("{{merchant_user}}",$params,'merchant_user_id',$res[0]['merchant_user_id']);

		    		

				} else $this->msg=Yii::t("default","Login Failed. You account status is ".$res[0]['status']);

			} else $this->msg=Yii::t("default","Either username or password is invalid.");

		}

		

	    public function categoryList()

		{

			$mtid=Yii::app()->functions->getMerchantID();

			$slug=$this->data['slug'];

			$stmt="

			SELECT * FROM

			{{category}}

			WHERE

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY cat_id DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	 

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[cat_id]\" class=\"chk_child\" >";   		

    	    		$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[cat_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[cat_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));    	    		

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		

    	    		if (!empty($val['photo'])){

    	    			$img=Yii::app()->request->baseUrl."/upload/$val[photo]";

    	    		    $photo="<img class=\"uk-thumbnail uk-thumbnail-mini\" src=\"$img\" >";	

    	    		} else $photo='';

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,stripslashes($val['category_name']).$option,

    	    		  stripslashes($val['category_description']),

    	    		  $photo,

    	    		  Widgets::displaySpicyIconNew($val['dish']),

    	    		  $date."<div>".Yii::t("default",$val['status'])."</div>"

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();

		}

		

	    public function uploadImage()

	    {	    	

	    	$qqfile=$_GET['qqfile'];

	    	if (preg_match("/.php/i", $qqfile)) {

	    		if (isset($_GET['currentController'])){

	    			if ($_GET['currentController']!="admin"){

	    				$this->msg=Yii::t("default","Invalid file");

	    				return ;

	    			} else {

	    				// check language file if has errors

	    			}	    		

	    		}	    	

	    	}

	    	

	    	$path_to_upload=Yii::getPathOfAlias('webroot')."/upload/";	    		    	

		    if(!file_exists($path_to_upload)) {	

               if (!@mkdir($path_to_upload,0777)){

               	    $this->msg=Yii::t("default","Cannot create upload folder. Please create the upload folder manually on your rood directory with 777 permission.");

               	    return ;

               }		    

		    }

		    

		    /*create htaccess file*/

		    $htaccess='<Files *>';

		    $htaccess.=PHP_EOL;

		    $htaccess.='SetHandler default-handler';

		    $htaccess.=PHP_EOL;

		    //$htaccess.='php_flag engine off';

		    $htaccess.=PHP_EOL;

		    $htaccess.='</Files>';

		    $htfile=$path_to_upload.'.htaccess';		    

		    if (!file_exists($htfile)){

		    	$myfile = fopen($htfile, "w") or die("Unable to open file!".$htfile);    

                fwrite($myfile, $htaccess);        

                fclose($myfile);

		    }	    

		    

		    if (isset($this->data['qqfile']) && !empty($this->data['qqfile'])){

		        $input = fopen("php://input", "r");

		        $temp = tmpfile();

		        $realSize = stream_copy_to_stream($input, $temp);

		

		        $pathinfo = pathinfo($this->data['qqfile']);	  		        

		        $time=time();

		        $file_name=$time."-".$pathinfo['filename'].".".$pathinfo['extension'];		        

		        $file_name=str_replace(" ","-",$file_name);

		        $path=$path_to_upload.$file_name;

		        		

		        $target = fopen($path, "w");        

		        fseek($temp, 0, SEEK_SET);

	            stream_copy_to_stream($temp, $target);

				

	            $this->code=1;

		        $this->msg=Yii::t("default","Upload Completed");

		        $this->details=array(

		           'file'=>$file_name,

		           'id'=>time().Yii::app()->functions->generateRandomKey(10)

		        );			    

	        } else $this->msg=Yii::t("default","File is empty");

	    }

		

		public function addCategory()

		{			

													

			$params=array(

			  'category_name'=>addslashes($this->data['category_name']),

			  'category_description'=>addslashes($this->data['category_description']),

			  'photo'=>isset($this->data['photo'])?addslashes($this->data['photo']):'',

			  'status'=>addslashes($this->data['status']),

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'merchant_id'=>Yii::app()->functions->getMerchantID(),

			  //'spicydish'=>isset($this->data['spicydish'])?$this->data['spicydish']:1,

			  'spicydish_notes'=>isset($this->data['spicydish_notes'])?$this->data['spicydish_notes']:'',

			  'dish'=>isset($this->data['dish'])?json_encode($this->data['dish']):''			  

			);				

			

			if (isset($this->data['category_name_trans'])){				

				if (okToDecode()){

					$params['category_name_trans']=json_encode($this->data['category_name_trans'],

					JSON_UNESCAPED_UNICODE);

				} else $params['category_name_trans']=json_encode($this->data['category_name_trans']);				

			}

			if (isset($this->data['category_description_trans'])){

				if (okToDecode()){

					$params['category_description_trans']=json_encode($this->data['category_description_trans'],

					JSON_UNESCAPED_UNICODE);

				} else $params['category_description_trans']=json_encode($this->data['category_description_trans']);

			}

						

						

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{category}}' , $params , 

				'cat_id=:cat_id' , array(':cat_id'=> addslashes($this->data['id']) ));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Category updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{category}}',$params)){

					$this->details=Yii::app()->db->getLastInsertID();	                

	                $this->code=1;

	                $this->msg=Yii::t("default",'Category added.');  	                

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}

		}

	    

	    public function AddOnCategoryList()

	    {	    

	    	$slug=$this->data['slug'];

	        $stmt="

			SELECT * FROM

			{{subcategory}}

			WHERE

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY subcat_id  DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	     	    		

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[subcat_id]\" class=\"chk_child\" >";   		

    	    		$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[subcat_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[subcat_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));  

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);    	    		

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,

    	    		  stripslashes($val['subcategory_name']).$option,

    	    		  stripslashes($val['subcategory_description']),

    	    		  $date."<br/>".t($val['status'])

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

	    }	    

	    

		public function addAddOnCategory()

		{

		    $params=array(

			  'subcategory_name'=>addslashes($this->data['subcategory_name']),

			  'subcategory_description'=>addslashes($this->data['subcategory_description']),

			  //'discount'=>addslashes($this->data['discount']),

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'merchant_id'=>Yii::app()->functions->getMerchantID(),

			  'status'=>$this->data['status']

			);				



			if (isset($this->data['subcategory_name_trans'])){

				if (okToDecode()){

				    $params['subcategory_name_trans']=json_encode($this->data['subcategory_name_trans'],

				    JSON_UNESCAPED_UNICODE);

				    $params['subcategory_description_trans']=json_encode($this->data['subcategory_description_trans'],JSON_UNESCAPED_UNICODE);

				} else {

					$params['subcategory_name_trans']=json_encode($this->data['subcategory_name_trans']);

				    $params['subcategory_description_trans']=json_encode($this->data['subcategory_description_trans']);

				}			

			}		

					

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{subcategory}}' , $params , 

				'subcat_id=:subcat_id' , array(':subcat_id'=>addslashes($this->data['id'])));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'SubCategory updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{subcategory}}',$params)){

					$this->details=Yii::app()->db->getLastInsertID();	

	                $this->code=1;

	                $this->msg=Yii::t("default",'SubCategory added.');  	                

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}

	    }

	    

	    public function AddOnItemList()

	    {

	    	$slug=$this->data['slug'];

	    	

	    	yii::app()->functions->data='list';

			$cat_list=yii::app()->functions->getSubcategory();						

			$cat='';

			

			$stmt="

			SELECT a.* 

			FROM {{subcategory_item}} a			

			WHERE

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY sub_item_id DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	        	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	 

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[sub_item_id]\" class=\"chk_child\" >";   		

    	    		$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[sub_item_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[sub_item_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		if (!empty($val['category'])){

    	    			$category=json_decode($val['category']);

    	    			if (is_array($category) && count($category)>=1){

	    	    			foreach ($category as $cat_id) {    	    				

	    	    				$cat.=$cat_list[$cat_id].",";

	                        }    	

	                        $cat=substr($cat,0,-1);

    	    			}

    	    		}    	    	    	    

    	    		

    	    		if (!empty($val['photo'])){

    	    			$img=Yii::app()->request->baseUrl."/upload/$val[photo]";

    	    		    $photo="<img src=\"$img\" class=\"uk-thumbnail uk-thumbnail-mini\" >";	

    	    		} else $photo='';

    	    				

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,$val['sub_item_name'].$option,$val['item_description'],$cat,

    	    		  yii::app()->functions->getCurrencyCode().prettyFormat($val['price']),$photo,$date."<div>".Yii::t("default",$val['status'])."</div>"

    	    		);

    	    		$cat='';

    	    	}    	    	    	    	

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();

	    }	

	    

	    public function addOnItemNew()

	    {

	    	if (!isset($this->data['photo'])){

	    		$this->data['photo']='';

	    	}

	    	$this->data['category']=isset($this->data['category'])?$this->data['category']:'';

	    		   

	    	$params=array(

			  'sub_item_name'=>addslashes($this->data['sub_item_name']),

			  'item_description'=>isset($this->data['item_description'])?addslashes($this->data['item_description']):"",

			  'category'=>json_encode($this->data['category']),

			  'price'=>isset($this->data['price'])?trim($this->data['price']):0,

			  'photo'=>isset($this->data['photo'])?addslashes(trim($this->data['photo'])):"",

			  'status'=>addslashes($this->data['status']),

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'merchant_id'=>Yii::app()->functions->getMerchantID()

			);		

			

			if (isset($this->data['sub_item_name_trans'])){

				if (okToDecode()){

					$params['sub_item_name_trans']=json_encode($this->data['sub_item_name_trans'],

					JSON_UNESCAPED_UNICODE);

				} else $params['sub_item_name_trans']=json_encode($this->data['sub_item_name_trans']);				

			}	    

			if (isset($this->data['item_description_trans'])){

				if (okToDecode()){

					$params['item_description_trans']=json_encode($this->data['item_description_trans'],

					JSON_UNESCAPED_UNICODE);

				} else $params['item_description_trans']=json_encode($this->data['item_description_trans']);

			}	    

			

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{subcategory_item}}' , $params , 

				'sub_item_id=:sub_item_id' , array(':sub_item_id'=>addslashes($this->data['id'])));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'AddOn Item updated.');  

	                $item_id=$this->data['id'];

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{subcategory_item}}',$params)){

					$item_id=Yii::app()->db->getLastInsertID();

	                $this->code=1;

	                $this->msg=Yii::t("default",'AddOn Item added.');  	                

	                $this->details=$item_id;

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}

	    }

	     

	    public function SizeList()

	    {	    

	    	$slug=$this->data['slug'];

	        $stmt="

			SELECT * FROM

			{{size}}

			WHERE			

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY size_id  DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	     	    		

    	    		$val['subcat_id']=isset($val['subcat_id'])?$val['subcat_id']:'';

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[subcat_id]\" class=\"chk_child\" >";   		

    	    		$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[size_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[size_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));  

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,$val['size_name'].$option,

    	    		  $date

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

	    }	    	    

	    

	    public function AddSize()

	    {

		    $params=array(

			  'size_name'=>$this->data['size_name'],

			  'status'=>addslashes($this->data['status']),

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'merchant_id'=>Yii::app()->functions->getMerchantID()

			);							

			

			if (isset($this->data['size_name_trans'])){

				if (okToDecode()){

					$params['size_name_trans']=json_encode($this->data['size_name_trans'],

					JSON_UNESCAPED_UNICODE);

				} else $params['size_name_trans']=json_encode($this->data['size_name_trans']);				

			}	    

			

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{size}}' , $params , 

				'size_id=:size_id' , array(':size_id'=>addslashes($this->data['id'])));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Size updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{size}}',$params)){

					$this->details=Yii::app()->db->getLastInsertID();	

	                $this->code=1;

	                $this->msg=Yii::t("default",'Size added.');  	                

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}	    	

	    }		

	    

	    public function CookingRefList()

	    {

	    	$slug=$this->data['slug'];

	        $stmt="

			SELECT * FROM

			{{cooking_ref}}

			WHERE			

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY cook_id  DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	     	    		

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[cook_id]\" class=\"chk_child\" >";   		

    	    		$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[cook_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[cook_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));  

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,$val['cooking_name'].$option,

    	    		  $date

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

	    }

	    

	    public function AddCookingRef()

	    {

		    $params=array(

			  'cooking_name'=>$this->data['cooking_name'],

			  'status'=>addslashes($this->data['status']),

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'merchant_id'=>Yii::app()->functions->getMerchantID()

			);					



			if (isset($this->data['cooking_name_trans'])){

				if (okToDecode()){

					$params['cooking_name_trans']=json_encode($this->data['cooking_name_trans'],

					JSON_UNESCAPED_UNICODE);

				} else $params['cooking_name_trans']=json_encode($this->data['cooking_name_trans']);				

			}	    

					

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{cooking_ref}}' , $params , 

				'cook_id=:cook_id' , array(':cook_id'=>addslashes($this->data['id'])));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Cooking Ref. updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{cooking_ref}}',$params)){

					$this->details=Yii::app()->db->getLastInsertID();	

	                $this->code=1;

	                $this->msg=Yii::t("default",'Cooking Ref. added.');  	                

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}	    		    	

	    }

	    

	    public function FoodItemAdd()

	    {	    	

	    	$mtid=Yii::app()->functions->getMerchantID();

	    	if (!Yii::app()->functions->validateMerchantCanPost($mtid) ){

	    		if (isset($this->data['id']) && is_numeric($this->data['id'])){				

	    		} else {	

	    		   $this->msg=Yii::t("default","Sorry but you reach the limit of adding food item. Please upgrade your membership");

	    		   return ;

	    		}

	    	}	    

	    	

	    	$price='';

	    	if (isset($this->data['price']) && count($this->data['price'])>=1){

	    		foreach ($this->data['price'] as $key=>$val) {

	    			if (!empty($val)){

	    			   $price[$this->data['size'][$key]]=$val;

	    			}

	    		}	    		

	    	}	  

	    	  	    		    		    	

	    	$params=array(			  

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'merchant_id'=>Yii::app()->functions->getMerchantID(),

			  'item_name'=>isset($this->data['item_name'])?$this->data['item_name']:"",

			  'item_description'=>isset($this->data['item_description'])?$this->data['item_description']:'',

			  'status'=>$this->data['status'],

			  'category'=>isset($this->data['category'])?json_encode($this->data['category']):"",

			  'price'=>isset($price)?json_encode($price):'',

			  'addon_item'=>isset($this->data['sub_item_id'])?json_encode($this->data['sub_item_id']):"",

			  'cooking_ref'=>isset($this->data['cooking_ref'])?json_encode($this->data['cooking_ref']):"",

			  'discount'=>isset($this->data['discount'])?$this->data['discount']:"",

			  'multi_option'=>isset($this->data['multi_option'])?json_encode($this->data['multi_option']):"",

			  'multi_option_value'=>isset($this->data['multi_option_value'])?json_encode($this->data['multi_option_value']):"",

			  'photo'=>isset($this->data['photo'])?$this->data['photo']:"",

			  'ingredients'=>isset($this->data['ingredients'])?json_encode($this->data['ingredients']):"",

			  'spicydish'=>isset($this->data['spicydish'])?$this->data['spicydish']:"",

			  'two_flavors'=>isset($this->data['two_flavors'])?$this->data['two_flavors']:'',

			  'two_flavors_position'=>isset($this->data['two_flavors_position'])?json_encode($this->data['two_flavors_position']):"",

			  'require_addon'=>isset($this->data['require_addon'])?json_encode($this->data['require_addon']):"",

			  'dish'=>isset($this->data['dish'])?json_encode($this->data['dish']):'',

			  'non_taxable'=>isset($this->data['non_taxable'])?$this->data['non_taxable']:1,

			  'gallery_photo'=>isset($this->data['gallery_photo'])?json_encode($this->data['gallery_photo']):""

			);			

						

			if (isset($this->data['item_name_trans'])){

				if (okToDecode()){

				    $params['item_name_trans']=json_encode($this->data['item_name_trans'],

				    JSON_UNESCAPED_UNICODE);

				} else $params['item_name_trans']=json_encode($this->data['item_name_trans']);

			}	    

			if (isset($this->data['item_description_trans'])){

				if (okToDecode()){

				   $params['item_description_trans']=json_encode($this->data['item_description_trans'],

				   JSON_UNESCAPED_UNICODE);

				} else $params['item_description_trans']=json_encode($this->data['item_description_trans']);

			}	    

			

			/*POINTS PROGRAM*/

			if (FunctionsV3::hasModuleAddon("pointsprogram")){

				if (isset($this->data['points_earned'])){

				   $params['points_earned']=$this->data['points_earned']>0?$this->data['points_earned']:'';

				   $params['points_disabled']=isset($this->data['points_disabled'])?$this->data['points_disabled']:1;

				}

			}

						

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{item}}' , $params , 

				'item_id=:item_id' , array(':item_id'=>addslashes($this->data['id'])));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Item updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{item}}',$params)){

					$this->details=Yii::app()->db->getLastInsertID();	

	                $this->code=1;

	                $this->msg=Yii::t("default",'Item added.');  	                

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}	    		    	

	    }

	    

	    public function FoodItemList()

	    {	  

	    	Yii::app()->functions->data="list";

	    	$cat_list=Yii::app()->functions->getCategoryList(Yii::app()->functions->getMerchantID());

	    	$size_list=Yii::app()->functions->getSizeList(Yii::app()->functions->getMerchantID());

	    	//dump($size_list);

	    	

	        $slug=$this->data['slug'];

			$stmt="

			SELECT * FROM

			{{item}}

			WHERE

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY item_id DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	   

    	    		$categories='';  	    	

    	    		$category=isset($val['category'])?(array)json_decode($val['category']):false;    	    		

    	    		if ( is_array($category) && count($category)>=1){

    	    			foreach ($category as $valcat) {    	    		

    	    				if (array_key_exists($valcat,(array)$cat_list)){

    	    					$categories.=$cat_list[$valcat] .", ";

    	    				}    	    			

    	    			}

    	    			$categories=!empty($categories)?substr($categories,0,-2):"";

    	    		}    	    	

    	    		    	    		

    	    		$price_list='';

    	    		$price=isset($val['price'])?(array)json_decode($val['price']):false;

    	    		if ( is_array($price) && count($price)>=1){

    	    			foreach ($price as $key_price=>$val_price) {    	    		

    	    				if (array_key_exists($key_price,(array)$size_list)){

    	    					$price_list.=getCurrencyCode().prettyFormat($val_price)." ".ucwords($size_list[$key_price]). "<br/>";

    	    				}    	    		

    	    			}

    	    		}    	    	

    	    		

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[item_id]\" class=\"chk_child\" >";   		

    	    		$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[item_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[item_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));    	    		

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		

    	    		if (!empty($val['photo'])){

    	    			$img=Yii::app()->request->baseUrl."/upload/$val[photo]";

    	    		    $photo="<img class=\"uk-thumbnail uk-thumbnail-mini\" src=\"$img\" >";	

    	    		} else $photo='';

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,$val['item_name'].$option,

    	    		  //Yii::app()->functions->limitText($val['item_description']),

    	    		  $val['item_description'],

    	    		  $categories,

    	    		  $price_list,

    	    		  $photo,

    	    		  CHtml::checkBox('food_not_available',

    	    		  $val['not_available']==2?true:false,

    	    		  array(

    	    		    'class'=>'not_available',

    	    		    'value'=>$val['item_id']

    	    		  )),

    	    		  $date."<div>".Yii::t("default",$val['status'])."</div>"

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

	    }	

	    

	    public function UpdateMerchant()

	    {	    	

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    	if (!empty($this->data['password'])){

				$params['username']=$this->data['username'];

				$params['password']=md5($this->data['password']);

		    }

		    

		    if (!empty($this->data['password'])){

		    	if ( Yii::app()->functions->validateUsername($this->data['username'],$merchant_id) ){

		    		$this->msg=Yii::t("default","Merchant Username is already been taken");

		    		return ;

		    	}		    

		    }	    		

		    

		    if ( Yii::app()->functions->validateMerchantEmail($this->data['contact_email'],$merchant_id) ){

		    	$this->msg=Yii::t("default","Merchant Email address is already been taken");

		    	return ;

		    }	    

		    /*dump($merchant_id);

		    dump($params);

		    die();*/

		    

		    $params['restaurant_name']=isset($this->data['restaurant_name'])?$this->data['restaurant_name']:"";

			$params['restaurant_phone']=isset($this->data['restaurant_phone'])?$this->data['restaurant_phone']:'';

			$params['contact_name']=isset($this->data['contact_name'])?$this->data['contact_name']:'';

			$params['contact_phone']=isset($this->data['contact_phone'])?$this->data['contact_phone']:'';

			$params['contact_email']=isset($this->data['contact_email'])?$this->data['contact_email']:'';

			$params['country_code']=isset($this->data['country_code'])?$this->data['country_code']:'';

			$params['street']=isset($this->data['street'])?$this->data['street']:'';

			$params['city']=isset($this->data['city'])?$this->data['city']:'';

			$params['post_code']=isset($this->data['post_code'])?$this->data['post_code']:'';

			$params['cuisine']=isset($this->data['cuisine'])?json_encode($this->data['cuisine']):'';

			$params['service']=isset($this->data['service'])?$this->data['service']:'';			

		    $params['date_created']=date('c');

		    $params['ip_address']=$_SERVER['REMOTE_ADDR'];

		    

		    $params['state']=isset($this->data['state'])?$this->data['state']:'';

		    $params['abn']=isset($this->data['abn'])?$this->data['abn']:'';

		    

		    $merchant_id=Yii::app()->functions->getMerchantID();

		    

		    $params['restaurant_slug']=FunctionsV3::verifyMerchantSlug(

		      Yii::app()->functions->seo_friendly_url($this->data['restaurant_slug']),$merchant_id

		    );

		   

	    	Yii::app()->functions->updateOption("merchant_latitude",

	    	isset($this->data['merchant_latitude'])?$this->data['merchant_latitude']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_longtitude",

	    	isset($this->data['merchant_longtitude'])?$this->data['merchant_longtitude']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_information",

	    	isset($this->data['merchant_information'])?$this->data['merchant_information']:''

	    	,$merchant_id);

		    		    

		    unset($params['date_created']);

			$params['date_modified']=date('c');				

													

			$res = $this->updateData('{{merchant}}' , $params ,'merchant_id',Yii::app()->functions->getMerchantID());

			if ($res){

				$this->code=1;

                $this->msg=Yii::t("default",'Merchant updated.');  

			} else $this->msg=Yii::t("default","ERROR: cannot update");		    

	    }	

	    

	    public function merchantSettings()
	    {	    	
	    	
	    	/** reverse back to 24 hour format if format is 12 hour*/

	    	if ( Yii::app()->functions->getOptionAdmin("website_time_picker_format") =="12"){
		    	if (is_array($this->data['stores_open_starts'])){
		    		foreach ($this->data['stores_open_starts'] as $key=>$val) 
					{
		    			//dump($key."=>".$val);
		    			$this->data['stores_open_starts'][$key]=timeFormat($val);
		    		}
		    	}


		    	if (is_array($this->data['stores_open_ends'])){

		    		foreach ($this->data['stores_open_ends'] as $key=>$val) {

		    			//dump($key."=>".$val);

		    			$this->data['stores_open_ends'][$key]=timeFormat($val);
		    		}

		    	}
            
	    	}
                 
				 $merchant_id=Yii::app()->functions->getMerchantID();
				 
	    		 $days=Yii::app()->functions->getDays();
				 foreach ($days as $key=>$val):
				 
				        // For Morning
				 		$stores_open_starts = $this->data['stores_open_starts'][$key]; 
			            $stores_open_times = $this->data['stores_open_time'][$key];
						
						$stores_open_ends = $this->data['stores_open_ends'][$key];
			            $stores_ends_time = $this->data['stores_ends_time'][$key];
						
						// For Afternoon
						
						$stores_open_pm_start = $this->data['stores_open_pm_start'][$key];
						$stores_open_time_pm = $this->data['stores_open_time_pm'][$key]; 
			            
						$stores_open_pm_ends = $this->data['stores_open_pm_ends'][$key];
						$stores_ends_time_pm = $this->data['stores_ends_time_pm'][$key];
			            
					    // For Morning
						
						/*if(!empty($stores_open_times) && !empty($stores_ends_time) )
						{*/
							if(strtotime($stores_open_starts) <= strtotime($stores_open_times) 
							&& strtotime($stores_open_ends) >= strtotime($stores_ends_time) && 
							strtotime($stores_open_ends) > strtotime($stores_open_times) && 
							strtotime($stores_open_times) < strtotime($stores_ends_time)
							)
							{
								Yii::app()->functions->updateOption("stores_open_time",
					
								isset($this->data['stores_open_time'])?json_encode($this->data['stores_open_time']):''
					
								,$merchant_id);
								
								Yii::app()->functions->updateOption("stores_ends_time",
					
								isset($this->data['stores_ends_time'])?json_encode($this->data['stores_ends_time']):''
								,$merchant_id); 
								
							/*}
							else 
							{
								$this->msg=t("Please Select Morning time between ".$stores_open_starts." to ".$stores_open_ends);
							   return ;
							}*/
						}
						// For Afternoon
						
						/*if(!empty($stores_open_time_pm) && !empty($stores_ends_time_pm) )
						{*/
							if(strtotime($stores_open_pm_start) <= strtotime($stores_open_time_pm) 
							&& strtotime($stores_open_pm_ends) >= strtotime($stores_ends_time_pm) && 
							strtotime($stores_open_pm_ends) > strtotime($stores_open_time_pm) && 
							strtotime($stores_open_time_pm) < strtotime($stores_ends_time_pm))
							{
							    Yii::app()->functions->updateOption("stores_open_time_pm",
					
								isset($this->data['stores_open_time_pm'])?json_encode($this->data['stores_open_time_pm']):''
					
								,$merchant_id);
								
								Yii::app()->functions->updateOption("stores_ends_time_pm",
					
								isset($this->data['stores_ends_time_pm'])?json_encode($this->data['stores_ends_time_pm']):''
								,$merchant_id); 
							/*}
							else 
							{
								$this->msg=t("Please Select Afternoon time between ".$stores_open_pm_start." to ".$stores_open_pm_ends);
							   return ;
							}*/
						}
						
				 endforeach;
				 
						
						

	    	/*dump(json_encode($this->data['stores_open_starts']));

	    	dump($this->data);	    	

	    	die();*/

	    	

	    	/*Yii::app()->functions->updateOption("merchant_currency",

	    	isset($this->data['merchant_currency'])?$this->data['merchant_currency']:''

	    	,$merchant_id);*/

	    	

	    	/*Yii::app()->functions->updateOption("merchant_decimal",

	    	isset($this->data['merchant_decimal'])?$this->data['merchant_decimal']:''

	    	,$merchant_id);*/

	    	

	    	/*Yii::app()->functions->updateOption("merchant_use_separators",

	    	isset($this->data['merchant_use_separators'])?$this->data['merchant_use_separators']:''

	    	,$merchant_id);*/

	    	

	    	Yii::app()->functions->updateOption("merchant_minimum_order",

	    	isset($this->data['merchant_minimum_order'])?$this->data['merchant_minimum_order']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_tax",

	    	isset($this->data['merchant_tax'])?$this->data['merchant_tax']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_delivery_charges",

	    	isset($this->data['merchant_delivery_charges'])?$this->data['merchant_delivery_charges']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("stores_open_day",

	    	isset($this->data['stores_open_day'])?json_encode($this->data['stores_open_day']):''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("stores_open_starts",

	    	isset($this->data['stores_open_starts'])?json_encode($this->data['stores_open_starts']):''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("stores_open_ends",

	    	isset($this->data['stores_open_ends'])?json_encode($this->data['stores_open_ends']):''

	    	,$merchant_id);
			
		
             
			
			 
	    	 Yii::app()->functions->updateOption("max_orders",

	    	isset($this->data['max_orders'])?json_encode($this->data['max_orders']):''

	    	,$merchant_id);
			

	    	Yii::app()->functions->updateOption("stores_open_custom_text",

	    	isset($this->data['stores_open_custom_text'])?json_encode($this->data['stores_open_custom_text']):''

	    	,$merchant_id);

	    		    	

	    	//if (isset($this->data['photo'])){

	    	Yii::app()->functions->updateOption("merchant_photo",

	    	isset($this->data['photo'])?$this->data['photo']:''

	    	,$merchant_id);

	    	//}

	    		    	

	    		    		    	

	    	Yii::app()->functions->updateOption("merchant_delivery_estimation",

	    	isset($this->data['merchant_delivery_estimation'])?$this->data['merchant_delivery_estimation']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_delivery_miles",

	    	isset($this->data['merchant_delivery_miles'])?$this->data['merchant_delivery_miles']:''

	    	,$merchant_id);

	    	

	    	/*Yii::app()->functions->updateOption("merchant_delivery_charges_type",

	    	isset($this->data['merchant_delivery_charges_type'])?$this->data['merchant_delivery_charges_type']:''

	    	,$merchant_id);*/

	    	

	    	Yii::app()->functions->updateOption("merchant_photo_bg",

	    	isset($this->data['photo2'])?$this->data['photo2']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_disabled_cod",

	    	isset($this->data['merchant_disabled_cod'])?$this->data['merchant_disabled_cod']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_disabled_ccr",

	    	isset($this->data['merchant_disabled_ccr'])?$this->data['merchant_disabled_ccr']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_extenal",

	    	isset($this->data['merchant_extenal'])?$this->data['merchant_extenal']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_enabled_voucher",

	    	isset($this->data['merchant_enabled_voucher'])?$this->data['merchant_enabled_voucher']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_distance_type",

	    	isset($this->data['merchant_distance_type'])?$this->data['merchant_distance_type']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_timezone",

	    	isset($this->data['merchant_timezone'])?$this->data['merchant_timezone']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_close_msg",

	    	isset($this->data['merchant_close_msg'])?$this->data['merchant_close_msg']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_preorder",

	    	isset($this->data['merchant_preorder'])?$this->data['merchant_preorder']:''

	    	,$merchant_id);

	    	

	    	/*Yii::app()->functions->updateOption("merchant_table_booking",

	    	isset($this->data['merchant_table_booking'])?$this->data['merchant_table_booking']:''

	    	,$merchant_id);*/

	    	

	    	Yii::app()->functions->updateOption("merchant_maximum_order",

	    	isset($this->data['merchant_maximum_order'])?$this->data['merchant_maximum_order']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_packaging_charge",

	    	isset($this->data['merchant_packaging_charge'])?$this->data['merchant_packaging_charge']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_close_msg_holiday",

	    	isset($this->data['merchant_close_msg_holiday'])?$this->data['merchant_close_msg_holiday']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_holiday",

	    	isset($this->data['merchant_holiday'])?json_encode($this->data['merchant_holiday']):''

	    	,$merchant_id);

	    		    	

	    	Yii::app()->functions->updateOption("merchant_activated_menu",

	    	isset($this->data['merchant_activated_menu'])?$this->data['merchant_activated_menu']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("spicydish",

	    	isset($this->data['spicydish'])?$this->data['spicydish']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_required_delivery_time",

	    	isset($this->data['merchant_required_delivery_time'])?$this->data['merchant_required_delivery_time']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_close_store",

	    	isset($this->data['merchant_close_store'])?$this->data['merchant_close_store']:''

	    	,$merchant_id);	    		    	

	    	

	    	Yii::app()->functions->updateOption("merchant_packaging_increment",

	    	isset($this->data['merchant_packaging_increment'])?$this->data['merchant_packaging_increment']:''

	    	,$merchant_id);	    		    	

	    	

	    	Yii::app()->functions->updateOption("merchant_show_time",

	    	isset($this->data['merchant_show_time'])?$this->data['merchant_show_time']:''

	    	,$merchant_id);	    		    	

	    	

	    	Yii::app()->functions->updateOption("merchant_enabled_tip",

	    	isset($this->data['merchant_enabled_tip'])?$this->data['merchant_enabled_tip']:''

	    	,$merchant_id);	    		    	

	    	

	    	Yii::app()->functions->updateOption("merchant_tip_default",

	    	isset($this->data['merchant_tip_default'])?$this->data['merchant_tip_default']:''

	    	,$merchant_id);	    		    	

	    		    	

	    	Yii::app()->functions->updateOption("merchant_minimum_order_pickup",

	    	isset($this->data['merchant_minimum_order_pickup'])?$this->data['merchant_minimum_order_pickup']:''

	    	,$merchant_id);	    		    	

	    	

	    	Yii::app()->functions->updateOption("merchant_maximum_order_pickup",

	    	isset($this->data['merchant_maximum_order_pickup'])?$this->data['merchant_maximum_order_pickup']:''

	    	,$merchant_id);	    		    	

	    	

	    	Yii::app()->functions->updateOption("merchant_disabled_ordering",

	    	isset($this->data['merchant_disabled_ordering'])?$this->data['merchant_disabled_ordering']:''

	    	,$merchant_id);	    		    	

	    		    

	    	Yii::app()->functions->updateOption("merchant_tax_charges",

	    	isset($this->data['merchant_tax_charges'])?$this->data['merchant_tax_charges']:''

	    	,$merchant_id);	    



	    	Yii::app()->functions->updateOption("stores_open_pm_start",

	    	isset($this->data['stores_open_pm_start'])?json_encode($this->data['stores_open_pm_start']):''

	    	,$merchant_id);

	    		    	

	    	Yii::app()->functions->updateOption("stores_open_pm_ends",

	    	isset($this->data['stores_open_pm_ends'])?json_encode($this->data['stores_open_pm_ends']):''

	    	,$merchant_id);    	

	    	
			Yii::app()->functions->updateOption("stores_open_time_pm",

	    	isset($this->data['stores_open_time_pm'])?json_encode($this->data['stores_open_time_pm']):''

	    	,$merchant_id); 
			
			Yii::app()->functions->updateOption("stores_ends_time_pm",

	    	isset($this->data['stores_ends_time_pm'])?json_encode($this->data['stores_ends_time_pm']):''

	    	,$merchant_id); 
			
			
			Yii::app()->functions->updateOption("max_orders_pm",

	    	isset($this->data['max_orders_pm'])?json_encode($this->data['max_orders_pm']):''

	    	,$merchant_id); 
			

	    	Yii::app()->functions->updateOption("food_option_not_available",

	    	isset($this->data['food_option_not_available'])?$this->data['food_option_not_available']:''

	    	,$merchant_id);  

	    	

	    	Yii::app()->functions->updateOption("order_verification",

	    	isset($this->data['order_verification'])?$this->data['order_verification']:''

	    	,$merchant_id);  

	    	

	    	Yii::app()->functions->updateOption("order_sms_code_waiting",

	    	isset($this->data['order_sms_code_waiting'])?$this->data['order_sms_code_waiting']:''

	    	,$merchant_id);  

	    	

	    	Yii::app()->functions->updateOption("disabled_food_gallery",

	    	isset($this->data['disabled_food_gallery'])?$this->data['disabled_food_gallery']:''

	    	,$merchant_id);  

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	    	
		}

			    

	    public function AlertSettings()

	    {	    	

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    	

	    	Yii::app()->functions->updateOption("merchant_notify_email",

	    	isset($this->data['merchant_notify_email'])?$this->data['merchant_notify_email']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("enabled_alert_notification",

	    	isset($this->data['enabled_alert_notification'])?$this->data['enabled_alert_notification']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("enabled_alert_sound",

	    	isset($this->data['enabled_alert_sound'])?$this->data['enabled_alert_sound']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	    }	

	    

	    public function socialSettings()

	    {

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    	

	    	Yii::app()->functions->updateOption("facebook_page",

	    	isset($this->data['facebook_page'])?$this->data['facebook_page']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("twitter_page",

	    	isset($this->data['twitter_page'])?$this->data['twitter_page']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("google_page",

	    	isset($this->data['google_page'])?$this->data['google_page']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	    }	

	    

	    public function sortItem()

	    {	    	

	    	$DbExt=new DbExt;

	    	if ( isset($this->data['table']) && isset($this->data['sort_field'])){

		    	if (!empty($this->data['table']) && is_array($this->data['sort_field'])){

		    		$tbl=$this->data['table'];

		    		if (is_array($this->data['sort_field']) && count($this->data['sort_field'])>=1){

		    			$x=1;

		    			foreach ($this->data['sort_field'] as $item_id) {

		    				$params=array(

		    				 'sequence'=>$x

		    				);		    				

		    				$DbExt->updateData("{{{$tbl}}}",$params,$this->data['whereid'],$item_id);

		    				$x++;

		    			}

		    			$this->code=1;

		    			$this->msg=Yii::t("default","Sort saved.");

		    		} else $this->msg=Yii::t("default","Missing parameters");

		    	} else $this->msg=Yii::t("default","Missing parameters");

	    	} else $this->msg=Yii::t("default","Missing parameters");

	    }	

	    

	    public function searchArea()

	    {

	    		    	

	    	$resto_cuisine='';	    	

	    	$rating='';

	    	$minimum='';

	    	$pay_by='';

	    	$minus_has_delivery_rates=0;

	    	

	    	$cuisine_list=Yii::app()->functions->Cuisine(true);	    		    	

	    	$country_list=Yii::app()->functions->CountryList();

	    		    	

	    	$this->data['s']=isset($this->data['s'])?$this->data['s']:"";

	    	$search_str=explode(",",$this->data['s']);

	    	

	    	if (is_array($search_str) && count($search_str)>=2){	    		

	    		$city=isset($search_str[1])?trim($search_str[1]):'';

	    		$state=isset($search_str[2])?trim($search_str[2]):'';

	    	} else {

	    		$city=trim($this->data['s']);

	    		$state=trim($this->data['s']);

	    	}	    

	    	

	    	$from_address=$this->data['s'];	    

	    	if ( empty($from_address)){

	    		$from_address=isset($this->data['st'])?$this->data['st']:'';

	    		if ( !empty($this->data['st'])){

	    			$_SESSION['kr_search_address']=$this->data['st'];

	    		}	    	

	    	}	    	    	

	    		    	

	    		    	

	    		    	

	    	

	    	if ( $res=Yii::app()->functions->searchByArea($city,$state)){

	    			    			    			    			    					   

	    		if (is_array($res) && count($res)>=1){

	    				    			

	    			$total=Yii::app()->functions->search_result_total;

	    		    $feed_datas['sEcho']=$this->data['sEcho'];

		            $feed_datas['iTotalRecords']=$total;

			        $feed_datas['iTotalDisplayRecords']=$total;					

			        

			        /*dump($feed_datas);

			        die();*/

	    			

	    			foreach ($res as $val) {	    				    				

	    					    				

	    				$merchant_address=$val['street']." ".$val['city'] ." ". $val['post_code'];

	    				$miles=0;

	    				$kms=0;

	    				$ft=false;

	    				$new_distance_raw=0;

	    				if($distance=getDistance($from_address,$merchant_address,$val['country_code'],false)){	    					    					

	    					$miles=$distance->rows[0]->elements[0]->distance->text;	

	    					//dump($miles);

	    					if (preg_match("/ft/i",$miles)) {

	    						$ft=true;

	    						$miles_raw=$miles;

	    						$new_distance_raw=str_replace("ft",'',$miles);

	    						$new_distance_raw=ft2kms(trim($new_distance_raw));

	    					} else {						        

								$miles_raw=str_replace(array(" ","mi"),"",$miles);

								$kms=miles2kms( unPrettyPrice($miles_raw));

								$new_distance_raw=$kms;

			                    $kms=standardPrettyFormat($kms);			                    

	    					}		                    

	    				}	    		

	    					    				

	    				/*get merchant distance */

$mt_delivery_miles=Yii::app()->functions->getOption("merchant_delivery_miles",$val['merchant_id']);

$merchant_distance_type=Yii::app()->functions->getOption("merchant_distance_type",$val['merchant_id']);

	    				/*dump($mt_delivery_miles);

	    				dump($miles_raw);*/	    				

	    						    		

	    				$resto_cuisine='';

	    				$cuisine=!empty($val['cuisine'])?(array)json_decode($val['cuisine']):false;

	    				if($cuisine!=false){

	    					foreach ($cuisine as $valc) {	    						

	    						if ( array_key_exists($valc,(array)$cuisine_list)){

	    							$resto_cuisine.=$cuisine_list[$valc].", ";

	    						}				

	    					}

	    					$resto_cuisine=!empty($resto_cuisine)?substr($resto_cuisine,0,-2):'';

	    				}	    			

	    				$resto_info="<h5><a href=\"".baseUrl()."/store/menu/merchant/".$val['restaurant_slug']. "\">".

	    				$val['restaurant_name']."</a></h5>";

	    				

	    				//$resto_cuisine="<span class=\"cuisine-list\">".$resto_cuisine."</span>";

	    				$resto_cuisine=wordwrap($resto_cuisine,50,"<br />\n");

	    				

	    				$resto_info.="<p class=\"uk-text-muted\">".$val['street'].

	    				" ".$val['city'] ." ". $val['post_code'] . "</p>";

	    				if ( array_key_exists($val['country_code'],(array)$country_list)){

	    					$resto_info.="<p class=\"uk-text-bold\">".$country_list[$val['country_code']]."</p>";

	    				}	    				

	    					    				

$resto_info.="<p class=\"uk-text-bold\">".Yii::t('default',"Cuisine")." - ".$resto_cuisine."</p>";

	    				

                        $delivery_est=Yii::app()->functions->getOption("merchant_delivery_estimation",$val['merchant_id']);

                     

                          

$distancesya=$miles_raw;

$unit_distance=$merchant_distance_type;





if (!empty($from_address)):

	if ( $ft==TRUE){

		$resto_info.="<p><span class=\"uk-text-bold\">".Yii::t("default","Distance").": </span> $miles_raw </p>";		   		

	   if ( $merchant_distance_type=="km"){

	   	   $distance_type=Yii::t("default","km");

	   } else $distance_type=Yii::t("default","miles");

	} else {

		if ( $merchant_distance_type=="km"){

			$distance_type=Yii::t("default","km");

		    $resto_info.="<p><span class=\"uk-text-bold\">".Yii::t("default","Distance").": </span> $kms ".Yii::t("default","km")."</p>";		

		    $distancesya=$kms;

		} else {	

		    $resto_info.="<p><span class=\"uk-text-bold\">".Yii::t("default","Distance").": </span> $miles_raw ".Yii::t("default","miles")."</p>";		

		   $distance_type=Yii::t("default","miles");

		}

	}

endif;

	    								

$resto_info.="<p><span class=\"uk-text-bold\">".Yii::t("default","Delivery Est").": </span> ".$delivery_est."</p>";



    if (is_numeric($mt_delivery_miles)){    	

        $resto_info.="<p><span class=\"uk-text-bold\">".Yii::t("default","Delivery Distance").": </span> ".$mt_delivery_miles." ".$distance_type."</p>";	 

    }	

        

        $shipping_enabled=Yii::app()->functions->getOption("shipping_enabled",$val['merchant_id']);  

        //delivery rates table         

        $delivery_fee=$val['delivery_charges'];

        if ( $shipping_enabled==2){

        	$FunctionsK=new FunctionsK();

        	        	

        	//$distancesya=round($distancesya);        	

        	//dump($distancesya);        	

        	

        	$delivery_fee=$FunctionsK->getDeliveryChargesByDistance($val['merchant_id'],

        	$distancesya,$unit_distance,$delivery_fee);

        	        	

        	if ($delivery_fee>=0.01){

        		if (isset($_GET['filter_promo'])){

        			if (preg_match("/free-delivery/i",$_GET['filter_promo'])) {

        				$minus_has_delivery_rates++;

        				continue;

        			}

        		}        	        		

        	}        

        }

                

	    						    								

	    				if ( is_numeric($delivery_fee) && $delivery_fee>=1){

	    			      $resto_info.="<p><span class=\"uk-text-bold\">".Yii::t("default","Delivery Fee").":</span> ".

	    				  displayPrice(getCurrencyCode(),prettyFormat($delivery_fee)). "</p>";

	    				} else {	    			

	    				  $resto_info.="<p><span class=\"uk-text-bold\">".Yii::t("default","Delivery Fee").":</span> ".

	    				  "<span class=\"uk-text-success\">".Yii::t("default","Free Delivery")."</span>". "</p>";

	    				}

	    					    					    				

	    				$image='';

	    				$merchant_photo=Yii::app()->functions->getOption("merchant_photo",$val['merchant_id']);

	    				if (!empty($merchant_photo)){

	    					$image.="<a href=\"".baseUrl()."/store/menu/merchant/".$val['restaurant_slug']. "\">";

	    					$image.="<img class=\"uk-thumbnail uk-thumbnail-mini\" src=\"".baseUrl()."/upload/".$merchant_photo."\" alt=\"\" title=\"\">";

	    					$image.="</a>";

	    				}	    

	    				

	    				if (empty($image)){

	    					$image.="<a href=\"".baseUrl()."/store/menu/merchant/".$val['restaurant_slug']. "\">";

	    					$image.="<img class=\"uk-thumbnail uk-thumbnail-mini\" src=\"".baseUrl()."/assets/images/thumbnail-medium.png\" alt=\"\" title=\"\">";

	    					$image.="</a>";

	    				}	    			

	    				

	    				$ratings=Yii::app()->functions->getRatings($val['merchant_id']);	    				

	    				$rating_meanings='';

	    				if ( $ratings['ratings'] >=1){

	    					$rating_meaning=Yii::app()->functions->getRatingsMeaning($ratings['ratings']);

	    					$rating_meanings=ucwords($rating_meaning['meaning']);

	    				}	    			

	    				$rating="<div class=\"rate-wrap\">

	    				<h6 class=\"rounded2\" data-uk-tooltip=\"{pos:'bottom-left'}\" title=\"$rating_meanings\" >".

	    				number_format($ratings['ratings'],1)."</h6>

	    				<span>".$ratings['votes']." ".Yii::t("default","Votes")."</span>

	    				</div>";

	    						

	    					    				

						$tips=Widgets::getOperationalHours($val['merchant_id']);

						

						$resto_info.=$tips;

						

						$resto_info.="<div class=\"spacer\"></div>";

							

						$is_open=true; $pre_order='';			

						if ( getOption($val['merchant_id'],'merchant_close_store')=="yes"){

							$is_open=false;

						}

						if ($is_open==false){

							if ( $pre_order=getOption($val['merchant_id'],'merchant_preorder')==1){

								$is_open=true;

							}						

						}

						

						if ( $is_open==true){

							$resto_info.="<div>

							<a class=\"uk-button uk-button-success uk-width-1-2\" href=\"".baseUrl()."/store/menu/merchant/".$val['restaurant_slug']. "\">";		

							if ( $pre_order == 1) {

								$resto_info.=Yii::t("default","Pre-Order");

							} else $resto_info.=Yii::t("default","Order Now");										

							$resto_info.="</a></div>";

						}

												

						$resto_info.="<div class=\"spacer\"></div>";

						

						$table_book=Yii::app()->functions->getOption("merchant_table_booking",$val['merchant_id']);  

						

						$admin_book=getOptionA('merchant_tbl_book_disabled');

						if ($admin_book==2){

							$table_book=$admin_book;

						}	    			

						

						if ( $table_book==""){

						$resto_info.="<div>

						<a class=\"uk-button uk-button-success uk-width-1-2\" href=\"".baseUrl()."/store/menu/merchant/".$val['restaurant_slug']."/?tab=booking". "\">";

						$resto_info.=Yii::t("default","Book a Table");

						$resto_info.="</a></div>";

						}

						

						$is_sponsored='';						

						if ($val['is_sponsored']==2){							

							$is_sponsored="<br/><div class=\"uk-badge uk-badge-warning\">".Yii::t("default","sponsored")."</div>";

						}			

						

						$is_merchant_open = Yii::app()->functions->isMerchantOpen($val['merchant_id']); 

						$merchant_preorder= Yii::app()->functions->getOption("merchant_preorder",$val['merchant_id']);

						

						

						$now=date('Y-m-d');

						$is_holiday=false;

 				        if ( $m_holiday=Yii::app()->functions->getMerchantHoliday($val['merchant_id'])){  

				      	   if (in_array($now,(array)$m_holiday)){

				      	   	  $is_merchant_open=false;

				      	   }

				        }

				        

				        if ( $is_merchant_open==true){

				        	if ( getOption($val['merchant_id'],'merchant_close_store')=="yes"){

				        		$is_merchant_open=false;				        		

				        	}

				        }	    			

						

						$tag_open='';

						if ( $is_merchant_open==TRUE){

							$tag_open='<div class="uk-badge uk-badge-success">'.t("Open").'</div>';

						} else {

							if ($merchant_preorder){

								$tag_open='<div class="uk-badge uk-badge-warning">'.t("Pre-Order").'</div>';

							} else $tag_open='<div class="uk-badge uk-badge-danger">'.t("Closed").'</div>';

						}

						$is_sponsored.=$tag_open;

						

						$offers=Widgets::offers($val['merchant_id'],2);		

						$is_sponsored.=$offers;

						



                    $merchant_latitude=Yii::app()->functions->getOption("merchant_latitude",$val['merchant_id']);

                    $merchant_longtitude=Yii::app()->functions->getOption("merchant_longtitude",$val['merchant_id']);

						

						$merchant_latitude=!empty($merchant_latitude)?$merchant_latitude:'0';

						$merchant_longtitude=!empty($merchant_longtitude)?$merchant_longtitude:'0';

	    				

	    				$feed_data[]=array(

	    				  $image,

	    				  $resto_info,

	    				  $rating,

	    				  !empty($val['minimum_order'])?displayPrice(getCurrencyCode(),prettyFormat($val['minimum_order']))."<br/>".$is_sponsored:"$is_sponsored",

	    				  $miles_raw,

	    				  $merchant_latitude,

	    				  $merchant_longtitude,

	    				  addslashes($val['restaurant_name']),

	    				  $merchant_address,

	    				  $val['restaurant_slug'],

	    				  $image,

	    				  $new_distance_raw

	    			    );	    			    

	    			}



	    			$this->data['sort_filter']=isset($this->data['sort_filter'])?$this->data['sort_filter']:'';

	    			

	    			//dump($feed_data);

	    			

	    			if ( $this->data['sort_filter']=="distance"){	    				

	    				Yii::app()->functions->arraySortByColumn($feed_data,11);

	    				$feed_datas['aaData']=$feed_data;

	    			} else {	    				

	    				/** sort by distance */	

	    				if ( Yii::app()->functions->getOptionAdmin('search_result_bydistance')==2){	 

	    				    Yii::app()->functions->arraySortByColumn($feed_data,11);

	    				    $feed_datas['aaData']=$feed_data;

	    				} else $feed_datas['aaData']=$feed_data;

	    			}	    		

	    				    			

	    			//dump("minus_has_delivery_rates->".$minus_has_delivery_rates);

	    			if ( $minus_has_delivery_rates>=1){

	    				$feed_datas['iTotalRecords']=$feed_datas['iTotalRecords']-$minus_has_delivery_rates;

                        $feed_datas['iTotalDisplayRecords']=$feed_datas['iTotalDisplayRecords']-$minus_has_delivery_rates;

                        if ($feed_datas['iTotalRecords']<=0) {

                        	$this->otableNodata();                        	

                        }

	    			}	    		

	    			

	    			$this->otableOutput($feed_datas);

	    			

	    		}	    	

	    	}	   

	    	$this->otableNodata();

	    }

	    

	    public function viewFoodItem()

	    {	    	

	    	if (isset($this->data['item_id'])){

	    		require_once 'food-item.php';

	    	} else {

	    		?>

	    		<p class="uk-alert uk-alert-danger"><?php echo Yii::t("default","Sorry but we cannot find what you are looking for.")?></p>

	    		<?php

	    	}

	    	die();

	    }	

	    

	    public function addToCart()

	    {	    		    	

	    	/** two flavor pizza */

	    	if (!isset($this->data['two_flavors'])){

	    		$this->data['two_flavors']='';

	    	}

	    

	    	if ( $this->data['two_flavors']==2){

	    		$two_flavor_price='';

	    		if (is_array($this->data['sub_item']) && count($this->data['sub_item'])>=1){	    			

	    			$price='';

	    			foreach ($this->data['sub_item'] as $key=>$val) {	    				

	    				$t=explode("|",$val[0]);	    				

	    				if ( $t[3]=="right" || $t[3]=="left"){

	    				   $price[$key]=$t[1];	    				

	    				}	    			

	    			}	    	

	    				    			

	    			$highest_price = max($price);				    			

	    			$highest_key = array_search($highest_price, $price);	    			

	    			$this->data['price']=$highest_price;	    					    			

	    		}	    	

	    	}	    

	    	/** two flavor pizza */

	    		    	

	    	//unset($_SESSION['kr_item']);

	    	$this->msg=t("Food Item added to cart");	    	

	    	if (isset($this->data['item_id'])){	    		

	    		$item=$this->data;

	    		

	    		/** check if item is taxable*/	  

	    		if ( $food_info=Yii::app()->functions->getFoodItem($item['item_id'])){	    			

	    			$item['non_taxable']=$food_info['non_taxable'];

	    		}	    	

	    		

	    		unset($item['action']);

	    		if (is_numeric($this->data['row'])){

	    			$row=$this->data['row']-1;

	    			$_SESSION['kr_item'][$row]=$item;

	    			$this->msg=t("Cart updated");

	    		} else {	  	    

	    			$addon_ids='';	    			

	    			if (!isset($item['sub_item'])){

	    				$item['sub_item']='';

	    			}	    			

	    			if (is_array($item['sub_item']) && count($item['sub_item'])>=1){

	    				foreach ($item['sub_item'] as $sub_item) {

	    					foreach ($sub_item as $sub_item1) {

	    						$t=explode("|",$sub_item1);

	    						if(is_array($t) && count($t)>=1){

	    						    $addon_ids[]=$t[0];

	    						}

	    					}

	    				}

	    			}	    		    

	    			if (is_array($addon_ids) && count($addon_ids)>=1){

	    			    $item['addon_ids']=$addon_ids;		

	    			}

	    			

	    			//dump($item);	    			

	    			//die();

	    			

	    			$found=false;	  

	    			$found_key='';  			

	    			if (!isset($_SESSION['kr_item'])){

	    				$_SESSION['kr_item']='';

	    			}

	    			if(is_array($_SESSION['kr_item']) && count($_SESSION['kr_item'])>=1){

	    			   $x=0;

	    			   foreach ($_SESSION['kr_item'] as $key=> $val) {	 	    			   	   

	    			   	   if ($val['item_id']==$item['item_id']){	    			   	   	      			   	   	  

	    			   	   	   $found_key=$key;	    

	    			   	   	   

	    			   	   	   $notes=false;	    			   	   	   	    			   	   	   

	    			   	   	   if ( $item['notes']==$val['notes']){

	    			   	   	   	  $notes=true;

	    			   	   	   }

	    			   	   	   

	    			   	   	   //dump($val);		

	    			   	   	   /** check cooking_ref*/

	    			   	   	   $cooking_ref=false;

	    			   	   	   if (!isset($val['cooking_ref'])){

	    			   	   	       $val['cooking_ref']='';

	    			   	   	   }

                               if (!isset($item['cooking_ref'])){

	    			   	   	       $item['cooking_ref']='';

	    			   	   	   }

	    			   	   	   

    			   	   	   	   if ( $item['cooking_ref']==$val['cooking_ref']){

    			   	   	   	  	  $cooking_ref=true;

    			   	   	   	   }	   	   	   

    			   	   	   	   

    			   	   	   	   /*check size*/

    			   	   	   	   $item_size=false;

    			   	   	   	   if ($item['price']==$val['price']){

    			   	   	   	   	  $item_size=true;

    			   	   	   	   }	    			   	   

    			   	   	   	   

    			   	   	   	   /** ingredients*/

    			   	   	   	   $ingredients=false;

    			   	   	   	   if (!isset($item['ingredients'])){

    			   	   	   	   	   $item['ingredients']='';

    			   	   	   	   }

    			   	   	   	   

	    			   	       if (!isset($val['ingredients'])){

	    			   	       	  $val['ingredients']='';

	    			   	       }			   	   	   

    			   	   	   	   if (is_array($item['ingredients']) && count($item['ingredients'])>=1){

	    			   	   	   	   if ( count($val['ingredients']) >= count($item['ingredients']) ) {

		    			   	   	   	   $compare=array_diff((array)$val['ingredients'],(array)$item['ingredients']);

		    			   	   	   	   if (count($compare)<=0){		    			   	   	   	   	  

		    			   	   	   	   	  $ingredients=true;

		    			   	   	   	   }		    			   	   	   

	    			   	   	   	   }	    			   	   	   	   

	    			   	   	   } else {		    			   	   	   	    	

	    			   	   	   	  if (!isset($val['ingredients'])){

	    			   	   	   	  	 $val['ingredients']='';

	    			   	   	   	  }		   	   	   	  

	    			   	   	   	  $compare=array_diff((array)$val['ingredients'],(array)$item['ingredients']);

	    			   	   	   	  if (count($compare)<=0){

	    			   	   	   	  	  $ingredients=true;

	    			   	   	   	   }

	    			   	   	   }	    			   	       			   	   	   	       			   	   	   	   

	    			   	   	   

	    			   	   	   /** addon */

	    			   	   	   if (!isset($item['addon_ids'])){

	    			   	   	   	   $item['addon_ids']=''; 

	    			   	   	   }

	    			   	   	   if (!isset($val['addon_ids'])){

	    			   	   	   	   $val['addon_ids']=''; 

	    			   	   	   }

	    			   	   	   

	    			   	   	   if (is_array($item['addon_ids']) && count($item['addon_ids'])>=1){	 

	    			   	   	   	   if ( count($val['addon_ids']) >= count($item['addon_ids']) ) {

		    			   	   	   	   $compare=array_diff((array)$val['addon_ids'],(array)$item['addon_ids']);

		    			   	   	   	   if (count($compare)<=0){		

		    			   	   	   	   	  if($cooking_ref==TRUE && $ingredients==TRUE && $item_size==TRUE && $notes==TRUE ){

		    			   	   	   	   	     $found=true;

		    			   	   	   	   	     break;

		    			   	   	   	   	  }

		    			   	   	   	   }		    			   	   	   

	    			   	   	   	   }	    			   	   	   	   

	    			   	   	   } else {			    			   	   

	    			   	   	   	  if (!isset($val['addon_ids'])){

	    			   	   	   	  	  $val['addon_ids']='';

	    			   	   	   	  }	   	  

	    			   	   	   	  $compare=array_diff((array)$val['addon_ids'],(array)$item['addon_ids']);

	    			   	   	   	  if (count($compare)<=0){

	    			   	   	   	  	  if($cooking_ref==TRUE && $ingredients==TRUE && $item_size==TRUE && $notes==TRUE){

		    			   	   	   	   	  $found=true;

		    			   	   	   	   	  break;

	    			   	   	   	  	  }

	    			   	   	   	   }

	    			   	   	   }	    			   	   

	    			   	   }	    	

	    			   	   	    			   	   

	    			   }/* end foreach*/

	    			}	  

	    				    				    			

	    			/*if ( $found==false){

	    				echo 'false';

	    			} else echo "true=>$found_key";	    				   

	    			die(); */ 

	    			

	    			if ( $found==false){

	    			   $_SESSION['kr_item'][]=$item;

	    			} else {	    					    			

	    			   $_SESSION['kr_item'][$found_key]['qty']+=$item['qty'];

	    			}	    		

	    		}	    	

	    			    		

	    		$this->code=1;	    		

	    	} else $this->msg=Yii::t("default","Item id is required");	    

	    }	

	    

	    public function loadItemCart()

	    {	    		    		  

	    	if (isset($this->data['merchant_id'])){

	    		$current_merchant_id=$this->data['merchant_id'];	    

	    		if (isset($_SESSION['kr_item'])) {		

		    		if (is_array($_SESSION['kr_item']) && count($_SESSION['kr_item'])>=1){

		    			foreach ($_SESSION['kr_item'] as $key=>$temp_item) {	    				

		    				if ( $temp_item['merchant_id']!=$current_merchant_id){

		    					unset($_SESSION['kr_item'][$key]);

		    				}	    				    				

		    			}

		    		}	    	

	    		}

	    	}	    

	    		    		    		    		    		

	    	//dump($_SESSION['kr_item']);

	    	

	    	Yii::app()->functions->displayOrderHTML($this->data, isset($_SESSION['kr_item'])?$_SESSION['kr_item']:'' );

	    	$this->code=Yii::app()->functions->code;

	    	$this->msg=Yii::app()->functions->msg;

	    	$this->details=Yii::app()->functions->details;	    	

	    }	

	    

	    public function deleteItem()

	    {	    		    		    	    	

	    	if ( isset($_SESSION['kr_item'][$this->data['row']])){

	    			    		

	    		//if (is_numeric($row_api_id)){

	    		if (isset($_SESSION['kr_item'][$this->data['row']]['row_api_id'])){

	    			$row_api_id=$_SESSION['kr_item'][$this->data['row']]['row_api_id'];	 

	    			$ApiFunctions=new ApiFunctions;

	    			$ApiFunctions->deleteItemFromCart($row_api_id);

	    		}	    	

	    		

	      	   unset($_SESSION['kr_item'][$this->data['row']]);

	    	}

	    	$this->code=1;

	    	$this->msg="";

	    }

	    

	    public function setDeliveryOptions()
	    {

	       /** check if time is non 24 hour format */	    

	       if ( yii::app()->functions->getOptionAdmin('website_time_picker_format')=="12"){

	       	   if (!empty($this->data['delivery_time'])){

	       	      $this->data['delivery_time']=date("G:i", strtotime($this->data['delivery_time']));	       	      

	       	   }

	       }	    

	       	       

	       /**check if customer chooose past time */

	       if ( isset($this->data['delivery_time'])){

	       	  if(!empty($this->data['delivery_time'])){

	       	  	 $time_1=date('Y-m-d g:i:s a');

	       	  	 $time_2=$this->data['delivery_date']." ".$this->data['delivery_time'];

	       	  	 $time_2=date("Y-m-d g:i:s a",strtotime($time_2));	       	  	 

	       	  	 $time_diff=Yii::app()->functions->dateDifference($time_2,$time_1);	       	  	

	       	  	 if (is_array($time_diff) && count($time_diff)>=1){

	       	  	     if ( $time_diff['hours']>0){	       	  	     	

		       	  	     $this->msg=t("Sorry but you have selected time that already past");

		       	  	     return ;	       	  	     	

	       	  	     }	       	  	

	       	  	 }	       	  

	       	  }	       

	       }		       
            $merchant_id=isset($this->data['merchant_id'])?$this->data['merchant_id']:'';	
		  
		 
	       $_SESSION['kr_delivery_options']['delivery_type']=$this->data['delivery_type'];

	       $_SESSION['kr_delivery_options']['delivery_date']=$this->data['delivery_date'];
          
	       $_SESSION['kr_delivery_options']['delivery_time']=$this->data['delivery_time'];

	       $_SESSION['kr_delivery_options']['delivery_asap']=$this->data['delivery_asap']=="undefined"?"":1;	       	       	       
        
	       $delivery_asap = $_SESSION['kr_delivery_options']['delivery_asap'];

	       $time=isset($this->data['delivery_time'])?$this->data['delivery_time']:'';

			$current_time=date('h:i A');
			if($delivery_asap) {
				$time=$current_time;
			}
		   
		   $full_booking_time=$this->data['delivery_date']." ".$time;
		   
		   $date = $this->data['delivery_date'];
		   
		   $timechange = $date.' '.$time;

          if($this->data['delivery_type']=='metro') {
              //clear address
              $_SESSION['kr_search_address']=NULL;

			  //client lat lng
			  $_SESSION['client_location']=NULL;
          }


		    /**check if customer order after Max Limit */
		   $res = Yii::app()->functions->getRecentMaxOrders($merchant_id,$date,$time,$delivery_asap);
		      $max_order_limit = $res[0]['max_order'];
	          $no_of_order = $res[0]['count_max_order'];
			    $dilivery_time = $res[0]['dilivery_time'];
			    //$stmt = $res[0]['stmt'];
		   if(!empty($no_of_order))
		   { 
			  if($max_order_limit>$no_of_order || $dilivery_time  == '2')    // 
			  {
				 // send order
			     /*  $this->msg.= t("You cannot make order at this timeeee->".$max_order_limit.'>'.$no_of_order.'==========>'.$dilivery_time.'==========>'.$stmt);
 			       return ; */
			  }
			  else
			  {
				  $this->msg.= t("You cannot make order at this time");
			      return ;
			  }
		   }
		 /*  else
		   {
			  
			    $this->msg.= t("You cannot make order at this time".$max_order_limit.'>'.$no_of_order.'<---->'.$dilivery_time.'==========>'.$stmt);
			      return ;
			  }*/
		    /**check if customer order after Max Limit */    

	      

		   $full_booking_day=strtolower(date("D",strtotime($full_booking_time)));			

		   $booking_time=date('h:i A',strtotime($full_booking_time));			

		   if (empty($time)){

		   	  $booking_time='';

		   }

			// check Delivery Estimation
			if(!$delivery_asap) {
				$deliveryEstimationMinutes = FunctionsV3::getDeliveryEstimation($merchant_id);
				$deliveryEstimationMinutes = is_numeric($deliveryEstimationMinutes)?$deliveryEstimationMinutes:0;
				$current = date('Y-m-d h:i');
				$diff=(strtotime($full_booking_time) - strtotime($current))/60;
				$readyTime = new DateTime($current);
				$readyTime->add(new DateInterval('PT' . $deliveryEstimationMinutes . 'M'));
				$readyStamp = $readyTime->format('Y-m-d h:i');
				if($diff < $deliveryEstimationMinutes) {
					$this->msg=t("Sorry but the restaurant need to prepare and delivery the food for about").$deliveryEstimationMinutes
						." ".t("minutes").", ".t("please select the booking time later than").$readyStamp;
					return;
				}
			}

		   if ( !Yii::app()->functions->isMerchantOpenTimes($merchant_id,$full_booking_day,$booking_time)){	

				$date_close=date("F,d l Y h:ia",strtotime($full_booking_time));

				$date_close=Yii::app()->functions->translateDate($date_close);

				$this->msg=t("Sorry but we are closed on")." ".$date_close;

				$this->msg.="<br/>";

				$this->msg.=t("Please check merchant opening hours");

			    return ;

			}





			$this->code=1;$this->msg=Yii::t("default","OK");

	       $this->details=Yii::app()->createUrl('store/checkout');

	    }

	    

	    public function clientRegistration()

	    {	    	

	    	

	    	/** check if admin has enabled the google captcha*/    	    	

	    	if ( getOptionA('captcha_customer_signup')==2){

	    		if ( GoogleCaptcha::checkCredentials()){

	    			if ( !GoogleCaptcha::validateCaptcha()){

	    				$this->msg=GoogleCaptcha::$message;

	    				return false;

	    			}	    		

	    		}	    	

	    	} 

	    	

	    	/*add confirm password */

	    	if (isset($this->data['cpassword'])){

	    		if ($this->data['cpassword'] != $this->data['password']){

	    			$this->msg=t("Confirm password does not match");

	    			return ;

	    		}	    	

	    	}	    	

	    	

	    	/*check if email address is blocked*/

	    	if ( FunctionsK::emailBlockedCheck($this->data['email_address'])){

	    		$this->msg=t("Sorry but your email address is blocked by website admin");

	    		return ;

	    	}	    

	    	

	    	if ( FunctionsK::mobileBlockedCheck($this->data['contact_phone'])){

	    		$this->msg=t("Sorry but your mobile number is blocked by website admin");

	    		return ;

	    	}	    	

	    		    	

	    	/*check if mobile number already exist*/

	        $functionk=new FunctionsK();

	        if ( !$res=Yii::app()->functions->isClientExist($this->data['email_address']) ){

		        if ( $functionk->CheckCustomerMobile($this->data['contact_phone'])){

		        	$this->msg=t("Sorry but your mobile number is already exist in our records");

		        	return ;

		        }	  

	        }

	        	        

	    	if ( !$res=Yii::app()->functions->isClientExist($this->data['email_address']) ){

	    		

	    		$p = new CHtmlPurifier();

	    		

	    		$params=array(

	    		  'first_name'=>$p->purify($this->data['first_name']),

	    		  'last_name'=>$p->purify($this->data['last_name']),

	    		  'email_address'=>$p->purify($this->data['email_address']),

	    		  'password'=>md5($this->data['password']),

	    		  'date_created'=>date('c'),

	    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    		  'contact_phone'=>$p->purify($this->data['contact_phone'])

	    		);

	    		

	    		/** send verification code */

                $verification=Yii::app()->functions->getOptionAdmin("website_enabled_mobile_verification");	    

		    	if ( $verification=="yes"){

		    		$code=Yii::app()->functions->generateRandomKey(5);

		    		Yii::app()->functions->sendVerificationCode($this->data['contact_phone'],$code);

		    		$params['mobile_verification_code']=$code;

		    		$params['status']='pending';

		    	}	    	  

		    	

		    	/*send email verification added on version 3*/

		    	$email_code=Yii::app()->functions->generateCode(10);

		    	$email_verification=getOptionA('theme_enabled_email_verification');

		    	if ($email_verification==2){

		    		$params['email_verification_code']=$email_code;

		    		$params['status']='pending';

		    		FunctionsV3::sendEmailVerificationCode($params['email_address'],$email_code,$params);

		    	}

	    	

		    	/** update 2.3*/

		    	if (isset($this->data['custom_field1'])){

		    		$params['custom_field1']=!empty($this->data['custom_field1'])?$this->data['custom_field1']:'';

		    	}

		    	if (isset($this->data['custom_field2'])){

		    		$params['custom_field2']=!empty($this->data['custom_field2'])?$this->data['custom_field2']:'';

		    	}

		    			    	

	    		if ( $this->insertData("{{client}}",$params)){

	    			$this->details=Yii::app()->db->getLastInsertID();	    		

	    			$this->code=1;

	    			$this->msg=Yii::t("default","Registration successful");



	    			if ( $verification=="yes"){	    				

	    				$this->msg=t("We have sent verification code to your mobile number");

	    			} elseif ( $email_verification ==2 ){ 

	    				$this->msg=t("We have sent verification code to your email address");

	    			} else {

	    			   Yii::app()->functions->clientAutoLogin($this->data['email_address'],$this->data['password']);

	    			}	    			

	    			

	    			/*sent welcome email*/

	    			FunctionsK::sendCustomerWelcomeEmail($this->data);

	    				    			

	    			/*POINTS PROGRAM*/	    			

	    			if (FunctionsV3::hasModuleAddon("pointsprogram")){

	    			   PointsProgram::signupReward($this->details);

	    			}

	    			

	    		} else $this->msg=Yii::t("default","Something went wrong during processing your request. Please try again later.");

	    	} else {	    			    		

	    		$verification=Yii::app()->functions->getOptionAdmin("website_enabled_mobile_verification");	    

		    	if ( $verification=="yes"){

		    		if (strlen($res['mobile_verification_code'])>=2 && $res['status']=='pending'){

		    			$this->msg=t("Found existing registration");

		    			$this->code=1;

		    			$this->details=$res['client_id'];

		    			return ;

		    		}		    	

		    	}

	    	   $this->msg=Yii::t("default","Sorry but your email address already exist in our records.");

	    	}

	    }		    	 

	    

	    public function clientRegistrationModal()

	    {

	    	

	    	/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			}  	



	    	/** check if admin has enabled the google captcha*/    	    	

	    	/*if ( getOptionA('captcha_customer_signup')==2){

	    		if ( GoogleCaptcha::checkCredentials()){

	    			if ( !GoogleCaptcha::validateCaptcha()){

	    				$this->msg=GoogleCaptcha::$message;

	    				return false;

	    			}	    		

	    		}	    	

	    	}*/ 	    		    		    

	    	$this->clientRegistration();	    	

	    }   

	    

	    public function addCreditCard()

	    {	    	    		    	

	    	$cid=Yii::app()->functions->getClientId();	    	

	    	if (empty($cid)){	    		

	    		$cid=$_SESSION['guest_client_id'];

	    	}	



	    	if (empty($cid)){

	    		$this->msg=t("ID is empty");

	    		return false;

	    	}

	    	    	

	    	if (!Yii::app()->functions->getCCbyCard($this->data['credit_card_number'],$cid) ){

		    	$params=$this->data;

		    	unset($params['action']);

		    	unset($params['currentController']);

		    	$params['client_id']=$cid;

		    	$params['date_created']=date('c');

		    	$params['ip_address']=$_SERVER['REMOTE_ADDR'];			    	

		    	if ( $this->insertData("{{client_cc}}",$params)){

		    		$this->code=1;

		    		$this->msg=Yii::t("default","Credit Card Successfuly added");

		    	} else $this->msg=Yii::t("default","ERROR: Cannot insert records");

	    	} else $this->msg=Yii::t("default","Credit card number already exist in you credit card list");

	    }

	    

	    public function loadCreditCardList()

	    {	    	

	    	$client_id=Yii::app()->functions->getClientId();	    	

	    	if (empty($client_id)){	    			    		

	    		if ($this->data['is_guest_checkout']==2){	    			

	    			if (empty($_SESSION['guest_client_id'])){

	    		       $client_id=getNextClientID();	    		       

	    		       $_SESSION['guest_client_id']=$client_id;

	    			} else $client_id=$_SESSION['guest_client_id'];

	    		}

	    	}	 

	    		    		    	

	    	if (empty($client_id)){

	    		$this->msg=Yii::t("default","No credit card yet");	 

	    		return false;

	    	}

	    	

	    	//dump($client_id);

	    		    	

	    	$data='';

	    	$stmt="SELECT * FROM

	    	{{client_cc}}

	    	WHERE

	    	client_id='".$client_id."'

	    	ORDER BY cc_id DESC

	    	";

	    	if ( $res=$this->rst($stmt)){

	    	   	$this->code=1;	    	   	

	    	   	foreach ($res as $val) {	    	   		

	    	   		$data[]=array(

	    	   		  'cc_id'=>$val['cc_id'],

	    	   		  'credit_card_number'=>Yii::app()->functions->maskCardnumber($val['credit_card_number'])

	    	   		);

	    	   	}

	    	   	$this->details=$data;

	    	} else $this->msg=Yii::t("default","No credit card yet");	    	

	    }	

	    

	    public function loadCreditCardListMerchant()

	    {

	    	$data='';

	    	$stmt="SELECT * FROM

	    	{{merchant_cc}}

	    	WHERE

	    	merchant_id='".$this->data['merchant_id']."'

	    	ORDER BY mt_id DESC

	    	";

	    	if ( $res=$this->rst($stmt)){

	    	   	$this->code=1;	    	   	

	    	   	foreach ($res as $val) {	    	   		

	    	   		$data[]=array(

	    	   		  'mt_id'=>$val['mt_id'],

	    	   		  'credit_card_number'=>Yii::app()->functions->maskCardnumber($val['credit_card_number'])

	    	   		);

	    	   	}

	    	   	$this->details=$data;

	    	} else $this->msg=Yii::t("default","No credit card yet");	    	

	    }		    

	    

	    public function clientLogin()

	    {	

	    		    	

	    	/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			}  	

	    	

	    	/** check if admin has enabled the google captcha*/    	    	

	    	if ( $this->data['action']=="clientLogin" || $this->data['action']=="clientLoginModal"){

		    	if ( getOptionA('captcha_customer_login')==2){

		    		if ( GoogleCaptcha::checkCredentials()){

		    			if ( !GoogleCaptcha::validateCaptcha()){	    				

		    				$this->msg=GoogleCaptcha::$message;

		    				return false;

		    			}	    		

		    		}	    	

		    	}

	    	}

	    		    	

	    	/*check if email address is blocked by admin*/	    	

	    	if ( FunctionsK::emailBlockedCheck($this->data['username'])){

	    		$this->msg=t("Sorry but your email address is blocked by website admin");

	    		return ;

	    	}	    	

	    		    	

	    	if (!isset($this->data['password_md5'])){

	    		$this->data['password_md5']='';

	    	}	    

	    	if ( Yii::app()->functions->clientAutoLogin($this->data['username'],

	    	    $this->data['password'],$this->data['password_md5']) ){

	    		$this->code=1;

	    		$this->msg=Yii::t("default","Login Okay");

	    	} else {

	    		/*check if user has pending application like mobile verification and email*/

	    		if ( $res=FunctionsV3::login($this->data['username'],$this->data['password'])){

	    			if (strlen($res['mobile_verification_code'])>=2 && $res['status']=='pending'){

	    				$this->msg=t("Found existing registration");

	    				$this->code=3;

	    				$this->details=$res['client_id'];

	    			} elseif (strlen($res['email_verification_code'])>=2 && $res['status']=='pending' ){	

	    				$this->code=4;

	    				$this->details=$res['client_id'];

	    				$this->msg=t("we have sent you email with verification");

	    			} else $this->msg=Yii::t("default","Login Failed. Either username or password is incorrect");

	    		} else $this->msg=Yii::t("default","Login Failed. Either username or password is incorrect"); 

	    	}

	    }

	    

	    public function clientLoginModal()

	    {

	    	$this->clientLogin();

	    }	

	    

	    public function placeOrder()

	    {

	    	$mtid=$_SESSION['kr_merchant_id'];

	    	$mt_timezone=Yii::app()->functions->getOption("merchant_timezone",$mtid);	   	   	    	

	    	if (!empty($mt_timezone)){

	    		Yii::app()->timeZone=$mt_timezone;

	    	}


	    	/*check if merchant has enabled Order sms verification*/

	    	if (isset($this->data['client_order_sms_code'])){

	    		if (!empty($this->data['client_order_sms_code'])){

	    			if (!FunctionsK::validateOrderSMSCode($this->data['contact_phone'],$this->data['client_order_sms_code'],

	    			$this->data['client_order_session'])){	    				

	    				$this->msg=t("Sorry but you have input invalid order sms code");

	    				return ;

	    			}	    		

	    		}

	    	}	    	


	    	/** re-check delivery address */	    	

	    	if ( $this->data['delivery_type']=="delivery" || $this->data['delivery_type']=="metro" ){

		    	/*Version 3 change getting of delivery fee*/

		    	if (!FunctionsV3::reCheckDelivery($mtid,$this->data)){

//		    		$mt_delivery_miles=getOption($mtid,'merchant_delivery_miles');

//		    		$distance_type=FunctionsV3::getMerchantDistanceType($mtid);

//		    		$unit=$distance_type=="M"?t("miles"):t("kilometers");

		    		$this->msg=t("Please input the correct address information");

		    		return ;

		    	}

	    	} 	    	

	    	/** re-check delivery address */

	    		    		    		    		    		    	  

	    	/*guest checkout*/    			    		    		    	 

	    	if (isset($this->data['is_guest_checkout'])){

	    		$Validator=new Validator;

	    		

	    		if (empty($this->data['email_address'])){

	    			$this->data['email_address']=str_replace(" ","_",$this->data['first_name']).

	    			Yii::app()->functions->generateRandomKey()."@".$_SERVER['HTTP_HOST'];

	    		}	    	

	    			    			    	

	    		if ( !$res_check=Yii::app()->functions->isClientExist($this->data['email_address']) ){	  

	    			$this->data['password']=isset($this->data['password'])?$this->data['password']:'';

	    			if (empty($this->data['password'])){

	    				$this->data['password']=Yii::app()->functions->generateRandomKey();

	    			}	    		

	    			

	    			/** check if admin has enabled the google captcha*/    	    	

			    	/*if ( getOptionA('captcha_customer_signup')==2){

			    		if ( GoogleCaptcha::checkCredentials()){

			    			if ( !GoogleCaptcha::validateCaptcha()){

			    				$this->msg=GoogleCaptcha::$message;

			    				return false;

			    			}	    		

			    		}	    	

			    	}*/

			    		    			

	    			/*check if email address is blocked*/

			    	if ( FunctionsK::emailBlockedCheck($this->data['email_address'])){

			    		$this->msg=t("Sorry but your email address is blocked by website admin");

			    		return ;

			    	}

			    	

			    	if ( FunctionsK::mobileBlockedCheck($this->data['contact_phone'])){

			    		$this->msg=t("Sorry but your mobile number is blocked by website admin");

			    		return ;

			    	}	  

			    	

			    	$functionk=new FunctionsK();

			        /*if ( $functionk->CheckCustomerMobile($this->data['contact_phone'])){

			        	$this->msg=t("Sorry but your mobile number is already exist in our records");

			        	return ;

			        }*/

	    			

	    			$params=array(

		    		  'first_name'=>$this->data['first_name'],

		    		  'last_name'=>$this->data['last_name'],

		    		  'email_address'=>$this->data['email_address'],

		    		  'password'=>md5($this->data['password']),

		    		  'street'=>$this->data['client_address'],

//		    		  'city'=>$this->data['city'],
//
//		    		  'state'=>$this->data['state'],
//
//		    		  'zipcode'=>$this->data['zipcode'],

		    		  'contact_phone'=>$this->data['contact_phone'],

		    		  'location_name'=>isset($this->data['location_name'])?$this->data['location_name']:'',

		    		  'date_created'=>date('c'),

		    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],

		    		  'is_guest'=>1

		    		);

		    				    		

		    				    		

			    	if ( $this->insertData("{{client}}",$params)){		    			

		    			Yii::app()->functions->clientAutoLogin($this->data['email_address'],$this->data['password']);

		    		} else $Validator->msg[]=t("Something went wrong during processing your request. Please try again later.");

	    		} else $Validator->msg[]=t("Sorry but your email address already exist in our records.");

	    		

	    		if (!$Validator->validate()){

	    			$this->msg=$Validator->getErrorAsHTML();

	    			return ;

	    		}	    	

	    	}	    

	    	/*guest checkout*/	    	

	    	

	    	$this->data['merchant_id']=$_SESSION['kr_merchant_id'];	 

	    	

	    	$default_order_status=Yii::app()->functions->getOption("default_order_status",$_SESSION['kr_merchant_id']);	    		    	

	    	$order_item=$_SESSION['kr_item'];

	    	if (is_array($order_item) && count($order_item)>=1){	

	    		//dump($this->data);    		

	    		

	    		/** card fee condition */

	    		$card_fee='';

	    		switch ($this->data['payment_opt'])

	    		{

	    			case "pyp":

	    				if ( Yii::app()->functions->isMerchantCommission($this->data['merchant_id'])){

	    					$card_fee=Yii::app()->functions->getOptionAdmin('admin_paypal_fee');

	    				} else {

	    					$card_fee=Yii::app()->functions->getOption('merchant_paypal_fee',

	    					$this->data['merchant_id']);

	    				}

	    			    break;

	    			default:	    				

	    				break;	

	    		}	    	

	    			    		

	    		if ( !empty($card_fee) && $card_fee>=0.1){	    			

	    			$this->data['card_fee']=$card_fee;	    			

	    		}

	    		/** end card fee */

	    		

	    		Yii::app()->functions->displayOrderHTML($this->data,$_SESSION['kr_item']);

	    		if ( Yii::app()->functions->code==1){

	    			//dump("<h2>RESP</h2>");

	    			$raw=Yii::app()->functions->details['raw'];	    				    			

	    				    			

	    			if (is_array($raw) && count($raw)>=1){	    				

	    				$params=array(

	    				  'merchant_id'=>$this->data['merchant_id'],

	    				  'client_id'=>Yii::app()->functions->getClientId(),

	    				  'json_details'=>json_encode($order_item),

	    				  'trans_type'=>isset($_SESSION['kr_delivery_options']['delivery_type'])?$_SESSION['kr_delivery_options']['delivery_type']:'',

	    				  'payment_type'=>isset($this->data['payment_opt'])?$this->data['payment_opt']:'',

	    				  'sub_total'=>isset($raw['total']['subtotal'])?$raw['total']['subtotal']:'',

	    				  'tax'=>isset($raw['total']['tax'])?$raw['total']['tax']:'',

	    				  'taxable_total'=>isset($raw['total']['taxable_total'])?$raw['total']['taxable_total']:'',

	    				  'total_w_tax'=>isset($raw['total']['total'])?$raw['total']['total']:'',

	    				  'delivery_charge'=>isset($raw['total']['delivery_charges'])?$raw['total']['delivery_charges']:'',

	    				  'delivery_date'=>isset($_SESSION['kr_delivery_options']['delivery_date'])?$_SESSION['kr_delivery_options']['delivery_date']:'',

	    				  'delivery_time'=>isset($_SESSION['kr_delivery_options']['delivery_time'])?$_SESSION['kr_delivery_options']['delivery_time']:'',

	    				  'delivery_asap'=>isset($_SESSION['kr_delivery_options']['delivery_asap'])?$_SESSION['kr_delivery_options']['delivery_asap']:'',

	    				  'date_created'=>date('c'),

	    				  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    				  'delivery_instruction'=>isset($this->data['delivery_instruction'])?$this->data['delivery_instruction']:'',

	    				  'cc_id'=>isset($this->data['cc_id'])?$this->data['cc_id']:'',

	    				  'order_change'=>isset($this->data['order_change'])?$this->data['order_change']:'',

	    				  'payment_provider_name'=>isset($this->data['payment_provider_name'])?$this->data['payment_provider_name']:'',

	    				);	    			

	    				

	    				/*FIXED ORDER STATUS*/	    					    				

if ($this->data['payment_opt']=="cod" || $this->data['payment_opt']=="pyr" || $this->data['payment_opt']=="ccr" || $this->data['payment_opt']=="ocr" ){

	    					if (!empty($default_order_status)){

	    					    $params['status']=$default_order_status;

	    				    } else $params['status']="pending";

	    				} else $params['status']=initialStatus();	    			

	    				

	    				if ($this->data['payment_opt']=="obd"){

	    					$params['status']="pending";

	    				}	    			

	    					    					    				

	    				/*PROMO*/	    				

	    				if (isset($raw['total']['discounted_amount'])){

		    				if ($raw['total']['discounted_amount']>=0.1){	    					

		    				    $params['discounted_amount']=$raw['total']['discounted_amount'];

		    				    $params['discount_percentage']=$raw['total']['merchant_discount_amount'];

		    				}

	    				}

	    				

	    					    					    				

    					/*VOUCHER*/

    					$has_voucher=false;

                        if (isset($_SESSION['voucher_code'])){		         	

				         	if (is_array($_SESSION['voucher_code'])){					         		

			         			$params['voucher_amount']=$_SESSION['voucher_code']['amount'];

			         			$params['voucher_code']=$_SESSION['voucher_code']['voucher_name'];

			         			$params['voucher_type']=$_SESSION['voucher_code']['voucher_type'];

			         			$has_voucher=true;

				         	}		         

			            }    		

			            			          

			            

/** add tips */			            

$params['cart_tip_percentage']=isset($this->data['cart_tip_percentage'])?$this->data['cart_tip_percentage']:'';

$params['cart_tip_value']=isset($this->data['cart_tip_value'])?$this->data['cart_tip_value']:'';                        			            

                        /*if (isset($this->data['cart_tip_value'])){

                        	if (is_numeric($this->data['cart_tip_value'])){

                        		$params['total_w_tax']=$params['total_w_tax']+$this->data['cart_tip_value'];

                        	}                        

                        }*/	

			            

			            /*Commission*/

			            if ( Yii::app()->functions->isMerchantCommission($this->data['merchant_id'])){

			            	$admin_commision_ontop=Yii::app()->functions->getOptionAdmin('admin_commision_ontop');

			            	if ( $com=Yii::app()->functions->getMerchantCommission($this->data['merchant_id'])){

			            		$params['percent_commision']=$com;			            		

			            		$params['total_commission']=($com/100)*$params['total_w_tax'];

			            		$params['merchant_earnings']=$params['total_w_tax']-$params['total_commission'];

			            		if ( $admin_commision_ontop==1){

			            			$params['total_commission']=($com/100)*$params['sub_total'];

			            			$params['commision_ontop']=$admin_commision_ontop;			            		

			            			$params['merchant_earnings']=$params['sub_total']-$params['total_commission'];

			            		}

			            	}			

			            	

			            	/** check if merchant commission is fixed  */

			            	$merchant_com_details=Yii::app()->functions->getMerchantCommissionDetails($this->data['merchant_id']);

			            	

			            	if ( $merchant_com_details['commision_type']=="fixed"){

			            		$params['percent_commision']=$merchant_com_details['percent_commision'];

			            		$params['total_commission']=$merchant_com_details['percent_commision'];

			            		$params['merchant_earnings']=$params['total_w_tax']-$merchant_com_details['percent_commision'];

			            		

			            		if ( $admin_commision_ontop==1){			            		

			            		    $params['merchant_earnings']=$params['sub_total']-$merchant_com_details['percent_commision'];

			            		}

			            	}            

			            }/** end commission condition*/

			            

			            // fixed packaging by saving the packaging charge to db

			            $merchant_packaging_charge=Yii::app()->functions->getOption('merchant_packaging_charge',$mtid);

			            if ( $merchant_packaging_charge>0){

			            	$params['packaging']=$merchant_packaging_charge;			            	

			            				            	

			            	/** if packaging is incremental*/

			            	if ( Yii::app()->functions->getOption("merchant_packaging_increment",$mtid)==2){

			            		$total_cart_item=0;

			            		foreach ($raw['item'] as $cart_item_x) {

		        			            $total_cart_item+=$cart_item_x['qty'];

		        		        }		                

		                        $params['packaging']=$total_cart_item*$merchant_packaging_charge;

			            	}

			            }	    						          

			            

			            /** card fee */

			            if ( !empty($card_fee) && $card_fee>=0.1){	    				    			        

	    			        $params['card_fee']=$card_fee;

	    		        }

	    		        	    		        	    		        	    		       

	    		        /*if has address book selected*/

	    		        if ( isset($this->data['address_book_id'])){

	    		        	if ($address_book=Yii::app()->functions->getAddressBookByID($this->data['address_book_id'])){

	    		        		$this->data['street']=$address_book['street'];

//	    		        		$this->data['city']=$address_book['city'];

//	    		        		$this->data['state']=$address_book['state'];

//	    		        		$this->data['zipcode']=$address_book['zipcode'];

	    		        		$this->data['location_name']=$address_book['location_name'];

	    		        	}	    		        

	    		        }	    		   

	    		        


	    		        $country_name='';

	    		        

	    		        if ( Yii::app()->functions->getOptionAdmin('website_enabled_map_address')==2){

		    		        if (isset($this->data['map_address_toogle'])){

		    		        	if ( $this->data['map_address_toogle']==2){

		    		        		/*$geo_res=geoCoding($this->data['map_address_lat'],

		    		        		$this->data['map_address_lng']);*/

		    		        		

		    		        		$geo_res=FunctionsV3::latToAdress($this->data['map_address_lat'],

		    		        		$this->data['map_address_lng']);

		    		        	    if ($geo_res){		    		        			

		    		        			$this->data['client_address']=isset($geo_res['address'])?$geo_res['address']." ":'';

//		    		        			$this->data['city']=isset($geo_res['city'])?$geo_res['city']:'';

//		    		        			$this->data['state']=isset($geo_res['state'])?$geo_res['state']:'';

//		    		        			$this->data['zipcode']=isset($geo_res['zip'])?$geo_res['zip']:'';

		    		        			

		    		        			$country_code=isset($geo_res['country_code'])?$geo_res['country_code']:'';

		    		        			$country_name=isset($geo_res['country'])?$geo_res['country']:'';

		    		        		} else {

		    		        			$this->msg=t("Sorry but something wrong when geocoding your address");

		    		        			return false;

		    		        		}		  

		    		        	}	    		        

		    		        }	    		

	    		        }	

	    		        

	    		        /*dump($country_code);

	    		        dump($this->data);*/

	    		        /*dump($params);*/	    		        

	    		        

	    		        /** check if item is taxable*/ 

	    		        if (Yii::app()->functions->getOption("merchant_tax_charges",$mtid)==2){

	    		        	$params['donot_apply_tax_delivery']=2;

	    		        }	    		        



	    		        /*POINTS PROGRAM*/   	    		        

	    		        if (FunctionsV3::hasModuleAddon("pointsprogram")){

		    		        if (isset($_SESSION['pts_redeem_amt'])){

		    		        	$params['points_discount']=$_SESSION['pts_redeem_amt'];

		    		        }

	    		        }

	    		        

	    				if ( $this->insertData("{{order}}",$params)){

		    				$order_id=Yii::app()->db->getLastInsertID();

		    				

		    				

		    				/*POINTS PROGRAM*/    		        

		    				if (FunctionsV3::hasModuleAddon("pointsprogram")){

			    		        PointsProgram::saveEarnPoints(

			    		           isset($_SESSION['pts_earn'])?$_SESSION['pts_earn']:'',

			    		           Yii::app()->functions->getClientId(),

			    		           $this->data['merchant_id'],

			    		           $order_id,

			    		           $this->data['payment_opt']

			    		        );

			    		        

			    		        if (isset($_SESSION['pts_redeem_points'])){

				    		        PointsProgram::saveExpensesPoints(

				    		          $_SESSION['pts_redeem_points'],

				    		          $_SESSION['pts_redeem_amt'],

				    		          Yii::app()->functions->getClientId(),

				    		          $this->data['merchant_id'],

				    		          $order_id,

				    		          $this->data['payment_opt']

				    		        );

			    		        }

		    				}

		    		       

		    				/** add delivery address */

		    				if ( $this->data['delivery_type']=="delivery" || $this->data['delivery_type']=="metro"){

			    				$params_address=array(

			    				  'order_id'=>$order_id,

			    				  'client_id'=>Yii::app()->functions->getClientId(),

			    				  'street'=>isset($this->data['client_address'])?$this->data['client_address']:'',

//			    				  'city'=>isset($this->data['city'])?$this->data['city']:'',

//			    				  'state'=>isset($this->data['state'])?$this->data['state']:'',

//			    				  'zipcode'=>isset($this->data['zipcode'])?$this->data['zipcode']:'',

			    				  'location_name'=>isset($this->data['location_name'])?$this->data['location_name']:'',

			    				  'country'=>Yii::app()->functions->adminCountry(),

			    				  'date_created'=>date('c'),

			    				  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			    				  'contact_phone'=>$this->data['contact_phone']    				  

			    				);		

			    				

			    				if (!empty($country_name)){

			    					$params_address['country']=$country_name;

			    				}		    		

			    				if(isset($this->data['map_address_lat'])){

			    					$params_address['google_lat']=$this->data['map_address_lat'];

			    				}

			    				if(isset($this->data['map_address_lng'])){

			    					$params_address['google_lng']=$this->data['map_address_lng'];

			    				}

			    				if(isset($geo_res)){

			    				if(isset($geo_res['formatted_address'])){

			    					$params_address['formatted_address']=$geo_res['formatted_address'];

			    				}

			    				}

		    							    			

			    				$this->insertData("{{order_delivery_address}}",$params_address);

			    				

			    				/** quick update mobile*/

			    				$params_mobile=array(

			    				  'contact_phone'=>$this->data['contact_phone'],

			    				  'location_name'=>isset($this->data['location_name'])?$this->data['location_name']:''

			    				 );

			    				$this->updateData("{{client}}",$params_mobile,'client_id',

			    				Yii::app()->functions->getClientId());

		    				}

		    				

		    				/** save to address book*/

		    				if (!isset($this->data['saved_address'])){

		    					$this->data['saved_address']='';

		    				}

				            if ( $this->data['saved_address']==2){

				            	$sql_up="UPDATE {{address_book}}

					     		SET as_default='1' 	     		

					     		";

					     		$this->qry($sql_up);

				            	$params_i=array(

				            	  'client_id'=>Yii::app()->functions->getClientId(),

				            	  'street'=>$this->data['client_address'],

//				            	  'city'=>$this->data['city'],

//				            	  'state'=>$this->data['state'],

//				            	  'zipcode'=>$this->data['zipcode'],

				            	  'location_name'=>$this->data['location_name'],

				            	  'date_created'=>date('c'),

				            	  'ip_address'=>$_SERVER['REMOTE_ADDR'],

				            	  'country_code'=>Yii::app()->functions->adminCountry(true),

				            	  'as_default'=>2

				            	);

				            	$this->insertData("{{address_book}}",$params_i);

				            }				            			            

		    						    				    				

		    			   /*VOUCHER*/

                            if ($has_voucher==TRUE){

                                Yii::app()->functions->updateVoucher($_SESSION['voucher_code']['voucher_name'],

				         			Yii::app()->functions->getClientId(),$order_id);				         

				            }		

				            	

		    				foreach ($raw['item'] as $val) {		    					

		    					$params_order_details=array(

		    					  'order_id'=>$order_id,

		    					  'client_id'=>Yii::app()->functions->getClientId(),

		    					  'item_id'=>isset($val['item_id'])?$val['item_id']:'',

		    					  'item_name'=>isset($val['item_name'])?$val['item_name']:'',

		    					  'order_notes'=>isset($val['order_notes'])?$val['order_notes']:'',

		    					  'normal_price'=>isset($val['normal_price'])?$val['normal_price']:'',

		    					  'discounted_price'=>isset($val['discounted_price'])?$val['discounted_price']:'',

		    					  'size'=>isset($val['size_words'])?$val['size_words']:'',

		    					  'qty'=>isset($val['qty'])?$val['qty']:'',		    					  

		    					  'addon'=>isset($val['sub_item'])?json_encode($val['sub_item']):'',

		    					  'cooking_ref'=>isset($val['cooking_ref'])?$val['cooking_ref']:'',

		    					  'ingredients'=>isset($val['ingredients'])?json_encode($val['ingredients']):'',

		    					  'non_taxable'=>isset($val['non_taxable'])?$val['non_taxable']:1

		    					);

		    					$this->insertData("{{order_details}}",$params_order_details);

		    				}

		    				

		    				$this->code=1;

		    				

		    				/*Driver app*/

						    if (FunctionsV3::hasModuleAddon("driver")){

							   Yii::app()->setImport(array(			

								  'application.modules.driver.components.*',

							   ));

							   Driver::addToTask($order_id);

						    }

		    						    				

		    				switch ($this->data['payment_opt'])

		    				{

		    					case "cod":

		    					case "ccr":				    					

		    					    $this->msg=Yii::t("default","Your order has been placed.");

		    					    

		    					    if (isset($_SESSION['guest_client_id'])){

			    					    if (isset($this->data['is_guest_checkout'])){

			    					    	$stmt="UPDATE

			    					    	{{client_cc}}

			    					    	SET client_id = ".q(Yii::app()->functions->getClientId())."

			    					    	WHERE

			    					    	client_id = ".q($_SESSION['guest_client_id'])."

			    					    	";			    					    	

			    					    	$this->qry($stmt);

			    					    }

		    					    }

		    					    

		    						break;

		    					case "obd":	    						

		    							    					

		    					    /** Send email if payment type is Offline bank deposit*/

							    	$functionsk=new FunctionsK();

							    	$functionsk->MerchantSendBankInstruction($mtid,$params['total_w_tax'],$order_id);

							    	

		    					    $this->msg=Yii::t("default","Your order has been placed.");

		    						break;

		    					default:	

		    					    $this->msg=Yii::t("default","Please wait while we redirect...");

		    					    break;

		    				}

		    					    				

		    				$this->details=array(

		    				  'order_id'=>$order_id,

		    				  'payment_type'=>$this->data['payment_opt']

		    				);

		    				

		    				//Yii::app()->functions->updateClient($this->data);

		    				

	    				} else $this->msg=Yii::t("default","ERROR: Cannot insert records.");

	    			} else $this->msg=Yii::t("default","ERROR: Something went wrong");	    		

	    		} else $this->msg=Yii::app()->functions->msg;

	    	} else $this->msg=Yii::t("default","Sorry but your order is empty");	    

	    }

	    

	    public function addRating()

	    {	    	

	    	$DbExt=new DbExt;

	    	

	    		    	    	

	    	if ( empty($this->data['merchant_id'])){

	    		$this->msg=Yii::t("default","Merchant ID is missing");

	    	    return ;

	    	}	    

	    	

	    	

	    	if (Yii::app()->functions->isClientLogin()){	    		

	    		$client_id=Yii::app()->functions->getClientId();	    		 	   

	    	    $params=array(

	    	      'merchant_id'=>$this->data['merchant_id'],

	    	      'ratings'=>$this->data['value'],

	    	      'client_id'=>$client_id,

	    	      'date_created'=>date('c'),

	    	      'ip_address'=>$_SERVER['REMOTE_ADDR']

	    	    );	    	    

	    	    

	    	    /** check if user has bought from the merchant*/	    	    

	    	    if ( Yii::app()->functions->getOptionAdmin('website_reviews_actual_purchase')=="yes"){

		    	    $functionk=new FunctionsK();

		    	    if (!$functionk->checkIfUserCanRateMerchant($client_id,$this->data['merchant_id'])){

		    	    	$this->code=3;

		    	    	$this->msg=t("Reviews are only accepted from actual purchases!");

		    	    	return ;

		    	    }	    		    	    

	    	    }

	    	    	    	    

	    	    if ( !$res=Yii::app()->functions->isClientRatingExist($this->data['merchant_id'],$client_id) ){	    

	    	    	$DbExt->insertData("{{rating}}",$params);

	    	    	$this->code=1;	 

	    	    	$this->msg=Yii::t("default","Successful"); 

	    	    } else {	    	    	

	    	    	$rating_id=$res['id'];	    	    	

	    	    	$update=array(

	    	    	  'ratings'=>$this->data['value'],

	    	    	   'date_created'=>date('c'),

	    	           'ip_address'=>$_SERVER['REMOTE_ADDR']

	    	        );

	    	    	if ( $DbExt->updateData("{{rating}}",$update,'id',$rating_id) ){

	    	    		$this->code=1;	 

	    	    	    $this->msg=Yii::t("default","Successful"); 

	    	    	} else {

	    	    		$this->msg=Yii::t("default","Sorry there's an error white updating you rating.");	    	    

	    	    		$this->code=3;

	    	    	}	    	    

	    	    }	    	

	    	} else $this->msg=Yii::t("default","Sorry but you need to login before you can make a rating.");	    	

	    }

	    

	    public function loginModal()

	    {

	    	require_once 'login-modal.php';

	    	die();

	    }



	    public function loadTopMenu()

	    {	    	

	    	ob_start();	

	    	if ( Yii::app()->functions->isClientLogin()):

	    	?>

	    	    <div class="uk-button-dropdown" data-uk-dropdown="{mode:'click'}">

			    <button class="uk-button">

			       <i class="uk-icon-user"></i> <?php echo ucwords(Yii::app()->functions->getClientName());?> <i class="uk-icon-caret-down"></i>

			    </button>	    

			    <div class="uk-dropdown" style="">

			        <ul class="uk-nav uk-nav-dropdown">            	            

			            <li>

			            <a href="<?php echo Yii::app()->request->baseUrl; ?>/store/Profile">

			            <i class="uk-icon-user"></i> <?php echo Yii::t("default","Profile")?></a>

			            </li>

			            <li>

			            <a href="<?php echo Yii::app()->request->baseUrl; ?>/store/orderHistory">

			            <i class="uk-icon-gear"></i> <?php echo Yii::t("default","Order History")?></a>

			            </li>

			            

			            <?php if (Yii::app()->functions->getOptionAdmin('disabled_cc_management')==""):?>

			            <li>

			            <a href="<?php echo Yii::app()->request->baseUrl; ?>/store/Cards">

			            <i class="uk-icon-gear"></i> <?php echo Yii::t("default","Credit Cards")?></a>

			            </li>

			            <?php endif;?>

			            

			            <li><a href="<?php echo Yii::app()->request->baseUrl; ?>/store/logout">

			            <i class="uk-icon-sign-out"></i> <?php echo Yii::t("default","Logout")?></a></li>

			        </ul>

			     </div>

			 </div> <!--uk-button-dropdown-->

	    	<?php	    	

	    	$forms = ob_get_contents();

            ob_end_clean();

            $this->code=1;

            $this->details=$forms;

	    	else :

	    	$this->code=2;

	    	endif;	    	

	    }    

	    

	    public function removeRating()

	    {	    	

	    	if ( Yii::app()->functions->isClientLogin()){

	    		$client_id=Yii::app()->functions->getClientId();

	    		Yii::app()->functions->removeRatings($this->data['merchant_id'],$client_id);

	    		$this->code=1;

	    		$this->msg="OK";

	    	} else $this->msg=Yii::t("default","Cannot remove ratings user is not login.");	    

	    }

	    

	    public function loadRatings()

	    {	    	

	    	$initial_rating='';

			$client_id=Yii::app()->functions->getClientId();  

			if ($ratings=Yii::app()->functions->getRatings($this->data['merchant_id'])){

				$this->code=1;

				$this->details=$ratings;

			} else $this->msg=Yii::t("default","Ratings not available.");    			

	    }	

	    

        public function addReviews()

	    {	    		    		    	

	    	$DbExt=new DbExt;

	    	if ( Yii::app()->functions->isClientLogin()){

		    	$client_id=Yii::app()->functions->getClientId();  		    	

		    	$params=array(

		    	  'merchant_id'=>$this->data['merchant-id'],

		    	  'client_id'=>$client_id,

		    	  'review'=>$this->data['review_content'],

		    	  'date_created'=>date('c'),

		    	  'rating'=>$this->data['initial_review_rating']

		    	);		    	

		    	

		    	/** check if user has bought from the merchant*/		    	

		    	if ( Yii::app()->functions->getOptionAdmin('website_reviews_actual_purchase')=="yes"){

		    		$functionk=new FunctionsK();

		    	    if (!$functionk->checkIfUserCanRateMerchant($client_id,$this->data['merchant-id'])){

		    	    	$this->msg=t("Reviews are only accepted from actual purchases!");

		    	    	return ;

		    	    }

		    	    		    	    	    	   

		    	    if (!$functionk->canReviewBasedOnOrder($client_id,$this->data['merchant-id'])){

		    		   $this->msg=t("Sorry but you can make one review per order");

		    	       return ;

		    	    }	  		   

		    	    

		    	    if ( $ref_orderid=$functionk->reviewByLastOrderRef($client_id,$this->data['merchant-id'])){

		    	    	$params['order_id']=$ref_orderid;

		    	    }

		    	}

		    	

		    	/*dump($params);		    	

		    	die();*/

		    	

		    	if ( $DbExt->insertData("{{review}}",$params)){

		    		$this->code=1;

		    		$this->msg=Yii::t("default","Your review has been published.");

		    		

		    		if (isset($this->data['initial_review_rating'])){

			    		Yii::app()->functions->updateRatings($this->data['merchant-id'],

			    		$this->data['initial_review_rating'],$client_id

			    		);

		    		}

		    		

		    		/*POINTS PROGRAM*/		    		

		    		if (FunctionsV3::hasModuleAddon("pointsprogram")){

		    		   PointsProgram::reviewsReward($client_id);

		    		}

		    				    		

		    	} else $this->msg=Yii::t("default","ERROR: cannot insert records.");

	    	} else $this->msg=Yii::t("default","Sorry but you need to login to write a review.");

	    }

	    

	    public function loadReviews()

	    {	    		    		    		    		    	

	    	$client_id=Yii::app()->functions->getClientId();

	    	if ( $res=Yii::app()->functions->getReviewsList($this->data['merchant_id']) ){	    		

	    		ob_start();

	    		foreach ($res as $val) {	    		

	    		$pretyy_date=PrettyDateTime::parse(new DateTime($val['date_created']));

	    		$pretyy_date=Yii::app()->functions->translateDate($pretyy_date);

	    		?>

	    		<div  id="#review-<?php echo $val['id']?>" class="row row-review">

	    		   <div class="col-md-2 col-xs-2 border center into-row">

	    		   

	    		     <!--<i class="ion-android-contact"></i>-->

	    		     <img src="<?php echo FunctionsV3::getAvatar($val['client_id']);?>" class="img-circle">

	    		     

	    		     <p class="small"><?php echo $val['client_name']?></p>

	    		   </div> <!--col-->

	    		   <div class="col-md-7 col-xs-7 border into-row">

	    		   

	    		     <div class="col-md-12 ">	    		         

	    		         <div class="col-md-5 ">

		    		       <div class="rating-stars" data-score="<?php echo $val['rating']?>"></div>

		    		     </div>

		    		     <div class="col-md-4 small text-left">

	    		         <?php echo $pretyy_date;?>

	    		         </div>

	    		     </div> 

	    		     <div class="col-md-12 top10">

	    		        <p class="read-more"><?php echo nl2br($val['review'])?></p>

	    		     </div>

	    		     

	    		   </div> <!--col-->

	    		   

	    		   <div class="col-md-3 center col-xs-3 border into-row">

	    		   <?php if ( $val['client_id']==$client_id ):?>

	    		    <a href="javascript:;" data-id="<?php echo $val['id']?>" class="edit-review orange-button inline">

	    		    <?php echo t("Edit")?>

	    		    </a>

	    		    <a href="javascript:;" data-id="<?php echo $val['id']?>" class="delete-review green-button inline">

	    		    <?php echo t("Delete")?>

	    		    </a>

	    		   <?php endif;?>

	    		   </div> <!--col-->

	    		   

	    		</div><!-- row-->

	    		<?php

	    		}

	    		$html = ob_get_contents();

                ob_end_clean();   

                

                $this->code=1;

	            $this->msg="OK";	    		

	             $this->details=$html;

	    	} else $this->msg=Yii::t("default","No reviews yet.");	

	    }

	    

	    public function savePaypalSettings()

	    {

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    	

	    	Yii::app()->functions->updateOption("enabled_paypal",

	    	isset($this->data['enabled_paypal'])?$this->data['enabled_paypal']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("paypal_mode",

	    	isset($this->data['paypal_mode'])?$this->data['paypal_mode']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sanbox_paypal_user",

	    	isset($this->data['sanbox_paypal_user'])?$this->data['sanbox_paypal_user']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sanbox_paypal_pass",

	    	isset($this->data['sanbox_paypal_pass'])?$this->data['sanbox_paypal_pass']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sanbox_paypal_signature",

	    	isset($this->data['sanbox_paypal_signature'])?$this->data['sanbox_paypal_signature']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("live_paypal_user",

	    	isset($this->data['live_paypal_user'])?$this->data['live_paypal_user']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("live_paypal_pass",

	    	isset($this->data['live_paypal_pass'])?$this->data['live_paypal_pass']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("live_paypal_signature",

	    	isset($this->data['live_paypal_signature'])?$this->data['live_paypal_signature']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_paypal_fee",

	    	isset($this->data['merchant_paypal_fee'])?$this->data['merchant_paypal_fee']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("mt_paypal_mobile_enabled",

	    	isset($this->data['mt_paypal_mobile_enabled'])?$this->data['mt_paypal_mobile_enabled']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("mt_paypal_mobile_mode",

	    	isset($this->data['mt_paypal_mobile_mode'])?$this->data['mt_paypal_mobile_mode']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("mt_paypal_mobile_clientid",

	    	isset($this->data['mt_paypal_mobile_clientid'])?$this->data['mt_paypal_mobile_clientid']:'',$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	    }		    

	    

	    public function editReview()

	    {

	    	require_once 'edit-review.php';

	    	die();

	    }	

	    

	    public function updateReview()

	    {

	    	$DbExt=new DbExt;	    	

	    	if (!isset($this->data['review_content'])){	 

	    		$this->msg=Yii::t("default","Review content is required.");

	    		return ;

	    	}

	    	if (empty($this->data['review_content'])){	 

	    		$this->msg=Yii::t("default","Review content is required.");

	    		return ;

	    	}

	    	if (!isset($this->data['id'])){	 

	    		$this->msg=Yii::t("default","Review ID is missig");

	    		return ;

	    	}

	    	if ( $this->data['web_session_id']!=session_id()){	 

	    		$this->msg=Yii::t("default","Sorry but you cannot post directly to this action");

	    		return ;

	    	}	    		    	

	    	$params=array('review'=>$this->data['review_content'],

	    	 'date_created'=>date('c'),

	    	 'ip_address'=>$_SERVER['REMOTE_ADDR']

	    	);	    	

	    	if ( $DbExt->updateData("{{review}}",$params,'id',$this->data['id'])){

	    		$this->code=1;

	    		$this->msg=Yii::t("default","Your review has been updated.");

	    	} else $this->msg=Yii::t("default","ERROR: cannot update reviews.");	   

	    }	

	    

	    public function deleteReview()

	    {	    	

	    	$DbExt=new DbExt;

	    	$stmt="DELETE FROM

	    	{{review}}

	    	WHERE

	    	id='".$this->data['id']."'

	    	";

	    	if ( $DbExt->qry($stmt)){

	    		$this->code=1;

	    		$this->msg=Yii::t("default","Succssful");

	    	} else $this->msg=Yii::t("default","ERROR: Failed deleting reviews.");

	    }

	    

	    public function paypalCheckoutPayment()

	    {	    	

	    	if (!empty($this->data['payerid']) && !empty($this->data['payerid'])){

	    	   //$paypal_con=Yii::app()->functions->getPaypalConnection();      	   

	    	   $paypal_con=Yii::app()->functions->getPaypalConnection($_SESSION['kr_merchant_id']);     

	    	   	    	   

	    	   /*get admin paypal connection if merchant is commission*/

			   if ( Yii::app()->functions->isMerchantCommission($_SESSION['kr_merchant_id'])){

			   	   unset($paypal_con);   	   

			   	   $paypal_con=Yii::app()->functions->getPaypalConnectionAdmin();

			   }  

				    	   			   

	           $paypal=new Paypal($paypal_con);	

	           

	           $paypal->params['PAYERID']=$this->data['payerid'];

	           $paypal->params['AMT']=$this->data['amount'];   

	           $paypal->params['TOKEN']=$this->data['token'];     

	           $paypal->params['CURRENCYCODE']=Yii::app()->functions->adminCurrencyCode();

	                 

	           if ($res=$paypal->expressCheckout()){            	              	   

	           	   $this->code=1;

	           	   $this->msg=Yii::t("default","Your purchase has been place.");

	           	   $this->details=array(

	           	     'order_id'=>$this->data['order_id'],

	           	     'token'=>$this->data['token']           	     

	           	   );           	   

	           	   

	           	   $params=array('status'=>addslashes($res['ACK']));

			       $command = Yii::app()->db->createCommand();

			       $command->update('{{paypal_checkout}}' , $params , 

					                     'token=:token' , array(':token'=> addslashes($this->data['token']) ));

					                     

				   $params=array('status'=>'paid');		       

			       $command->update('{{order}}' , $params , 

					                     'order_id=:order_id' , array(':order_id'=> addslashes($this->data['order_id']) ));

	              

	           	   $db_ext=new DbExt;

	           	   $insert=array(

	           	     'order_id'=>$this->data['order_id'],

	           	     'TOKEN'=>$this->data['token'],

			         'TRANSACTIONID'=>$res['TRANSACTIONID'],

			    	 'TRANSACTIONTYPE'=>$res['TRANSACTIONTYPE'],

			    	 'PAYMENTTYPE'=>$res['PAYMENTTYPE'],

			    	 'ORDERTIME'=>$res['ORDERTIME'],

			    	 'AMT'=>$res['AMT'],

			    	 'FEEAMT'=>!isset($res['FEEAMT'])?0:$res['FEEAMT'],

			    	 'TAXAMT'=>$res['TAXAMT'],

			    	 'CURRENCYCODE'=>$res['CURRENCYCODE'],

			    	 'PAYMENTSTATUS'=>$res['PAYMENTSTATUS'],

			    	 'CORRELATIONID'=>$res['CORRELATIONID'],

			    	 'TIMESTAMP'=>$res['TIMESTAMP'],

			    	 'json_details'=>json_encode($res),

			    	 'date_created'=>date('c'),

			    	 'ip_address'=>$_SERVER['REMOTE_ADDR']

	           	   );

	           	   $db_ext->insertData("{{paypal_payment}}",$insert);   

	           	   

	           	   

	           	   /*POINTS PROGRAM*/ 

	           	   if (FunctionsV3::hasModuleAddon("pointsprogram")){

	           	      PointsProgram::updatePoints($this->data['order_id']);

	           	   }

	           	   

	           	   /*Driver app*/

				   if (FunctionsV3::hasModuleAddon("driver")){

					   Yii::app()->setImport(array(			

						  'application.modules.driver.components.*',

					   ));

					   Driver::addToTask($this->data['order_id']);

				   }

	           	   

	           } else $this->msg=$paypal->getError();

	    	} else $this->msg=Yii::t("default","ERROR: One or more field is missing.");

	    }	

	    

	    public function viewReceipt()

	    {

	    	if (isset($this->data['backend'])){

	    		$params=array(

	    		  'viewed'=>2

	    		);

	    		$this->updateData("{{order}}",$params,'order_id',$this->data['id']);

	    	}	    	    	

	    	require_once 'view-receipt.php';

	    	die();

	    }	

	    

	    public function addToOrder()

	    {	    	

	    	if (isset($this->data['order_id'])){

	    		if ( $res=Yii::app()->functions->getOrder($this->data['order_id'])){	    			

	    			$json_details=!empty($res['json_details'])?json_decode($res['json_details'],true):false;

	    			if ( $json_details!=false){	    				

	    				$json_details=(array)$json_details;

	    			}	    			    		

	    			$_SESSION['kr_merchant_slug']=$res['restaurant_slug'];

	    			$_SESSION['kr_merchant_id']=$res['merchant_id'];

	    			$_SESSION['kr_item']=$json_details;

	    			$this->code=1;

	    			$this->msg="ok";

	    			$this->details=array(

	    			  'restaurant_slug'=>$res['restaurant_slug'],

	    			  'merchant_id'=>$res['merchant_id'],

	    			);

	    		}	    	

	    	} else $this->msg=Yii::t("default","Missing Order id");

	    }	

	    

	    public function removeLogo()

	    {	    	

	    	if (Yii::app()->functions->isMerchantLogin()){

	    		$DbExt=new DbExt;

		    	$merchant_id=Yii::app()->functions->getMerchantID();	    

		    	$stmt="Delete  FROM

		    	{{option}}

		    	WHERE

		    	option_name='merchant_photo'

		    	AND

		    	merchant_id='$merchant_id'

		    	";

		    	$DbExt->qry($stmt);

		    	$this->code=1;

		    	$this->msg=Yii::t("default","Successful");

	    	} else $this->msg=Yii::t("default","ERROR: Your session has expired.");

	    }	

	    

	    public function salesReport()

	    {	    		  

	    	$and='';  

	    	if (isset($this->data['start_date']) && isset($this->data['end_date']))	{

	    		if (!empty($this->data['start_date']) && !empty($this->data['end_date'])){

	    		$and=" AND date_created BETWEEN  '".$this->data['start_date']." 00:00:00' AND 

	    		        '".$this->data['end_date']." 23:59:00'

	    		 ";

	    		}

	    	}

	    	

	    	$order_status_id='';

	    	$or='';

	    	if (isset($this->data['stats_id'])){

		    	if (is_array($this->data['stats_id']) && count($this->data['stats_id'])>=1){

		    		foreach ($this->data['stats_id'] as $stats_id) {		    			

		    			$order_status_id.="'$stats_id',";

		    		}

		    		if ( !empty($order_status_id)){

		    			$order_status_id=substr($order_status_id,0,-1);

		    		}		    	

		    	}	    

	    	}

	    	

	    	if ( !empty($order_status_id)){	    		

	    		$and.= " AND status IN ($order_status_id)";

	    	}	    	    	

	    	 

	    	//dump($and);	    	

	    	

	    	$DbExt=new DbExt;

	    	$merchant_id=Yii::app()->functions->getMerchantID();	    

	    	$stmt="SELECT a.*,

	    	(

	    	select concat(first_name,' ',last_name)

	    	from

	    	{{client}}

	    	where

	    	client_id=a.client_id

	    	) as client_name,

	    	

	    	(

	    	select group_concat(item_name)

	    	from

	    	{{order_details}}

	    	where

	    	order_id=a.order_id

	    	) as item

	    	

	    	FROM

	    	{{order}} a

	    	WHERE

	    	merchant_id='$merchant_id'

	    	AND status NOT IN ('".initialStatus()."')

	    	$and

	    	ORDER BY order_id DESC

	    	LIMIT 0,2000

	    	";

	    	/*dump($this->data);

	    	dump($stmt);*/

	    	

	    	$_SESSION['kr_export_stmt']=$stmt;	    	

	    		    		    	

	    	if ( $res=$DbExt->rst($stmt)){

	    		foreach ($res as $val) {	    			    			

	    			$action="<a data-id=\"".$val['order_id']."\" class=\"edit-order\" href=\"javascript:\">".Yii::t("default","Edit")."</a>";

	    			$action.="<a data-id=\"".$val['order_id']."\" class=\"view-receipt\" href=\"javascript:\">".Yii::t("default","View")."</a>";

	    			

	    			$action.="<a data-id=\"".$val['order_id']."\" class=\"view-order-history\" href=\"javascript:\">".Yii::t("default","History")."</a>";

	    			

	    			/*$date=prettyDate($val['date_created'],true);

	    			$date=Yii::app()->functions->translateDate($date);*/

	    			$date=FormatDateTime($val['date_created']);

	    			

	    			$feed_data['aaData'][]=array(

	    			  $val['order_id'],

	    			  ucwords($val['client_name']),

	    			  $val['item'],

	    			  ucwords(Yii::t("default",$val['trans_type'])),

	    			  //strtoupper(Yii::t("default",$val['payment_type'])),

	    			  FunctionsV3::prettyPaymentType('payment_order',$val['payment_type'],$val['order_id']),

	    			  prettyFormat($val['sub_total'],$merchant_id),

	    			  prettyFormat($val['taxable_total'],$merchant_id),

	    			  prettyFormat($val['total_w_tax'],$merchant_id),

	    			  t($val['status']),

	    			  $date,

	    			  $action

	    		    );

	    		}

	    		$this->otableOutput($feed_data);

	    	}	   

	    	$this->otableNodata();

	    }

	    

	    public function editOrder()

	    {	    		    	

	    	

	    	if (isset($this->data['id'])){

	    	   $params=array('viewed'=>2);

	           $this->updateData("{{order}}",$params,'order_id',$this->data['id']);

	    	}

	    	

	    	$status_list=Yii::app()->functions->orderStatusList();

	    	?>

	    	<div class="view-receipt-pop">

	    	 <h3><?php echo Yii::t("default",'Change Order Status')?></h3>

	    	 

	    	 <?php if ( $res=Yii::app()->functions->getOrderInfo($this->data['id']) ):?>

	    	    <form id="frm-pop" class="frm-pop uk-form uk-form-horizontal" method="POST" onsubmit="return false;">

	    	    <?php echo CHtml::hiddenField('action','updateOrder')?>

	    	    <?php echo CHtml::hiddenField('order_id',$this->data['id'])?>

	    	    

		    	 <div class="uk-form-row">

		    	  <label class="uk-form-label"><?php echo Yii::t("default",'Status')?></label>

		    	  <?php echo CHtml::dropDownList('status',$res['status'],(array)$status_list,array(

		    	  'class'=>"uk-form-width-large"

		    	  ))?>

		    	 </div>

		    	 

		    	 

		    	 <div class="uk-form-row">

		    	  <label class="uk-form-label"><?php echo Yii::t("default",'Remarks')?></label>

		    	  <?php 

		    	  echo CHtml::textArea('remarks','',array(

		    	    'class'=>"uk-form-width-large"

		    	  ));

		    	  ?>		    	  

		    	 </div>

		    	 

		    	 <!--Driver-->

		    	 <?php 

		    	 /*Yii::app()->setImport(array(			

				    'application.modules.driver.components.*',

			     ));

			     Driver::AdminStatusTpl();*/

		    	 ?>		    	 

		    	 <!--Driver-->

		    	 

		    	 

		    	 <div class="action-wrap">

		    	   <?php echo CHtml::submitButton('Submit',		    	  

		    	   array('value'=>Yii::t("default",'Submit'),'class'=>"uk-button uk-form-width-medium uk-button-success"))?>

		    	 </div>

	    	   </form> 

	    	 <?php else:?>

	    	 <p class="uk-text-danger"><?php echo Yii::t("default","Error: Order not found")?></p>

	    	 <?php endif;?>

	    	</div> <!--view-receipt-pop-->	    					    

			<script type="text/javascript">

			$.validate({ 	

			    form : '#frm-pop',    

			    onError : function() {      

			    },

			    onSuccess : function() {     

			      form_submit('frm-pop');

			      return false;

			    }  

			});		

			</script>

	    	<?php

	    	die();

	    }

	    

	    public function updateOrder()

	    {	    	

	    		    		    	

	    	$DbExt=new DbExt;

	    	$merchant_id=Yii::app()->functions->getMerchantID();	    	    	

	    		    		

	    	$mt_timezone=Yii::app()->functions->getOption("merchant_timezone",$merchant_id);

    	    if (!empty($mt_timezone)){    	 	

    		   Yii::app()->timeZone=$mt_timezone;

    	    }

    	    

    	    /*dump($this->data);

    	    die();*/

    	    /** check if merchant has initiate widthrawals*/

    	    if ( Yii::app()->functions->isMerchantCommission($merchant_id)){    	    	

    	    	if ( FunctionsK::validateChangeOrder($this->data['order_id'])){

    	    		$this->msg=t("Sorry but you cannot change the order status of this order it has reference already on the withdrawals that you made");

    	    		return;

    	    	}    	    

    	    }	        	    

    	        	        	

    	    $date_now=date('Y-m-d');

    	    	    	

	    	if (isset($this->data['order_id'])){

	    		$order_id=$this->data['order_id'];	    		

	    		if ( $resp=Yii::app()->functions->verifyOrderIdByOwner($order_id,$merchant_id) ){

	    			$params=array('status'=>$this->data['status'],'date_modified'=>date('c'),'viewed'=>2);		

	    			

	    			/*check if merchant can change the status*/

	    			$can_edit=Yii::app()->functions->getOptionAdmin('merchant_days_can_edit_status');

	    			

	    			if (is_numeric($can_edit) && !empty($can_edit)){

	    				

	    				//$date_now=date('Y-m-d g:i:s a');

	    				//$date_now=date('Y-m-d');

	    				

	    				$base_option=getOptionA('merchant_days_can_edit_status_basedon');	    				

	    				

	    				if ( $base_option==2){	    					

	    					$date_created=date("Y-m-d",

	    					strtotime($resp['delivery_date']." ".$resp['delivery_time']));		

	    				} else $date_created=date("Y-m-d",strtotime($resp['date_created']));	    						    			

	    				/*dump($date_created);			    			

	    				dump($date_now);*/

	    					    				

	    				/*$date1=date_create($date_created);

	    				$date2=date_create($date_now);

	    				$interval = date_diff($date1,$date2);

	    				dump($interval);	    				

	    				if (is_object($interval)){

	    					echo $interval->d;

	    				}*/

	    					    				

		    			$date_interval=Yii::app()->functions->dateDifference($date_created,$date_now);

		    			if (is_array($date_interval) && count($date_interval)>=1){		    				

		    				if ( $date_interval['days']>$can_edit){

		    					$this->msg=t("Sorry but you cannot change the order status anymore. Order is lock by the website admin");

		    					$this->details=json_encode($date_interval);

		    					return ;

		    				}		    			

		    			}	    		

		    			

		    			

	    			}

	    			

	    			$mechant_sms_enabled= Yii::app()->functions->getOptionAdmin("mechant_sms_enabled");

	    			

	    			/*check if order has past for 2 days*/

	    			$this->details['order_id']=$order_id;

	    			$this->details['show_sms']=2;

	    			$date_created=date("Y-m-d g:i:s a",strtotime($resp['date_created']));

	    			$date_interval=Yii::app()->functions->dateDifference($date_created,$date_now);	    			

	    			if (is_array($date_interval) && count($date_interval)>=1){	    				

	    				if ( $date_interval['days']>2){

	    					$this->details['show_sms']=1;

	    				}

	    			}

	    			

	    			if ( $mechant_sms_enabled=="yes"){

	    				$this->details['show_sms']=1;

	    			}	    		

	    			

	    			/**check if admin has disabled the sending of sms*/

	    			if (getOptionA('merchant_changeorder_sms')==2){

	    				$this->details['show_sms']=1;

	    			}

	    				    			

	    			if ($DbExt->updateData('{{order}}',$params,'order_id',$order_id)){

	    				$this->code=1;

	    				$this->msg=Yii::t("default","Status saved.");

	    				

	    				/*Now we insert the order history*/	    		

	    				$params_history=array(

	    				  'order_id'=>$order_id,

	    				  'status'=>$this->data['status'],

	    				  'remarks'=>isset($this->data['remarks'])?$this->data['remarks']:'',

	    				  'date_created'=>date('c'),

	    				  'ip_address'=>$_SERVER['REMOTE_ADDR']

	    				);	    				

	    				$DbExt->insertData("{{order_history}}",$params_history);



	    				    				

	    				if (FunctionsV3::hasModuleAddon("mobileapp")){

		    				/** Mobile save logs for push notification */

					    	Yii::app()->setImport(array(			

							  'application.modules.mobileapp.components.*',

						    ));

					    	AddonMobileApp::savedOrderPushNotification($this->data);

	    				}

				    	

	    				if (FunctionsV3::hasModuleAddon("driver")){

					    	/*Driver app*/

					    	Yii::app()->setImport(array(			

							  'application.modules.driver.components.*',

						    ));

						    Driver::addToTask($order_id);

	    				}

				    	

				    	

	    			} else $this->msg=Yii::t("default","ERROR: cannot update order.");

	    		} else $this->msg=Yii::t("default","This Order does not belong to you.");

	    	} else $this->msg=Yii::t("default","Missing parameters");	    

	    }

	    

	    public function salesSummaryReport()

	    {	    	

	    	

	    	unset($_SESSION['rpt_date_range']);

	        $and='';  

	    	if (isset($this->data['start_date']) && isset($this->data['end_date']))	{

	    		if (!empty($this->data['start_date']) && !empty($this->data['end_date'])){

	    		   $and=" AND date_created BETWEEN  '".$this->data['start_date']." 00:00:00' AND 

	    		        '".$this->data['end_date']." 23:59:00'

	    		   ";

	    		   $_SESSION['rpt_date_range']=array(

    		         'start_date'=>$this->data['start_date'],

    		         'end_date'=>$this->data['end_date']

    		       );

	    		}

	    	}

	    	

	    	$order_status_id='';

	    	$or='';

	    	if (isset($this->data['stats_id'])){

		    	if (is_array($this->data['stats_id']) && count($this->data['stats_id'])>=1){

		    		foreach ($this->data['stats_id'] as $stats_id) {		    			

		    			$order_status_id.="'$stats_id',";

		    		}

		    		if ( !empty($order_status_id)){

		    			$order_status_id=substr($order_status_id,0,-1);

		    		}		    	

		    	}	    

	    	}

	    	

	    	if ( !empty($order_status_id)){	    		

	    		$and.= " AND status IN ($order_status_id)";

	    	}	    	    	

	    		    	 

	    	$DbExt=new DbExt;

	    	$merchant_id=Yii::app()->functions->getMerchantID();	    

	    	

	    	//dump($merchant_id);

	    	$stmt="SELECT SUM(a.qty) as total_qty,

	    	a.item_id,a.item_name,a.discounted_price,a.size

	    	FROM

	    	{{view_order_details}} a	 

	    	WHERE

	    	merchant_id='$merchant_id'

	    	AND status NOT IN ('".initialStatus()."')

	        $and

	    	GROUP BY item_id,size

	    	ORDER BY item_name ASC

	    	LIMIT 0,2000 	

	    	";	   

	    	$_SESSION['kr_export_stmt']=$stmt;

	    	

	    	//dump($stmt); 	

	    	if ($res=$DbExt->rst($stmt)){

	    		foreach ($res as $val) {

	    			//dump($val);

	    			$feed_data['aaData'][]=array(

	    			   $val['item_id'],

	    			   $val['item_name'],

	    			   $val['size'],

	    			   prettyFormat($val['discounted_price'],$merchant_id),

	    			   $val['total_qty'],

	    			   prettyFormat($val['discounted_price']*$val['total_qty'],$merchant_id)

	    		    );

	    		}

	    		$this->otableOutput($feed_data);

	    	}	    

	    	$this->otableNodata();	

	    }	

	    

	     public function chartTotalSales()

	    {

	    	$merchant_id=Yii::app()->functions->getMerchantID();	    

	    	

	    	$data='';

	    	$db_ext=new DbExt();    	

	    	$date_now=date('Y-m-d 23:00:59');

	    	$start_date=date('Y-m-d 00:00:00',strtotime($date_now . "-30 days"));

	    	$stmt="SELECT DATE_FORMAT(date_created, '%M-%D') as date_created_format,SUM(total_w_tax) as total

	    	FROM

	    	{{order}}

	    	WHERE

	    	date_created BETWEEN '$start_date' AND '$date_now'

	    	AND

	    	merchant_id ='$merchant_id'

	    	AND status NOT IN ('".initialStatus()."')

	    	GROUP BY DATE_FORMAT(date_created, '%M-%D')

	    	ORDER BY date_created ASC

	    	";

	    	//dump($stmt);

	    	if ($res=$db_ext->rst($stmt)){

	    		foreach ($res as $val) {

	    			//$data[$val['date_created_format']]=prettyFormat($val['total'],$merchant_id);

	    			$t=explode("-",$val['date_created_format']);

	    			if (is_array($t) && count($t)>=1){

	    				$tt=Yii::t("default",$t[0])."-".$t[1];

	    			    $data[$tt]=unPrettyPrice($val['total']);

	    			} else $data[$val['date_created_format']]=unPrettyPrice($val['total']);

	    		}

	    	}	    	    	

	    	echo Yii::app()->functions->formatAsChart($data);

	    	die();

	    }		   



	    public function chartByItem()

	    {	    	

	    	$merchant_id=Yii::app()->functions->getMerchantID();	    

	    		    

	    	$data='';

	    	$db_ext=new DbExt();		    	

	    	$date_now=date('Y-m-d 23:00:59');

	    	$start_date=date('Y-m-d 00:00:00',strtotime($date_now . "-30 days"));

	    	$stmt="SELECT a.item_name,a.discounted_price,	    	       

	    	       SUM(qty) as total,

	    	       b.date_created

	    	       FROM

	    	       {{view_order_details}} a

	    	       left join {{order}} b

	    	       ON

	    	       a.order_id=b.order_id

	    	       WHERE

	    	       b.date_created BETWEEN '$start_date' AND '$date_now'

	    	       AND a.merchant_id ='$merchant_id'

	    	       AND a.status NOT IN ('".initialStatus()."')

	    	       GROUP BY item_id

	    	       ORDER BY item_name ASC

	    	";	    	

	    	//dump($stmt);

	    	if ($res=$db_ext->rst($stmt)){	    			    		

	    		foreach ($res as $val) {

	    			$val['item_name']=trim($val['item_name']);

	    			$data[$val['item_name']]=$val['discounted_price']*$val['total'];

	    		}

	    	}	    	    	

	    	echo Yii::app()->functions->formatAsChart($data);

	    	die();

	    }
         
		
	    

        public function recentOrder()
	    {	    		    

	    	$DbExt=new DbExt;

	    	$merchant_id=Yii::app()->functions->getMerchantID();	    

	    	$stmt="SELECT a.*,

	    	(

	    	select concat(first_name,' ',last_name)

	    	from

	    	{{client}}

	    	where

	    	client_id=a.client_id

	    	) as client_name,

	    	

	    	(

	    	select concat(contact_phone)

	    	from

	    	{{client}}

	    	where

	    	client_id=a.client_id

	    	) as contact_phone,

	    	

	    	(

	    	select group_concat(item_name)

	    	from

	    	{{order_details}}

	    	where

	    	order_id=a.order_id

	    	) as item

	    	

	    	FROM

	    	{{order}} a

	    	WHERE

	    	merchant_id='$merchant_id'	    	

	    	AND date_created like '".date('Y-m-d')."%'

	    	AND status NOT in ('".initialStatus()."')

	    	ORDER BY date_created DESC	    	

	    	";

	    	//dump($this->data);

	    	//dump($stmt);

	    	if ( $res=$DbExt->rst($stmt)){

	    		//dump($res);

	    		foreach ($res as $val) {	    			

	    			$new='';

	    			$action="<a data-id=\"".$val['order_id']."\" class=\"edit-order\" href=\"javascript:\">".Yii::t("default","Edit")."</a>";

	    			$action.="<a data-id=\"".$val['order_id']."\" class=\"view-receipt\" href=\"javascript:\">".Yii::t("default","View")."</a>";

	    			

	    			$action.="<a data-id=\"".$val['order_id']."\" class=\"view-order-history\" href=\"javascript:\">".Yii::t("default","History")."</a>";

	    			

	    			if ($val['viewed']==1){

	    				$new=" <div class=\"uk-badge\">".Yii::t("default","NEW")."</div>";

	    			}	    		

	    			

	    			/*$date=prettyDate($val['date_created'],true);

	    			$date=Yii::app()->functions->translateDate($date);*/

	    			$date=FormatDateTime($val['date_created']);

	    			

	    			$item=FunctionsV3::translateFoodItemByOrderId(

	    			  $val['order_id'],

	    			  'kr_merchant_lang_id'

	    			);

	    			

	    			$feed_data['aaData'][]=array(

	    			  $val['order_id'],

	    			  ucwords($val['client_name']).$new,

	    			  $val['contact_phone'],

	    			  $item,

	    			  ucwords(Yii::t("default",$val['trans_type'])),

	    			  //strtoupper(Yii::t("default",$val['payment_type'])),

	    			  FunctionsV3::prettyPaymentType('payment_order',$val['payment_type'],$val['order_id']),

	    			  prettyFormat($val['sub_total'],$merchant_id),

	    			  prettyFormat($val['taxable_total'],$merchant_id),

	    			  prettyFormat($val['total_w_tax'],$merchant_id),

	    			  t($val['status']),

	    			  $date,

	    			  $action

	    		    );

	    		}

	    		$this->otableOutput($feed_data);

	    	}	   

	    	$this->otableNodata();

	    }		    

	    

	    public function orderStatusSettings()

	    {	    	

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    		    

	    	Yii::app()->functions->updateOption("default_order_status",

	    	isset($this->data['default_order_status'])?$this->data['default_order_status']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	    }	

	    

	    public function export()

	    {	    	    	

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    	$db_ext=new DbExt();

	    	if (!empty($_SESSION['kr_export_stmt'])){

	    		$stmt= $_SESSION['kr_export_stmt'];

	    		$pos=strpos($stmt,"LIMIT");

	    		$stmt=substr($stmt,0,$pos);	 	    		

	    		switch ($this->data['rpt']) {

	    			case 'sales-report':

	    				if ($res=$db_ext->rst($stmt)){		    					

	    					$csvdata=array();

    	    	            $datas=array();  

    	    	            foreach ($res as $val) {	    			    			

				    			$item='';				    			

				    			$date=date('m-d-Y G:i:s',strtotime($val['date_created']));    	    		

			    	    		$latestdata[]=array(    	    		  

			    	    		  $val['order_id'],

			    	    		  $val['client_name'],

			    	    		  $val['item'],

			    	    		  $val['trans_type'],

			    	    		  $val['payment_type'],

			    	    		  prettyFormat($val['sub_total'],$merchant_id),

			    	    		  prettyFormat($val['tax'],$merchant_id),

			    	    		  prettyFormat($val['total_w_tax'],$merchant_id),

			    	    		  $val['status'],

			    	    		  $date

			    	    		);    	    		

				    		}				    		 	

				    		unset($data);

    	                    $data=$latestdata;    	                    

    	                    

	    				}	

	    				

	    				if (is_array($data) && count($data)>=1){

			    	    	$csvdata=array();

			    	    	$datas=array();

			    	        foreach ($data as $val) {    	        	

			    	            foreach ($val as $key => $vals) {

			    	            	$datas[]=$vals;

			    	            }

			    	            $csvdata[]=$datas;

			    	            unset($datas);

			    	        }	

			    	    }    	    

	    				

			    	    $header=array(

			    	    Yii::t("default","Ref#"),

			    	    Yii::t("default","Client Name"),

			    	    Yii::t("default","Item"),

			    	    Yii::t("default","Trans Type"),

			    	    Yii::t("default","Payment Type"),

			    	    Yii::t("default","Total"),

			    	    Yii::t("default","Tax"),

			    	    Yii::t("default","Total W/Tax"),

			    	    Yii::t("default","Status"),

			    	    Yii::t("default","Date"));

			    	    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($csvdata);	  

                        $excel->prepareExcel();	 

                        exit;

	    				break;

	    		

	    			case "sales-summary-report":	

	    			   $has_date_range=false;

                       if (isset($_SESSION['rpt_date_range'])){

	    			   	   if (is_array($_SESSION['rpt_date_range'])){

	    			   	   	   $has_date_range=true;

	    			   	   }

	    			   }

	    			   

	    			    if ($res=$db_ext->rst($stmt)){	    		

			    		    foreach ($res as $val) {	

			    			    $total_amt='';

			    			    if ( $has_date_range==true){

			    			    	$data[]=array(

				    			       $val['item_id'],

				    			       $val['item_name'],

				    			       $val['size'],

				    			       prettyFormat($val['discounted_price'],$merchant_id),

				    			       prettyFormat($val['total_qty'],$merchant_id),

				    			       prettyFormat($val['total_qty']*$val['discounted_price'],$merchant_id),

				    			       $_SESSION['rpt_date_range']['start_date'],

							    	   $_SESSION['rpt_date_range']['end_date'],

				    			    );

			    			    } else {			    			    

				    			    $data[]=array(

				    			       $val['item_id'],

				    			       $val['item_name'],

				    			       $val['size'],

				    			       prettyFormat($val['discounted_price'],$merchant_id),

				    			       prettyFormat($val['total_qty'],$merchant_id),

				    			       prettyFormat($val['total_qty']*$val['discounted_price'],$merchant_id)

				    			    );

			    			    }

				    		}			    		

			    	    }			  			    	    			    	  

			    	    $header=array(

			    	    Yii::t("default","Item"),

			    	    Yii::t("default","Item Name"),

			    	    Yii::t("default","Size"),

			    	    Yii::t("default","Item Price"),

			    	    Yii::t("default","Total Qty"),

			    	    Yii::t("default","Total Amount")

			    	    );		

			    	    if ( $has_date_range==true) {

    			   	   	   $header[]=t("Start Date");

    			   	   	   $header[]=t("End Date");

	    			    }			    	    

			    	    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                        exit; 

	    			    break;

	    			    

	    			case "rptSalesMerchant":   	    			   

	    			   if ($res=$db_ext->rst($stmt)){	    		

			    		    foreach ($res as $val) {				    			    

			    			    $data[]=array(

			    			       $val['merchant_id'],

			    			       $val['restaurant_name'],

	    			               $val['contact_name'],

	    			               $val['contact_phone']." / ".$val['contact_email'],

	    			               $val['street']." ".$val['city']." ".$val['state']." ".$val['country_code']." ".$val['post_code'],

	    			               ucwords($val['package_name']),

	    			               $val['status'],

			    			       Yii::app()->functions->prettyDate($val['date_created'],true)

			    			    );

				    		}			    		

			    	    }					    	    

			    	    $header=array(

			    	    Yii::t("default","MerchantID"),

			    	    Yii::t("default","MerchantName"),

			    	    Yii::t("default","ContactName"),

			    	    Yii::t("default","Contact"),

			    	    Yii::t("default","Address"),

			    	    Yii::t("default","Package"),

			    	    Yii::t("default","Status"),

			    	    Yii::t("default","Date")

			    	    );			    	  

			    	    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                        exit; 

	    			    break;

	    			break;

	    			

	    			case 'rptAdminSalesMerchant':

	    			if ($res=$db_ext->rst($stmt)){	    		

			    		    foreach ($res as $val) {

			    		    	$date=prettyDate($val['date_created'],true);

	    			            $date=Yii::app()->functions->translateDate($date);

			    			    $data[]=array(

			    			        $val['order_id'],

					    			  ucwords($val['client_name']),

					    			  $val['item'],

					    			  ucwords(Yii::t("default",$val['trans_type'])),

					    			  strtoupper(Yii::t("default",$val['payment_type'])),

					    			  standardPrettyFormat($val['sub_total'],$merchant_id),

					    			  standardPrettyFormat($val['tax'],$merchant_id),

					    			  standardPrettyFormat($val['total_w_tax'],$merchant_id),

					    			  ucwords($val['status']),

					    			  $date,

			    			    );

				    		}			    		

			    	    }							    	    

			    	    $header=array(

			    	    Yii::t("default","Ref#"),

			    	    Yii::t("default","Name"),

			    	    Yii::t("default","Item"),

			    	    Yii::t("default","TransType"),

			    	    Yii::t("default","Payment Type"),

			    	    Yii::t("default","Total"),			    	    

			    	    Yii::t("default","Tax"),

			    	    Yii::t("default","Total W/Tax"),

			    	    Yii::t("default","Status"),

			    	    Yii::t("default","Date")

			    	    );			    	  

			    	    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                        exit; 

                        break;



	    			case "rptCustomerList":	 			

	    				if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){	

	    					foreach ($res as $val) {

	    						$data[]=array(

	    						   $val['email_address'],

	    						   $val['first_name'],

	    						   $val['last_name']

	    						);

	    					}	    					

	    				}		    				

    					$header=array(

    					 t("Email"),

    					 t("firstname"),

    					 t("lastname"),

    					);

	    				$filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                        exit; 

	    				break;

	    				

                    case "rptSubriberList":		    			

	    			  if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

			    		    foreach ($res as $val) {

			    		    	$date=prettyDate($val['date_created'],true);

	    			            $date=Yii::app()->functions->translateDate($date);

			    			    $data[]=array(

			    			        $val['id'],			   	      

			   	                    $val['email_address'],

			   	                    $date,

			   	                    $val['ip_address']		

			    			    );

				    		}			    		

			    	    }								    	   	    	   

	    			   $header=array(

	    			    t("ID"),t("Email address"),t("Date Created"),t("I.P Address")

	    			   );

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                        exit;                        

	    			   break;

	    			   

                    case "rptmerchantcommission":

                    	if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

			    		    foreach ($res as $val) {

			    		    	$date=prettyDate($val['date_created'],true);

	    			            $date=Yii::app()->functions->translateDate($date);

			    			    $data[]=array(

						    	   $val['merchant_id'],

				    			   $val['merchant_name'],

				    			   normalPrettyPrice($val['total_order']),

				    			   normalPrettyPrice($val['total_commission'])

			    			    );

				    		}			    		

			    	    }						    	   

	    			   $header=array(

	    			    t("ID"),

	    			    t("Merchant Name"),

	    			    t("Total Price"),

	    			    t("Commission Price")	    			    

	    			   );

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                        exit;                      

                    	break;   

	    			   	    	

                    case "rptmerchantcommissiondetails": 				

                    

                        $total_order=0;

                        $total_commission=0;

                        

                       if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

			    		    foreach ($res as $val) {

			    		    	$date=prettyDate($val['date_created'],true);

	    			            $date=Yii::app()->functions->translateDate($date);

	    			            

	    			            $total_order=$total_order+$val['total_order'];

	    		                $total_commission=$total_commission+$val['total_commission'];

	    			            

			    			    $data[]=array(

						    	   $val['order_id'],

					               normalPrettyPrice($val['total_w_tax']),

					               normalPrettyPrice($val['percent_commision']),

					               normalPrettyPrice($val['total_commission']),

					               $date

			    			    );

				    		}			    		

			    	    }								    	    

	    			    $header=array(

	    			    t("Reference #"),

	    			    t("Total Price"),

	    			    t("Commission (%)"),

	    			    t("Commission price"),

	    			    t("Date")

	    			   );

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                        exit;                      

                    	break;     

                    

                    case "rptmerchantstatement":

                    	

                    	if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

                    		 foreach ($res as $val) {

			    		    	$date=prettyDate($val['date_created'],true);

	    			            $date=Yii::app()->functions->translateDate($date);

	    			            

	    			             $total=$val['total_w_tax'];

								 if ( $val['commision_ontop']==1){

								     $total=$val['sub_total'];

								 }

								    

								 $total_commission=$val['total_commission'];

								 $amount=$total-$total_commission;

	    			            

			    			    $data[]=array(

						    	   $val['order_id'],

						    	    strtoupper($val['payment_type']),

					    		    normalPrettyPrice($total),					    		    

					    		    normalPrettyPrice($val['percent_commision']),

					    		    normalPrettyPrice($total_commission),

					    		    normalPrettyPrice($amount),

					    		    $date

			    			    );

				    		}			 

                    	}                    	

                    	$header=array(

	    			    t("Reference #"),

	    			    t("Payment Type"),

	    			    t("Total Price"),	    			    

	    			    t("Commission (%)"),

	    			    t("Commission"),

	    			    t("Net Amount"),

	    			    t("Date")

	    			   );

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                    	

                    	exit;                      

                    	break; 

                      

                    case "rptmerchantsalesummary": 	

                        

                       $has_date_range=false;

                       if (isset($_SESSION['rpt_date_range'])){

	    			   	   if (is_array($_SESSION['rpt_date_range'])){

	    			   	   	   $has_date_range=true;

	    			   	   }

	    			   }

                       if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

                    		 foreach ($res as $val) {			    		    	

                    		 	if ($has_date_range==true){

                    		 		$data[]=array(

							    	   $val['restaurant_name'],

							    	   normalPrettyPrice($val['total_sales']+0),

							    	   normalPrettyPrice($val['total_commission']+0),

							    	   normalPrettyPrice($val['total_earnings']+0),

							    	   $_SESSION['rpt_date_range']['start_date'],

							    	   $_SESSION['rpt_date_range']['end_date'],

				    			    );

                    		 	} else {

				    			    $data[]=array(

							    	   $val['restaurant_name'],

							    	   normalPrettyPrice($val['total_sales']+0),

							    	   normalPrettyPrice($val['total_commission']+0),

							    	   normalPrettyPrice($val['total_earnings']+0),						    	   

				    			    );

                    		 	}

				    		}			 

                    	}                    	

                       $header=array(

	    			    t("Merchant Name"),

	    			    t("Total Sales"),

	    			    t("Total Commission"),

	    			    t("Merchant Earnings"),

	    			    //t("Approved No. Of Guests")

	    			   );

	    			   if ( $has_date_range==true) {

    			   	   	   $header[]=t("Start Date");

    			   	   	   $header[]=t("End Date");

	    			   }

	    			   	    			   

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	

                    	

                        exit;                      

                    	break; 

                    	

                    case "booking-summary-report":

                    	

                       $has_date_range=false;

                       if (isset($_SESSION['rpt_date_range'])){

	    			   	   if (is_array($_SESSION['rpt_date_range'])){

	    			   	   	   $has_date_range=true;

	    			   	   }

	    			   }

	    			   

                    	if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

                    		 foreach ($res as $val) {		

                    		 	if ( $has_date_range==true){

                    		 		$data[]=array(

							    	     $val['total_approved']+0,

			   	                         $val['total_denied']+0,

			   	                         $val['total_pending']+0,

			   	                         $_SESSION['rpt_date_range']['start_date'],

								    	 $_SESSION['rpt_date_range']['end_date'],

			    			        );

                    		 	} else {

				    			    $data[]=array(

							    	     $val['total_approved']+0,

			   	                         $val['total_denied']+0,

			   	                         $val['total_pending']+0

				    			    );

                    		 	}

				    		}			 

                    	}                    	

                    	$header=array(

	    			    t("Total Approved"),

	    			    t("Total Denied"),

	    			    t("Total Pending")	    			    

	    			   );

	    			   

	    			   if ( $has_date_range==true) {

    			   	   	   $header[]=t("Start Date");

    			   	   	   $header[]=t("End Date");

	    			   }

	    			   

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	                    	

                        exit;                  

                    	break;	

                    	

                    case "merchanBbookingSummaryReport":

                    	

                       $has_date_range=false;

                       if (isset($_SESSION['rpt_date_range'])){

	    			   	   if (is_array($_SESSION['rpt_date_range'])){

	    			   	   	   $has_date_range=true;

	    			   	   }

	    			   }

                    	                    

                       if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

                    		 foreach ($res as $val) {			    		    	

                    		 	if ( $has_date_range==true){

                    		 		 $data[]=array(

							    	    ucwords($val['merchant_name']),

			   	                        $val['total_approved']+0,

			   	                        $val['total_denied']+0,

			   	                        $val['total_pending']+0,

			   	                        $_SESSION['rpt_date_range']['start_date'],

							    	    $_SESSION['rpt_date_range']['end_date'],

				    			    );

                    		 	} else {                    		 

				    			    $data[]=array(

							    	    ucwords($val['merchant_name']),

			   	                        $val['total_approved']+0,

			   	                        $val['total_denied']+0,

			   	                        $val['total_pending']+0

				    			    );

                    		 	}

				    		}			 

                    	}                    	

                    	$header=array(

	    			    t("Merchant Name"),

	    			    t("Total Approved"),

	    			    t("Total Denied"),

	    			    t("Total Pending")	    			    

	    			   );

	    			   if ( $has_date_range==true) {

    			   	   	   $header[]=t("Start Date");

    			   	   	   $header[]=t("End Date");

	    			   }

	    			   

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	                    	

                        exit;                  

                    	break;	

                    	

                    case "rpt_incomingwithdrawal":

                    	

                    	 if ($res=$db_ext->rst($_SESSION['kr_export_stmt'])){

                    		 foreach ($res as $val) {	

                    		 	   $date=date('M d,Y G:i:s',strtotime($val['date_created']));  

 	    		                   $date=Yii::app()->functions->translateDate($date);

	    		

	    		                   $date_created=displayDate($val['date_created']);

	    		                   $date_to_process=displayDate($val['date_to_process']);

	    				    		    	

	    		                   $method=t("Paypal to")." ".$val['account'];

	    		                   if ( $val['payment_method']=="bank"){

	    			                    $method=t("Bank to")." ".$val['bank_account_number'];

	    		                   }

	    		                   	    		

                    		       $data[]=array(

								      $val['withdrawal_id'],

						    		  $val['merchant_name'],

						    		  $method,

						    		  normalPrettyPrice($val['amount']),

						    		  normalPrettyPrice($val['current_balance']),

						    		  $val['status'],

						    		  $date_created,

						    		  $date_to_process,	    		

				    			    );

				    		}			 

                    	}                    	

                    	$header=array(

	    			    t("ID"),

	    			    t("Merchant Name"),

	    			    t("Payment Method"),

	    			    t("Amount"),	    			    

	    			    t("From Balance"),

	    			    t("Status"),

	    			    t("Date Of Request"),

	    			    t("Date to process")

	    			   );

	    			 

	    			   

	    			    $filename = $this->data['rpt'].'-'. date('c') .'.csv';    	    

				    	$excel  = new ExcelFormat($filename);

				    	$excel->addHeaders($header);

                        $excel->setData($data);	  

                        $excel->prepareExcel();	                    	

                        exit;                  

                    	break; 	

	    			default:

	    				break;

	    		}

	    	} else echo Yii::t("default","Error: Something went wrong. please try again.");

	    }

	    

	    public function packagesAdd()

	    {	       	    	

	       if (isset($this->data['unlimited_post'])){

	       	   if ( $this->data['unlimited_post']==1){

	       	   	    if ($this->data['post_limit']<=0){

	       	   	    	$this->msg=Yii::t("default","Number of Food Item Can Add must be greate than 1");

	       	   	    	return ;

	       	   	    }	       	   

	       	   }	       

	       }	    

	       $params=array(

	         'title'=>$this->data['title'],

	         'description'=>$this->data['description'],

	         'price'=>$this->data['price'],

	         'promo_price'=>$this->data['promo_price'],

	         'expiration'=>$this->data['expiration'],

	         'status'=>$this->data['status'],

	         'date_created'=>date('c'),

	         'ip_address'=>$_SERVER['REMOTE_ADDR'],

	         'expiration_type'=>$this->data['expiration_type'],

	         'unlimited_post'=>$this->data['unlimited_post'],

	         'post_limit'=>$this->data['post_limit'],

	         'sell_limit'=>isset($this->data['sell_limit'])?$this->data['sell_limit']:''

	       );	        

	       if ( $this->data['expiration_type']=="year"){

	       	   $params['expiration']=$this->data['expiration']*365;

	       }	    

	       if (empty($this->data['id'])){	

		    	if ( $this->insertData("{{packages}}",$params)){

		    		$this->details=Yii::app()->db->getLastInsertID();

		    		$this->code=1;

		    		$this->msg=Yii::t("default","Successful");		    		

		    	}

		    } else {		    	

		    	unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $this->updateData('{{packages}}' , $params ,'package_id',$this->data['id']);

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Package updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

		    }	

	    }

	    

		public function packagesList()

		{

			

			$ListlimitedPost=Yii::app()->functions->ListlimitedPost();

			$slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{packages}}			

			ORDER BY package_id DESC

			";

			if ( $res=$this->rst($stmt)){

				foreach ($res as $val) {	

					/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));  				

					$date=Yii::app()->functions->translateDate($date);*/

					$date=FormatDateTime($val['date_created']);

					

					$action="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[package_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[package_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

					$val['title']=ucwords($val['title']);

					$feed_data['aaData'][]=array(

					  $val['package_id'],

					  $val['title'].$action,

					  '<span class="concat-text">'.$val['description'].'</span>',

					  Yii::app()->functions->standardPrettyFormat($val['price']),

					  Yii::app()->functions->standardPrettyFormat($val['promo_price']),

					  $val['expiration'],

					  $ListlimitedPost[$val['unlimited_post']],

					  $val['sell_limit']>=1?$val['sell_limit']:'',

					  $date."<div>".Yii::t("default",$val['status'])."</div>"					  

					);

				}

				$this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function saveAdminPaypalSettings()

		{			

		    Yii::app()->functions->updateOptionAdmin("admin_enabled_paypal",

	    	isset($this->data['admin_enabled_paypal'])?$this->data['admin_enabled_paypal']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_paypal_mode",

	    	isset($this->data['admin_paypal_mode'])?$this->data['admin_paypal_mode']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sanbox_paypal_user",

	    	isset($this->data['admin_sanbox_paypal_user'])?$this->data['admin_sanbox_paypal_user']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sanbox_paypal_pass",

	    	isset($this->data['admin_sanbox_paypal_pass'])?$this->data['admin_sanbox_paypal_pass']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sanbox_paypal_signature",

	    	isset($this->data['admin_sanbox_paypal_signature'])?$this->data['admin_sanbox_paypal_signature']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_live_paypal_user",

	    	isset($this->data['admin_live_paypal_user'])?$this->data['admin_live_paypal_user']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_live_paypal_pass",

	    	isset($this->data['admin_live_paypal_pass'])?$this->data['admin_live_paypal_pass']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_live_paypal_signature",

	    	isset($this->data['admin_live_paypal_signature'])?$this->data['admin_live_paypal_signature']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_paypal_fee",

	    	isset($this->data['admin_paypal_fee'])?$this->data['admin_paypal_fee']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("adm_paypal_mobile_enabled",

	    	isset($this->data['adm_paypal_mobile_enabled'])?$this->data['adm_paypal_mobile_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("adm_paypal_mobile_mode",

	    	isset($this->data['adm_paypal_mobile_mode'])?$this->data['adm_paypal_mobile_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("adm_paypal_mobile_clientid",

	    	isset($this->data['adm_paypal_mobile_clientid'])?$this->data['adm_paypal_mobile_clientid']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}	

		

		public function adminSettings()

		{			

			

			Yii::app()->functions->updateOptionAdmin("website_disbaled_auto_cart",

	    	isset($this->data['website_disbaled_auto_cart'])?$this->data['website_disbaled_auto_cart']:'');

	    	

			Yii::app()->functions->updateOptionAdmin("website_enabled_mobile_verification",

	    	isset($this->data['website_enabled_mobile_verification'])?$this->data['website_enabled_mobile_verification']:'');

	    	

			Yii::app()->functions->updateOptionAdmin("website_date_picker_format",

	    	isset($this->data['website_date_picker_format'])?$this->data['website_date_picker_format']:'');

	    	

			Yii::app()->functions->updateOptionAdmin("website_time_picker_format",

	    	isset($this->data['website_time_picker_format'])?$this->data['website_time_picker_format']:'');

	    	

			Yii::app()->functions->updateOptionAdmin("website_date_format",

	    	isset($this->data['website_date_format'])?$this->data['website_date_format']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_time_format",

	    	isset($this->data['website_time_format'])?$this->data['website_time_format']:'');

	    	

			Yii::app()->functions->updateOptionAdmin("merchant_sigup_status",

	    	isset($this->data['merchant_sigup_status'])?$this->data['merchant_sigup_status']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_email_verification",

	    	isset($this->data['merchant_email_verification'])?$this->data['merchant_email_verification']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_payment_enabled",

	    	isset($this->data['merchant_payment_enabled'])?$this->data['merchant_payment_enabled']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_enabled_paypal",

	    	isset($this->data['admin_enabled_paypal'])?$this->data['admin_enabled_paypal']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_enabled_card",

	    	isset($this->data['admin_enabled_card'])?$this->data['admin_enabled_card']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_country_set",

	    	isset($this->data['admin_country_set'])?$this->data['admin_country_set']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_currency_set",

	    	isset($this->data['admin_currency_set'])?$this->data['admin_currency_set']:'');

	    		    	

	    	Yii::app()->functions->updateOptionAdmin("home_search_text",

	    	isset($this->data['home_search_text'])?$this->data['home_search_text']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("home_search_subtext",

	    	isset($this->data['home_search_subtext'])?$this->data['home_search_subtext']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("home_search_mode",

	    	isset($this->data['home_search_mode'])?$this->data['home_search_mode']:'' );	    	

	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_logo",

	    	isset($this->data['photo'])?$this->data['photo']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_title",

	    	isset($this->data['website_title'])?$this->data['website_title']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_address",

	    	isset($this->data['website_address'])?$this->data['website_address']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_contact_phone",

	    	isset($this->data['website_contact_phone'])?$this->data['website_contact_phone']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_contact_email",

	    	isset($this->data['website_contact_email'])?$this->data['website_contact_email']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_decimal_place",

	    	isset($this->data['admin_decimal_place'])?$this->data['admin_decimal_place']:'' );	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_use_separators",

	    	isset($this->data['admin_use_separators'])?$this->data['admin_use_separators']:'' );	  

	    	

	    	Yii::app()->functions->updateOptionAdmin("google_auto_address",

	    	isset($this->data['google_auto_address'])?$this->data['google_auto_address']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("home_search_radius",

	    	isset($this->data['home_search_radius'])?$this->data['home_search_radius']:'' );	    	  	

	    	

	    	Yii::app()->functions->updateOptionAdmin("home_search_unit_type",

	    	isset($this->data['home_search_unit_type'])?$this->data['home_search_unit_type']:'' );	    	  	

	    	

	    	Yii::app()->functions->updateOptionAdmin("google_default_country",

	    	isset($this->data['google_default_country'])?$this->data['google_default_country']:'' );	    	  	

	    	

	    	Yii::app()->functions->updateOptionAdmin("enabled_advance_search",

	    	isset($this->data['enabled_advance_search'])?$this->data['enabled_advance_search']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_share_location",

	    	isset($this->data['disabled_share_location'])?$this->data['disabled_share_location']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("enabled_search_map",

	    	isset($this->data['enabled_search_map'])?$this->data['enabled_search_map']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_currency_position",

	    	isset($this->data['admin_currency_position'])?$this->data['admin_currency_position']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_default_country",

	    	isset($this->data['merchant_default_country'])?$this->data['merchant_default_country']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_specific_country",

	    	isset($this->data['merchant_specific_country'])?json_encode($this->data['merchant_specific_country']):'' );

	    		    	

	    	Yii::app()->functions->updateOptionAdmin("map_marker",

	    	isset($this->data['photo2'])?$this->data['photo2']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("global_admin_sender_email",

	    	isset($this->data['global_admin_sender_email'])?$this->data['global_admin_sender_email']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_disabled_registration",

	    	isset($this->data['merchant_disabled_registration'])?$this->data['merchant_disabled_registration']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_subscription",

	    	isset($this->data['disabled_subscription'])?$this->data['disabled_subscription']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_featured_merchant",

	    	isset($this->data['disabled_featured_merchant'])?$this->data['disabled_featured_merchant']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_days_can_edit_status",

	    	isset($this->data['merchant_days_can_edit_status'])?$this->data['merchant_days_can_edit_status']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_website_ordering",

	    	isset($this->data['disabled_website_ordering'])?$this->data['disabled_website_ordering']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_activated_menu",

	    	isset($this->data['admin_activated_menu'])?$this->data['admin_activated_menu']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_hide_foodprice",

	    	isset($this->data['website_hide_foodprice'])?$this->data['website_hide_foodprice']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_cc_management",

	    	isset($this->data['disabled_cc_management'])?$this->data['disabled_cc_management']:'' );

	    		    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_reg_abn",

	    	isset($this->data['merchant_reg_abn'])?$this->data['merchant_reg_abn']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("spicydish",

	    	isset($this->data['spicydish'])?$this->data['spicydish']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_timezone",

	    	isset($this->data['website_timezone'])?$this->data['website_timezone']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_admin_mutiple_login",

	    	isset($this->data['website_admin_mutiple_login'])?$this->data['website_admin_mutiple_login']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_merchant_mutiple_login",

	    	isset($this->data['website_merchant_mutiple_login'])?$this->data['website_merchant_mutiple_login']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_disabled_guest_checkout",

	    	isset($this->data['website_disabled_guest_checkout'])?$this->data['website_disabled_guest_checkout']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_reviews_actual_purchase",

	    	isset($this->data['website_reviews_actual_purchase'])?$this->data['website_reviews_actual_purchase']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_terms_merchant",

	    	isset($this->data['website_terms_merchant'])?$this->data['website_terms_merchant']:'' );

	    	Yii::app()->functions->updateOptionAdmin("website_terms_merchant_url",

	    	isset($this->data['website_terms_merchant_url'])?$this->data['website_terms_merchant_url']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_terms_customer",

	    	isset($this->data['website_terms_customer'])?$this->data['website_terms_customer']:'' );

	    	Yii::app()->functions->updateOptionAdmin("website_terms_customer_url",

	    	isset($this->data['website_terms_customer_url'])?$this->data['website_terms_customer_url']:'' );

	    		    	

	    	Yii::app()->functions->updateOptionAdmin("admin_thousand_separator",

	    	isset($this->data['admin_thousand_separator'])?$this->data['admin_thousand_separator']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_decimal_separator",

	    	isset($this->data['admin_decimal_separator'])?$this->data['admin_decimal_separator']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_disabled_login_popup",

	    	isset($this->data['website_disabled_login_popup'])?$this->data['website_disabled_login_popup']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_can_edit_reviews",

	    	isset($this->data['merchant_can_edit_reviews'])?$this->data['merchant_can_edit_reviews']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_enabled_rcpt",

	    	isset($this->data['website_enabled_rcpt'])?$this->data['website_enabled_rcpt']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_receipt_logo",

	    	isset($this->data['website_receipt_logo'])?$this->data['website_receipt_logo']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_cart_sticky",

	    	isset($this->data['disabled_cart_sticky'])?$this->data['disabled_cart_sticky']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("search_result_bydistance",

	    	isset($this->data['search_result_bydistance'])?$this->data['search_result_bydistance']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("google_geo_api_key",

	    	isset($this->data['google_geo_api_key'])?$this->data['google_geo_api_key']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("website_enabled_map_address",

	    	isset($this->data['website_enabled_map_address'])?$this->data['website_enabled_map_address']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("client_custom_field_name1",

	    	isset($this->data['client_custom_field_name1'])?$this->data['client_custom_field_name1']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("client_custom_field_name2",

	    	isset($this->data['client_custom_field_name2'])?$this->data['client_custom_field_name2']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_days_can_edit_status_basedon",

	    	isset($this->data['merchant_days_can_edit_status_basedon'])?$this->data['merchant_days_can_edit_status_basedon']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_status_disabled",

	    	isset($this->data['merchant_status_disabled'])?$this->data['merchant_status_disabled']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("view_map_disabled",

	    	isset($this->data['view_map_disabled'])?$this->data['view_map_disabled']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("view_map_default_zoom",

	    	isset($this->data['view_map_default_zoom'])?$this->data['view_map_default_zoom']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("view_map_default_zoom_s",

	    	isset($this->data['view_map_default_zoom_s'])?$this->data['view_map_default_zoom_s']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("receipt_default_subject",

	    	isset($this->data['receipt_default_subject'])?$this->data['receipt_default_subject']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_tbl_book_disabled",

	    	isset($this->data['merchant_tbl_book_disabled'])?$this->data['merchant_tbl_book_disabled']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_changeorder_sms",

	    	isset($this->data['merchant_changeorder_sms'])?$this->data['merchant_changeorder_sms']:'' );

	    	

	    	Yii::app()->functions->updateOptionAdmin("customer_ask_address",

	    	isset($this->data['customer_ask_address'])?$this->data['customer_ask_address']:'' );

	    		    		    		   	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_site_key",

	    	isset($this->data['captcha_site_key'])?$this->data['captcha_site_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_secret",

	    	isset($this->data['captcha_secret'])?$this->data['captcha_secret']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_lang",

	    	isset($this->data['captcha_lang'])?$this->data['captcha_lang']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_customer_signup",

	    	isset($this->data['captcha_customer_signup'])?$this->data['captcha_customer_signup']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_merchant_signup",

	    	isset($this->data['captcha_merchant_signup'])?$this->data['captcha_merchant_signup']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_customer_login",

	    	isset($this->data['captcha_customer_login'])?$this->data['captcha_customer_login']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_merchant_login",

	    	isset($this->data['captcha_merchant_login'])?$this->data['captcha_merchant_login']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_admin_login",

	    	isset($this->data['captcha_admin_login'])?$this->data['captcha_admin_login']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("captcha_order",

	    	isset($this->data['captcha_order'])?$this->data['captcha_order']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("blocked_email_add",

	    	isset($this->data['blocked_email_add'])?$this->data['blocked_email_add']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("blocked_mobile",

	    	isset($this->data['blocked_mobile'])?$this->data['blocked_mobile']:'');

	    	

    	 	Yii::app()->functions->updateOptionAdmin("admin_zipcode_searchtype",

	    	isset($this->data['admin_zipcode_searchtype'])?$this->data['admin_zipcode_searchtype']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("mobilelogo",

	    	isset($this->data['mobilelogo'])?$this->data['mobilelogo']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_enabled_email_verification",

	    	isset($this->data['theme_enabled_email_verification'])?$this->data['theme_enabled_email_verification']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("google_distance_method",

	    	isset($this->data['google_distance_method'])?$this->data['google_distance_method']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("google_use_curl",

	    	isset($this->data['google_use_curl'])?$this->data['google_use_curl']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_menu_allowed_merchant",

	    	isset($this->data['admin_menu_allowed_merchant'])?$this->data['admin_menu_allowed_merchant']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}

		

		public function merchantSignUp()

		{		    

						

			/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			} 



            /** check if admin has enabled the google captcha*/    	    	

	    	if ( getOptionA('captcha_merchant_signup')==2){

	    		if ( GoogleCaptcha::checkCredentials()){

	    			if ( !GoogleCaptcha::validateCaptcha()){

	    				$this->msg=GoogleCaptcha::$message;

	    				return false;

	    			}	    		

	    		}	    	

	    	}

	    	

	    	if (isset($this->data['cpassword'])){

	    		if ($this->data['cpassword']!=$this->data['password']){

	    			$this->msg=t("Confirm password does not match");

	    			return ;

	    		}

	    	}

	    				

			if (Yii::app()->functions->isMerchantExist($this->data['contact_email'])){

				$this->msg=Yii::t("default","Sorry you input email address that is already registered in our records.");

				return ;

			}		

			if (!isset($this->data['package_id'])){

				$this->msg=Yii::t("default","ERROR: Missing package id");

				return ;

			}	

			

			if ( !$package=Yii::app()->functions->getPackagesById($this->data['package_id'])){

				$this->msg=Yii::t("default","ERROR: Package information not found");

				return ;

			}		

			

			

			$package_price=0;

			if ( $package['promo_price']>=1){

				$package_price=$package['promo_price'];

			} else $package_price=$package['price'];			

			

			$expiration=$package['expiration'];			

			$membership_expired = date('Y-m-d', strtotime ("+$expiration days"));							

			

			$status=Yii::app()->functions->getOptionAdmin('merchant_sigup_status');			

			$token=md5($this->data['restaurant_name'].date('c'));

			

			$p = new CHtmlPurifier();

			

		    $params=array(

		      'restaurant_name'=>$p->purify(addslashes($this->data['restaurant_name'])),

		      'restaurant_phone'=>$p->purify($this->data['restaurant_phone']),

		      'contact_name'=>$p->purify($this->data['contact_name']),

		      'contact_phone'=>$p->purify($this->data['contact_phone']),

		      'contact_email'=>$p->purify($this->data['contact_email']),

		      'street'=>$p->purify(addslashes($this->data['street'])),

		      'city'=>$p->purify(addslashes($this->data['city'])),

		      'post_code'=>$p->purify(addslashes($this->data['post_code'])),

		      'cuisine'=>json_encode($this->data['cuisine']),

		      'username'=>$p->purify($this->data['username']),

		      'password'=>md5($this->data['password']),

		      'status'=>$status,

		      'date_created'=>date('c'),

		      'ip_address'=>$_SERVER['REMOTE_ADDR'],

		      'activation_token'=>$token,

		      'activation_key'=>Yii::app()->functions->generateRandomKey(5),

		      'restaurant_slug'=>Yii::app()->functions->createSlug($this->data['restaurant_name']),

		      'package_id'=>$this->data['package_id'],

		      'package_price'=>$package_price,

		      'membership_expired'=>$membership_expired,

		      'payment_steps'=>2,

		      //'country_code'=>Yii::app()->functions->adminSetCounryCode(),

		      'country_code'=>$p->purify($this->data['country_code']),

		      'state'=>$p->purify(addslashes($this->data['state'])),

		      'abn'=>isset($this->data['abn'])?$p->purify($this->data['abn']):'',

		      'service'=>isset($this->data['service'])?$p->purify($this->data['service']):'',

		    );				 

		    		    

		    /*dump($params);

		    die();*/

		    

		    if ( !Yii::app()->functions->validateUsername($this->data['username']) ){

		    			    	

		    	if ($respck=Yii::app()->functions->validateMerchantUserFromMerchantUser($params['username'],

		    	    $params['contact_email'])){

		    		$this->msg=$respck;

		    		return ;		    		

		    	}		    

		    			    	

		    	

			    if ($this->insertData("{{merchant}}",$params)){

			    	$mtid=Yii::app()->db->getLastInsertID();

			    				    	

			    	/*special category*/

			    	//ClassCategory::autoInsertCategory($mtid);

			    	

			    	$this->code=1;

			    	$this->msg=Yii::t("default","Successful");

			    	$this->details=$token;

			    	

			    	// send email activation key

			    	$tpl=EmailTPL::merchantActivationCode($params);

		            $sender=Yii::app()->functions->getOptionAdmin('website_contact_email');

		            $to=$this->data['contact_email'];		    		  

		            if (!sendEmail($to,$sender,"Merchant Registration",$tpl)){		    	

		            	//$this->details="failed";

		            } //else $this->details="ok mail";

		            			    				    				    	

			    } else $this->msg=Yii::t("default","Sorry but we cannot add your information. Please try again later");

		    } else $this->msg=Yii::t("default","Sorry but your username is alread been taken.");

		}

		

		public function activationMerchant()

		{		   

		   $merchant_status=Yii::app()->functions->getOptionAdmin("merchant_sigup_status");		   

			

		   if (isset($this->data['activation_code']) && isset($this->data['token'])){

		      $stmt="SELECT * FROM		   

		      {{merchant}}

		      WHERE

		      activation_token='".$this->data['token']."'

		      LIMIT 0,1

		      ";

		      if ($res=$this->rst($stmt)){		      	 		      	 

		      	 if ($res[0]['status']=="active"){

		      	 	$this->msg=Yii::t("default","Merchant is already activated.");

		      	 } else {		      

			      	 if ($res[0]['activation_key']==$this->data['activation_code']){

			      	 	$this->code=1;		      	 	

			      	 	$this->msg=Yii::t("default","Merchant Successfully activated.");

			      	 	$this->details=$this->data['token'];

			      	 	

			      	 	$params=array('status'=>"active",'date_activated'=>date('c'),'ip_address'=>$_SERVER['REMOTE_ADDR']);

			      	 				      	 	

			      	 	/*If payment was offline don't set the the status to active*/

			      	 	if ( $package_info=FunctionsV3::getMerchantPaymentMembership($res[0]['merchant_id'],

			      	 	$res[0]['package_id'])){			      	 		

			      	 		$offline_payment=FunctionsV3::getOfflinePaymentList();			      	 		

			      	 		if ( array_key_exists($package_info['payment_type'],(array)$offline_payment)){

			      	 			unset($params['status']);

			      	 		}

			      	 	}			      	 

			      	 				      	 	

			      	 	$this->updateData("{{merchant}}",$params,'merchant_id',$res[0]['merchant_id']);

			      	 	

			      	 } else $this->msg=Yii::t("default","Invalid Activation code.");		      

		      	 }

		      } else $this->msg=Yii::t("default","Sorry but we cannot find your information.");

		   } else $this->msg=Yii::t("default","ERROR: Missing parameters");

		}

		

		public function addCreditCardMerchant()

		{					

			$params=$this->data;

			unset($params['action']);

			unset($params['cc_id']);

			

			if (isset($params['currentController'])){

				unset($params['currentController']);

			}

		

			$params['date_created']=date('');

			$params['ip_address']=$_SERVER['REMOTE_ADDR'];			

			if ($this->insertData("{{merchant_cc}}",$params)){

				$this->code=1;

				$this->msg=Yii::t("default","Card added.");

			} else $this->msg=Yii::t("default","ERROR: Cannot insert records.");		

		}

		

		public function merchantPayment()

		{						

					

			$token=isset($this->data['token'])?$this->data['token']:'';

			if (isset($this->data['payment_opt'])){

				if (!$merchant=Yii::app()->functions->getMerchantByToken($token)){

					$this->msg=Yii::t("default","ERROR: cannot get merchant information");

					return ;

				}																						

				if ( $this->data['payment_opt']=="ccr" || $this->data['payment_opt']=="ocr" ){

					if (is_numeric($this->data['cc_id'])){



						if (isset($this->data['renew'])){

							

							$membership_info=Yii::app()->functions->upgradeMembership($merchant['merchant_id'],$this->data['package_id']);

							

							$params=array(

							  'package_id'=>$this->data['package_id'],

							  'merchant_id'=>$merchant['merchant_id'],

							  'price'=>$membership_info['package_price'],

							  'payment_type'=>$this->data['payment_opt'],

							  'mt_id'=>$this->data['cc_id'],

							  'date_created'=>date('c'),

							  'ip_address'=>$_SERVER['REMOTE_ADDR'],

							  'membership_expired'=>$membership_info['membership_expired']

							);							

							$this->insertData("{{package_trans}}",$params);

							

							$params_update=array(

							  'package_id'=>$this->data['package_id'],

							  'package_price'=>$membership_info['package_price'],

							  'membership_expired'=>$membership_info['membership_expired'],

							  'membership_purchase_date'=>date('c'),

							  'status'=>'active'

							);

							

							$this->updateData("{{merchant}}",$params_update,'merchant_id',$merchant['merchant_id']);

							$this->code=1;

							$this->msg=Yii::t("default","Payment Successful");

							

						} else {					

							$params=array(

							  'package_id'=>$merchant['package_id'],

							  'merchant_id'=>$merchant['merchant_id'],

							  'price'=>$merchant['package_price'],

							  'payment_type'=>$this->data['payment_opt'],

							  'mt_id'=>$this->data['cc_id'],

							  'date_created'=>date('c'),

							  'ip_address'=>$_SERVER['REMOTE_ADDR']

							);

							

							if ( $package=Yii::app()->functions->getPackagesById($merchant['package_id'])){

								$expiration=$package['expiration'];

	                            $membership_expired = date('Y-m-d', strtotime ("+$expiration days"));							

	                            $params['membership_expired']=$membership_expired;

				            }	

				            if ($this->insertData("{{package_trans}}",$params)){

							$this->code=1;

							$this->msg=Yii::t("default","Payment Successful");

							$this->details=$token;

							

							$this->updateData("{{merchant}}",

							   array(

							     'payment_steps'=>3,

							     'membership_purchase_date'=>date('c')

							   )

							   ,'merchant_id',$merchant['merchant_id']);

							   

						    } else $this->msg=Yii::t("default","ERROR: Cannot insert records.");

						}

			            												

					} else $this->msg=Yii::t("default","Please select credit card.");			

							

				} elseif ($this->data['payment_opt']=="pyp"){					

					

					if (isset($this->data['renew'])){						

						if ($new_info=Yii::app()->functions->getPackagesById($this->data['package_id'])){

							$package_price=$new_info['price'];

							if ( $new_info['promo_price']>0){

								$package_price=$new_info['promo_price'];

							}			

							$merchant['package_name']=$new_info['title'];

							$merchant['package_id']=$new_info['package_id'];							

						} else $package_price=0;  					

					} else {

					    $package_price=$merchant['package_price'];

					}

															

					$paypal_con=Yii::app()->functions->getPaypalConnectionAdmin();					

					

					$params='';

					$x=0;

					$params['L_NAME'.$x]=isset($merchant['package_name'])?$merchant['package_name']:Yii::t("default","No description");

			        $params['L_NUMBER'.$x]=$merchant['package_id'];

			        $params['L_DESC'.$x]=isset($merchant['package_name'])?$merchant['package_name']:Yii::t("default","No description");

			        $params['L_AMT'.$x]=normalPrettyPrice($package_price);

			        $params['L_QTY'.$x]=1;					

					

					$params['AMT']=normalPrettyPrice($package_price);

					

					/** add card fee */

					$card_fee=Yii::app()->functions->getOptionAdmin('admin_paypal_fee');

					if (!empty($card_fee) && $card_fee>=0.1){

						$x++;

						$params['L_NAME'.$x]=t("Card Fee");						

						$params['L_DESC'.$x]=t("Card Fee");

						$params['L_AMT'.$x]=normalPrettyPrice($card_fee);

			            $params['L_QTY'.$x]=1;					

						$params['AMT']=$params['AMT']+$card_fee;

					}									

					

					if (isset($this->data['renew'])){

						$params['RETURNURL']="http://".$_SERVER['HTTP_HOST'].Yii::app()->request->baseUrl."/merchantsignup/?Do=step3a&internal-token=$token&renew=1&package_id=".$this->data['package_id'];

					} else {

				       $params['RETURNURL']="http://".$_SERVER['HTTP_HOST'].Yii::app()->request->baseUrl."/merchantsignup/?Do=step3a&internal-token=$token";

					}

																	

				    $params['CANCELURL']="http://".$_SERVER['HTTP_HOST'].Yii::app()->request->baseUrl."/merchantsignup/?Do=step3&internal-token=$token";	  	  

				    $params['NOSHIPPING']='1';

			        $params['LANDINGPAGE']='Billing';

			        $params['SOLUTIONTYPE']='Sole';

			        $params['CURRENCYCODE']=adminCurrencyCode();

			        			       			        

			        $paypal=new Paypal($paypal_con);

			  	    $paypal->params=$params;

			  	    $paypal->debug=false;

			  	    if ($resp=$paypal->setExpressCheckout()){  	   	  			  	  	  

			  	  	  $this->code=1;

			  	  	  $this->msg=Yii::t("default","Please wait while we redirect you to paypal.");

			  	  	  $this->details=$resp['url'];

			  	    } else $this->msg=$paypal->getError();

								

				} elseif ($this->data['payment_opt']=="stp"){ /*STRIPE*/

					

                      $this->code=1;

                      $this->msg=Yii::t("default","Please wait while we redirect you to stripe");

                      //$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token";

                      $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                        'Do'=>"step3b",

                        'token'=>$token

                      ));

                      if (isset($this->data['renew'])){

                      	 /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/renew/1/package_id/".$this->data['package_id'];*/

                      	 $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	   'Do'=>"step3b",

                      	   'token'=>$token,

                      	   'renew'=>1,

                      	   'package_id'=>$this->data['package_id']

                      	 ));

                      }                                            

                      

                } elseif ($this->data['payment_opt']=="mcd"){  /*MERCADO*/     	  

                	

                      $this->code=1;                      

                      $this->msg=Yii::t("default","Please wait while we redirect you to mercadopago");

                      /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/mcd";*/

                      $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                        'Do'=>"step3b",

                        'token'=>$token,

                        'gateway'=>"mcd"

                      ));

                      if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/mcd/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('store/merchantsignup',array(

                      	    'Do'=>"step3b",

                      	    'token'=>$token,

                      	    'gateway'=>"mcd",

                      	    'renew'=>1,

                      	    'package_id'=>$this->data['package_id']

                      	  ));

                      }

                    

                } elseif ($this->data['payment_opt']=="pyl"){  /*PAYLINE*/     	  

                	                	

                	$this->code=1;                      

                    $this->msg=Yii::t("default","Please wait while we redirect you to payline");

                    //$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/pyl";                                       

                    $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      'Do'=>"step3b",

                      'token'=>$token,

                      'gateway'=>"pyl"

                    ));

                    

                    if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/pyl/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	    'Do'=>"step3b",

                      	    'token'=>$token,

                      	    'gateway'=>'pyl',

                      	    'renew'=>1,

                      	    'package_id'=>$this->data['package_id']

                      	  ));

                      }

                      

               } elseif ($this->data['payment_opt']=="ide"){  /*PAYLINE*/     	  

                	                	

                	$this->code=1;                      

                    $this->msg=Yii::t("default","Please wait while we redirect you to Sisow");

                    //$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/ide";

                    $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      'Do'=>"step3b",

                      'token'=>$token,

                      'gateway'=>"ide"

                    ));

                    

                    if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/ide/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	    'Do'=>"step3b",

                      	    'token'=>$token,

                      	    'gateway'=>"ide",

                      	    'renew'=>1,

                      	    'package_id'=>$this->data['package_id']

                      	  ));

                      }                      

                                                  

               } elseif ($this->data['payment_opt']=="payu"){  /*PAYLINE*/     	  

                	                	

                	$this->code=1;                      

                    $this->msg=Yii::t("default","Please wait while we redirect you to PayUMoney");

                    //$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/payu";

                    $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      'Do'=>"step3b",

                      'token'=>$token,

                      'gateway'=>'payu'

                    ));

                    

                    if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/payu/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('/store/merchantsignup',array(  

                      	     'Do'=>"step3b",

                      	     'token'=>$token,

                      	     'gateway'=>'payu',

                      	     'renew'=>1,

                      	     'package_id'=>$this->data['package_id']

                      	  ));

                     }                      

                           

                } elseif ($this->data['payment_opt']=="obd"){   // offline bank deposit  



                	 if (Yii::app()->functions->offlineBankDeposit($merchant,$this->data)){

                	 	

                	 	 $this->code=1;                  	 	 

                	 	 $this->msg=Yii::t("default","Thank You. an email has been sent to your email.");  

                         //$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/thankyou3/token/$token/";

                         $this->details=Yii::app()->createUrl('/store/merchantsignup',array(  

                           'Do'=>'thankyou3',

                           'token'=>$token

                         ));

                	 	

                	 } else $this->msg=Yii::t("default","Error: cannot send bank instructions email"); 

                	                 	                	 

                } elseif ($this->data['payment_opt']=="pys"){   // paysera

                	

                	$this->code=1;                      

                    $this->msg=Yii::t("default","Please wait while we redirect you to paysera");

                    //$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/pys"; 

                    $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      'Do'=>"step3b",

                      'token'=>$token,

                      'gateway'=>'pys'

                    ));

                    

                    if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/pys/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	    'Do'=>"step3b",

                      	    'token'=>$token,

                      	    'gateway'=>'pys',

                      	    'renew'=>1,

                      	    'package_id'=>$this->data['package_id']

                      	  ));

                     }                      	 

                	                 	                 

                     

                } elseif ($this->data['payment_opt']=="bcy"){   // barclay

                	                	

                	$this->code=1;                      

                    $this->msg=Yii::t("default","Please wait while we redirect you");

                    /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/".$this->data['payment_opt']; */                                      

                    $this->details=Yii::app()->createUrl('store/merchantsignup',array(

                      'Do'=>"step3b",

                      'token'=>$token,

                      'gateway'=>$this->data['payment_opt']

                    ));

                    

                    if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/bcy/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	    'Do'=>"step3b",

                      	    'token'=>$token,

                      	    'gateway'=>"bcy",

                      	    'renew'=>1,

                      	    'package_id'=>$this->data['package_id']

                      	  ));

                     }                      	 

                          

                } elseif ($this->data['payment_opt']=="epy"){   // EpayBg

                	                	                	

                	$this->code=1;                      

                    $this->msg=Yii::t("default","Please wait while we redirect you");

                    /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/".$this->data['payment_opt']; */                                                          

                    $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                       'Do'=>"step3b",

                       'token'=>$token,

                       'gateway'=>$this->data['payment_opt']

                    ));

                    if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/epy/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	     'Do'=>"step3b",

                      	     'token'=>$token,

                      	     'gateway'=>'epy',

                      	     'renew'=>1,

                      	     'package_id'=>$this->data['package_id']

                      	  ));

                     }                      	                          

                     

                

                /*braintree*/   

                } elseif ($this->data['payment_opt']=="btr"){   // braintree

                	                	                	

                	$this->code=1;                      

                    $this->msg=Yii::t("default","Please wait while we redirect you");

                    /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/".$this->data['payment_opt']; */                                                          

                    

                    $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      'Do'=>"step3b",

                      'token'=>$token,

                      'gateway'=>$this->data['payment_opt']

                    ));

                    

                    if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/btr/renew/1/package_id/".$this->data['package_id'];*/

                      	  $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	    'Do'=>"step3b",

                      	    'token'=>$token,

                      	    'gateway'=>'btr',

                      	    'renew'=>1,

                      	    'package_id'=>$this->data['package_id']

                      	  ));

                     }                  

                                   

				} else {

					

					if ( isset($this->data['payment_opt'])){

						

						$this->code=1;    

						$this->msg=Yii::t("default","Please wait while we redirect you");

                        /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/".$this->data['payment_opt']; */                     

                        

                        $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                          'Do'=>'step3b',

                          'token'=>$token,

                          'gateway'=>$this->data['payment_opt']

                        ));

                        

                        if (isset($this->data['renew'])){

                      	  /*$this->details=Yii::app()->request->baseUrl."/store/merchantsignup/Do/step3b/token/$token/gateway/".$this->data['payment_opt']."/renew/1/package_id/".$this->data['package_id'];*/

                      	  

                      	    $this->details=Yii::app()->createUrl('/store/merchantsignup',array(

                      	       'Do'=>"step3b",

                      	       'token'=>$token,

                      	       'gateway'=>$this->data['payment_opt'],

                      	       'renew'=>1,

                      	       'package_id'=>$this->data['package_id']

                      	    ));

                         }                      	                          

						

					} else $this->msg=Yii::t("default","No payment method has been selected.");					

				}

				

			} else $this->msg=Yii::t("default","Please select payment option");		

		}

		

		public function merchantFreePayment()

		{						

			if (!$merchant=Yii::app()->functions->getMerchantByToken($this->data['token'])){			

				$this->msg=Yii::t("default","ERROR: Cannot get package information");

				return ;

			}	

			$this->updateData("{{merchant}}",

							   array('payment_steps'=>3),'merchant_id',$merchant['merchant_id']);

		}

		

		public function rptSalesMerchant()

		{			

			$and='';  

	    	if (isset($this->data['start_date']) && isset($this->data['end_date']))	{

	    		if (!empty($this->data['start_date']) && !empty($this->data['end_date'])){

	    		$and=" WHERE date_created BETWEEN  '".$this->data['start_date']." 00:00:00' AND 

	    		        '".$this->data['end_date']." 23:59:00'

	    		 ";

	    		}

	    	}

	    	

	    	$order_status_id='';

	    	$or='';

	    	if (isset($this->data['stats_id'])){

		    	if (is_array($this->data['stats_id']) && count($this->data['stats_id'])>=1){

		    		foreach ($this->data['stats_id'] as $stats_id) {		    			

		    			$order_status_id.="'$stats_id',";

		    		}

		    		if ( !empty($order_status_id)){

		    			$order_status_id=substr($order_status_id,0,-1);

		    		}		    	

		    	}	    

	    	}

	    	

	    	if ( !empty($order_status_id)){	    		

	    		if (empty($and)){

	    			$and.= " WHERE status IN ($order_status_id)";

	    		} else $and.= " AND status IN ($order_status_id)";

	    	}	    	    	

	    		    

	    	

	    	$DbExt=new DbExt;

	    	$stmt="SELECT a.*,

	    	(

	    	select title 

	    	from

	    	{{packages}} 

	    	where

	    	package_id=a.package_id

	    	) as package_name

	    	 FROM

	    	{{merchant}} a	    	

	    	$and

	    	LIMIT 0,2000

	    	";	    	

	    	//dump($stmt);

	    	$_SESSION['kr_export_stmt']=$stmt;

	    	

	    	if ($res=$this->rst($stmt)){	    		

	    		foreach ($res as $val) {	   

	    			

	    		   $action="<a data-id=\"".$val['merchant_id']."\" class=\"edit-merchant-status\" href=\"javascript:\">".Yii::t("default","Edit")."</a>"; 				

	    		    /*$date=Yii::app()->functions->prettyDate($val['date_created']);

	    		    $date=Yii::app()->functions->translateDate($date);    			*/

	    		    

	    		    $date=FormatDateTime($val['date_created'],false);

	    		    

	    			$feed_data['aaData'][]=array(

	    			  $val['merchant_id'],

	    			  $val['restaurant_name'],

	    			  $val['contact_name'],

	    			  $val['contact_phone']." / ".$val['contact_email'],

	    			  $val['street']." ".$val['city']." ".$val['state']." ".$val['country_code']." ".$val['post_code'],

	    			  ucwords($val['package_name']),

	    			  //$val['package_price']>=1?Yii::app()->functions->standardPrettyFormat($val['package_price']):"",	 



	    			  $date."<br/>".ucwords($val['status']),

	    			  $action

	    			);	    			

	    		}

	    		$this->otableOutput($feed_data);

	    	}		

	    	$this->otableNodata();	

		}

		

		public function rptMerchantPayment()

		{

			$and='';  

	    	if (isset($this->data['start_date']) && isset($this->data['end_date']))	{

	    		if (!empty($this->data['start_date']) && !empty($this->data['end_date'])){

	    		$and=" WHERE date_created BETWEEN  '".$this->data['start_date']." 00:00:00' AND 

	    		        '".$this->data['end_date']." 23:59:00'

	    		 ";

	    		}

	    	}

	    	

	    	$order_status_id='';

	    	$or='';

	    	if (isset($this->data['stats_id'])){

		    	if (is_array($this->data['stats_id']) && count($this->data['stats_id'])>=1){

		    		foreach ($this->data['stats_id'] as $stats_id) {		    			

		    			$order_status_id.="'$stats_id',";

		    		}

		    		if ( !empty($order_status_id)){

		    			$order_status_id=substr($order_status_id,0,-1);

		    		}		    	

		    	}	    

	    	}

	    	

	    	if ( !empty($order_status_id)){	    		

	    		if (empty($and)){

	    			$and.= " WHERE status IN ($order_status_id)";

	    		} else $and.= " AND status IN ($order_status_id)";

	    	}	    	    	

	    		    

	    	

	    	$DbExt=new DbExt;

	    	$stmt="SELECT a.*,

	    	(

	    	select title 

	    	from

	    	{{packages}} 

	    	where

	    	package_id=a.package_id

	    	) as package_name,

	    	

	    	(

	    	select restaurant_name

	    	from

	    	{{merchant}}

	    	where

	    	merchant_id=a.merchant_id

	    	) as merchant_name

	    	

	    	 FROM

	    	{{package_trans}} a	    	

	    	$and

	    	";	    	

	    	//dump($stmt);	

	    	$_SESSION['kr_export_stmt']=$stmt;

	    	if ($res=$DbExt->rst($stmt)){	    		

	    		foreach ($res as $val) {	    			

	    			

	    			

	    			$action="<a data-id=\"".$val['id']."\" class=\"edit-payment\" href=\"javascript:\">".Yii::t("default","Edit")."</a>";

	    			//$action.="<a data-id=\"".$val['id']."\" class=\"view-payment\" href=\"javascript:\">View</a>";

	    			

	    			/*$date=Yii::app()->functions->prettyDate($val['date_created'],true);

	    			$date=Yii::app()->functions->translateDate($date);*/

	    			$date=FormatDateTime($val['date_created']);

	    			

	    			if ( $val['payment_type']=="ocr"){

	    				$payment_type= "<a href=\"javascript:;\" class=\"show-cc-details\" data-id=\"$val[mt_id]\">".strtoupper(t($val['payment_type']))."</a>";

	    			} else $payment_type= strtoupper(t($val['payment_type']));

	    			

	    			$feed_data['aaData'][]=array(

	    			  $val['id'],

	    			  $val['merchant_name'],

	    			  $val['package_name'],

	    			  $val['price']>=1?Yii::app()->functions->standardPrettyFormat($val['price']):"",

	    			  $payment_type,

	    			  ucwords($val['status']),

	    			  $date,	    			  

	    			  $action

	    			);

	    		}

	    		$this->otableOutput($feed_data);

	    	}

	    	$this->otableNodata();	

		}

		

		public function rptMerchantPaymentToday()

		{

			$and='';  

			$datenow=date('Y-m-d');

	    	$and="WHERE date_created like '$datenow%' ";

	    	

	    	$DbExt=new DbExt;

	    	$stmt="SELECT a.*,

	    	(

	    	select title 

	    	from

	    	{{packages}} 

	    	where

	    	package_id=a.package_id

	    	) as package_name,

	    	

	    	(

	    	select restaurant_name

	    	from

	    	{{merchant}}

	    	where

	    	merchant_id=a.merchant_id

	    	) as merchant_name

	    	

	    	 FROM

	    	{{package_trans}} a	    	

	    	$and

	    	";	    	

	    	

	    	$_SESSION['kr_export_stmt']=$stmt;

	    	if ($res=$DbExt->rst($stmt)){	    		

	    		foreach ($res as $val) {	    			

	    			$action="<a data-id=\"".$val['id']."\" class=\"edit-payment\" href=\"javascript:\">".Yii::t("default","Edit")."</a>";

	    			//$action.="<a data-id=\"".$val['id']."\" class=\"view-payment\" href=\"javascript:\">View</a>";

	    			

	    			$feed_data['aaData'][]=array(

	    			  $val['id'],

	    			  $val['merchant_name'],

	    			  $val['package_name'],

	    			  $val['price']>=1?Yii::app()->functions->standardPrettyFormat($val['price']):"",

	    			  //strtoupper($val['payment_type']),

	    			  FunctionsV3::prettyPaymentType('package_trans',$val['payment_type'],$val['id']),

	    			  ucwords($val['status']),

	    			  Yii::app()->functions->FormatDateTime($val['date_created'],true),	    			  

	    			  $action

	    			);

	    		}

	    		$this->otableOutput($feed_data);

	    	}

	    	$this->otableNodata();	

		}		

		

		public function editPayment()

		{

  	       $status_list=paymentStatus();

	    	?>

	    	<div class="view-receipt-pop">

	    	 <h3><?php echo Yii::t("default","Change Order Status")?></h3>

	    	 

	    	 <?php if ( $res=Yii::app()->functions->getMerchantPaymentByID($this->data['id']) ):?>

	    	    <form id="frm-pop" class="frm-pop uk-form uk-form-horizontal" method="POST" onsubmit="return false;">

	    	    <?php echo CHtml::hiddenField('action','updatePaymennt')?>

	    	    <?php echo CHtml::hiddenField('id',$this->data['id'])?>

	    	    

		    	 <div class="uk-form-row">

		    	  <label class="uk-form-label"><?php echo Yii::t("default","Status")?></label>

		    	  <?php echo CHtml::dropDownList('status',$res['status'],(array)$status_list,array(

		    	  'class'=>"uk-form-width-large"

		    	  ))?>

		    	 </div>

		    	 

		    	 <div class="action-wrap">

		    	   <?php echo CHtml::submitButton('Submit',

		    	   array('class'=>"uk-button uk-form-width-medium uk-button-success"))?>

		    	 </div>

	    	   </form> 

	    	 <?php else:?>

	    	 <p class="uk-text-danger"><?php echo Yii::t("default","Error: Order not found")?></p>

	    	 <?php endif;?>

	    	</div> <!--view-receipt-pop-->	    					    

			<script type="text/javascript">

			$.validate({ 	

			    form : '#frm-pop',    

			    onError : function() {      

			    },

			    onSuccess : function() {     

			      form_submit('frm-pop');

			      return false;

			    }  

			});		

			</script>

	    	<?php

	    	die();

		}

		

		public function updatePaymennt()

		{			

			if(isset($this->data['status']) && isset($this->data['id'])){

				$params=array('status'=>$this->data['status']);

				if ($this->updateData('{{package_trans}}',$params,'id',$this->data['id'])){

					$this->code=1;

					$this->msg=Yii::t("default","Successfully updated.");

				} else $this->msg=Yii::t("default","ERROR: cannot update records.");

			} else $this->msg=Yii::t("default","ERROR: Missing parameters");		

		}

		

		public function merchantPaymentPaypal()

		{

				

			if (!$merchant=Yii::app()->functions->getMerchantByToken($this->data['internal-token'])){			

				$this->msg=Yii::t("default","ERROR: Cannot get package information");

				return ;

			}	

						

			$paypal_con=Yii::app()->functions->getPaypalConnectionAdmin();   			

            $paypal=new Paypal($paypal_con);

            if ($res_paypal=$paypal->getExpressDetail()){	            	

            	//dump($res_paypal);

            	$paypal->params['PAYERID']=$res_paypal['PAYERID'];

	            $paypal->params['AMT']=$res_paypal['AMT'];

	            $paypal->params['TOKEN']=$res_paypal['TOKEN'];

	            $paypal->params['CURRENCYCODE']=$res_paypal['CURRENCYCODE'];	            	           

	            //dump($params);

	            if ($res=$paypal->expressCheckout()){ 	   

	            	

	            	if (isset($this->data['renew'])) {

	            		

	            		$membership_info=Yii::app()->functions->upgradeMembership($merchant['merchant_id'],$this->data['package_id']);	            		

	            		$params=array(

			             'package_id'=>$this->data['package_id'],

			             'merchant_id'=>$merchant['merchant_id'],

			             'price'=>$res_paypal['AMT'],

			             'payment_type'=>"pyp",

			             'date_created'=>date('c'),

			             'ip_address'=>$_SERVER['REMOTE_ADDR'],

			             'TOKEN'=>$res_paypal['TOKEN'],

			             'TRANSACTIONID'=>$res['TRANSACTIONID'],

			             'TRANSACTIONTYPE'=>$res['TRANSACTIONTYPE'],

			             'PAYMENTSTATUS'=>$res['PAYMENTSTATUS'],

			             'PAYPALFULLRESPONSE'=>json_encode($res),

			             'membership_expired'=>$membership_info['membership_expired']

		                );	 

		                //dump($params);



	                    $params_update=array(

						  'package_id'=>$this->data['package_id'],

						  'package_price'=>$membership_info['package_price'],

						  'membership_expired'=>$membership_info['membership_expired'],

						  'membership_purchase_date'=>date('c'),

						  'status'=>'active'

					 	 );		

					 	 //dump($params_update);

						 $this->updateData("{{merchant}}",$params_update,'merchant_id',$merchant['merchant_id']);

						 	

	            	} else {	            

		            	$params=array(

			             'package_id'=>$merchant['package_id'],

			             'merchant_id'=>$merchant['merchant_id'],

			             'price'=>$res_paypal['AMT'],

			             'payment_type'=>"pyp",

			             'date_created'=>date('c'),

			             'ip_address'=>$_SERVER['REMOTE_ADDR'],

			             'TOKEN'=>$res_paypal['TOKEN'],

			             'TRANSACTIONID'=>$res['TRANSACTIONID'],

			             'TRANSACTIONTYPE'=>$res['TRANSACTIONTYPE'],

			             'PAYMENTSTATUS'=>$res['PAYMENTSTATUS'],

			             'PAYPALFULLRESPONSE'=>json_encode($res)

		               );	      

		               

		               if ( $package=Yii::app()->functions->getPackagesById($merchant['package_id'])){

						   $expiration=$package['expiration'];

	                       $membership_expired = date('Y-m-d', strtotime ("+$expiration days"));							

	                       $params['membership_expired']=$membership_expired;

				       }					

	            	}         

			       

	               $this->insertData("{{package_trans}}",$params);

	               $this->code=1;

	               $this->msg=Yii::t("default","Successful");

	               $this->details=$this->data['internal-token'];

	               

	               $this->updateData("{{merchant}}",

							   array('payment_steps'=>3,

							          'membership_purchase_date'=>date('c')

							   ),'merchant_id',$merchant['merchant_id']);

	               

	            } else $this->msg=$paypal->getError();	

            } else $this->msg=$paypal->getError();	



		}

		

		public function newMerchantRegList()

		{

			

			$datenow=date('Y-m-d');

		    $and="WHERE date_created like '$datenow%' ";

	    	$DbExt=new DbExt;	    		

	    	$stmt="SELECT a.*,

	    	(

	    	select title 

	    	from

	    	{{packages}} 

	    	where

	    	package_id=a.package_id

	    	) as package_name,

	    	

	    	(

	    	select payment_type

	    	from

	    	{{package_trans}}

	    	where

	    	merchant_id=a.merchant_id

	    	ORDER BY id DESC

	    	LIMIT 0,1

	    	) as payment_type,

	    	

	    	(

	    	select id

	    	from

	    	{{package_trans}}

	    	where

	    	merchant_id=a.merchant_id

	    	ORDER BY id DESC

	    	LIMIT 0,1

	    	) as package_trans_id

	    	

	    	 FROM

	    	{{merchant}} a	    	

	    	$and

	    	";	   

	    	//dump($stmt);

	    	if ( $res=$DbExt->rst($stmt)){	    		

	    		foreach ($res as $val) {	    		   

	    		   $action="<a data-id=\"".$val['merchant_id']."\" class=\"edit-merchant-status\" href=\"javascript:\">".Yii::t("default","Edit")."</a>";

	    		   $feed_data['aaData'][]=array(

	    		      $val['merchant_id'],

	    		      stripslashes($val['restaurant_name']),

	    		      $val['package_name'],

	    		      Yii::app()->functions->standardPrettyFormat($val['package_price']),

	    		      //strtoupper($val['payment_type']),

	    		      FunctionsV3::prettyPaymentType('package_trans',$val['payment_type'],$val['package_trans_id']),

	    		      $val['status'],

	    		      //Yii::app()->functions->prettyDate($val['date_created'],true),

	    		      Yii::app()->functions->FormatDateTime($val['date_created'],true),

	    		      $action

	    		   );	

	    		}	    		

	    		$this->otableOutput($feed_data);

	    	}	    	

	    	$this->otableNodata();

		}

		

		public function editMerchantStatus()

		{					

  	        $status_list=clientStatus();

	    	?>

	    	<div class="view-receipt-pop">

	    	 <h3>Change Order Status</h3>

	    	 

	    	 <?php if ( $res=Yii::app()->functions->getMerchant($this->data['id']) ):?>

	    	    <form id="frm-pop" class="frm-pop uk-form uk-form-horizontal" method="POST" onsubmit="return false;">

	    	    <?php echo CHtml::hiddenField('action','updateMerchantStatus')?>

	    	    <?php echo CHtml::hiddenField('id',$this->data['id'])?>

	    	    

		    	 <div class="uk-form-row">

		    	  <label class="uk-form-label"><?php echo Yii::t("default","Status")?></label>

		    	  <?php echo CHtml::dropDownList('status',$res['status'],(array)$status_list,array(

		    	  'class'=>"uk-form-width-large"

		    	  ))?>

		    	 </div>

		    	 

		    	 <div class="action-wrap">

		    	   <?php echo CHtml::submitButton('Submit',

		    	   array('class'=>"uk-button uk-form-width-medium uk-button-success"))?>

		    	 </div>

	    	   </form> 

	    	 <?php else:?>

	    	 <p class="uk-text-danger"><?php echo Yii::t("default","Error: Order not found")?></p>

	    	 <?php endif;?>

	    	</div> <!--view-receipt-pop-->	    					    

			<script type="text/javascript">

			$.validate({ 	

			    form : '#frm-pop',    

			    onError : function() {      

			    },

			    onSuccess : function() {     

			      form_submit('frm-pop');

			      return false;

			    }  

			});		

			</script>

	    	<?php

	    	die();

		}

		

		public function updateMerchantStatus()

		{			

			if (isset($this->data['id'])){

				$params=array('status'=>$this->data['status']);				

				if ($this->updateData("{{merchant}}",$params,'merchant_id',$this->data['id'])){

					$this->code=1;

					$this->msg=Yii::t("default","Status Updated.");

				} else $this->msg=Yii::t("default","Error; cannot update status.");

			} else $this->msg=Yii::t("default","Missing parameters");

		}		

		

		public function sponsoreMerchantAdd()

		{		

			if (isset($this->data['merchant_id'])){

				$params=array(

				  'is_sponsored'=>2,

				  'sponsored_expiration'=>$this->data['expiration'],

				  'date_modified'=>date('c')

				);				

				if ( $this->updateData("{{merchant}}",$params,'merchant_id',$this->data['merchant_id'])){

					$this->code=1;

					$this->msg=Yii::t("default","Successful");

					//$this->details=$this->data['merchant_id'];

				} else $this->msg=Yii::t("default","ERROR: cannot update records.");

			} else $this->msg=Yii::t("default","Missing parameters");

		}	

		

		public function sponsoredMerchantList()

		{

			$slug=websiteUrl().'/admin/'.$this->data['slug'];

			$stmt="SELECT * FROM

			{{merchant}}

			WHERE

			status in ('active')

			AND

			is_sponsored='2'

			ORDER BY restaurant_name ASC

			";

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[merchant_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[merchant_id]\" >".Yii::t("default","Remove")."</a>

    	    		</div>";		   	   

				   /*$date=Yii::app()->functions->prettyDate($val['sponsored_expiration']);					

				   $date=Yii::app()->functions->translateDate($date);*/

				   $date=FormatDateTime($val['sponsored_expiration'],false);

			   	   $feed_data['aaData'][]=array(

			   	      $val['merchant_id'],

			   	      $val['restaurant_name'].$action,

			   	      $date

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function currencyList()

		{

			$slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{currency}}

			ORDER BY date_created DESC

			";

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[currency_code]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[currency_code]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   

					

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date);	*/

				   $date=FormatDateTime($val['date_created']);

			   	   $feed_data['aaData'][]=array(

			   	      $val['currency_code'].$action,

			   	      $val['currency_symbol'],

			   	      $date

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		
		

		public function addCurrency()

		{			

			$Validator=new Validator;

			$req=array(

			  'currency_code'=>Yii::t("default","Currency Code is required"),

			  'currency_symbol'=>Yii::t("default","Currency Symbol is required")

			);		

			$Validator->required($req,$this->data);

			if ($Validator->validate()){

				$params=array(

				  'currency_code'=>$this->data['currency_code'],

				  'currency_symbol'=>$this->data['currency_symbol'],

				  'ip_address'=>$_SERVER['REMOTE_ADDR']

				);

			   if (empty($this->data['id'])){	

			    	if ( $this->insertData("{{currency}}",$params)){

			    		    $this->details=Yii::app()->db->getLastInsertID();

				    		$this->code=1;

				    		$this->msg=Yii::t("default","Successful");				    		

				    	}

				    } else {		    	

				    	unset($params['date_created']);

						$params['date_modified']=date('c');				

						$res = $this->updateData('{{currency}}' , $params ,'currency_code',$this->data['id']);

						if ($res){

							$this->code=1;

			                $this->msg=Yii::t("default",'Currency updated.');  

					} else $this->msg=Yii::t("default","ERROR: cannot update");

			    }	

			} else $this->msg=$Validator->getErrorAsHTML();		

		}

		

		public function CuisineList()

		{

		    $slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{cuisine}}

			ORDER BY sequence DESC

			";

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[cuisine_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[cuisine_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);

				   $date=Yii::app()->functions->translateDate($date);*/

				   $date=FormatDateTime($val['date_created']);

			   	   $feed_data['aaData'][]=array(

			   	      $val['cuisine_id'],

			   	      $val['cuisine_name'].$action,

			   	      $date

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function addCuisine()

		{			

		    $Validator=new Validator;

			$req=array(

			  'cuisine_name'=>Yii::t("default","Name is required")		  

			);		

			$Validator->required($req,$this->data);

			if ($Validator->validate()){

				$params=array(

				  'cuisine_name'=>$this->data['cuisine_name'],

				  'date_created'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR']

				);

				

				

	            if (isset($this->data['cuisine_name_trans'])){				

					if (okToDecode()){

						$params['cuisine_name_trans']=json_encode($this->data['cuisine_name_trans'],

						JSON_UNESCAPED_UNICODE);

					} else $params['cuisine_name_trans']=json_encode($this->data['cuisine_name_trans']);				

				}

				

			   if (empty($this->data['id'])){	

			    	if ( $this->insertData("{{cuisine}}",$params)){

			    		    $this->details=Yii::app()->db->getLastInsertID();

				    		$this->code=1;

				    		$this->msg=Yii::t("default","Successful");				    		

				    	}

				    } else {		    	

				    	unset($params['date_created']);

						$params['date_modified']=date('c');				

						$res = $this->updateData('{{cuisine}}' , $params ,'cuisine_id',$this->data['id']);

						if ($res){

							$this->code=1;

			                $this->msg=Yii::t("default",'Cuisine updated.');  

					} else $this->msg=Yii::t("default","ERROR: cannot update");

			    }	

			} else $this->msg=$Validator->getErrorAsHTML();		

		}	

		

		public function merchantSetReady()

		{

			$mtid=Yii::app()->functions->getMerchantID();

			if (isset($this->data['status'])){

				$params=array(

				  'is_ready'=>$this->data['status'],

				  'date_modified'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR']

				);			



							

				if ( $_SESSION['kr_merchant_user_type']=="merchant_user"){

					$user_info=Yii::app()->functions->getMerchantInfo();					

					$user_id=$user_info[0]->merchant_user_id;					

					if (!$data=Yii::app()->functions->getMerchantUserInfo($user_id)){

						$this->msg=t("Sorry but you dont have permission");

						return ;

					} else $user_access=json_decode($data['user_access'],true);					

					if (!in_array('can_published_merchant',$user_access)){

						$this->msg=t("Sorry but you dont have permission");

						return ;

					}

				}

				

				if ( $this->updateData("{{merchant}}",$params,'merchant_id',$mtid)){

					$this->code=1;

					if ( $this->data['status']==2){

					    $this->msg=Yii::t("default","Successful Merchant is now published.");

					} else $this->msg=Yii::t("default","Successful");

				} else $this->msg=Yii::t("default","ERROR: cannot update status");			

			} else $this->msg=Yii::t("default","Missing parameters");		

		}	

		

		public function merchantStatus()

		{

			$mtid=Yii::app()->functions->getMerchantID();					

			if ( $res=Yii::app()->functions->getMerchant($mtid)){												

				$this->code=1;

				$this->msg=$res['is_ready'];

				$this->details=array(

				 'status'=>$res['status'],				 

				 'display_status'=>strtoupper(Yii::t("default",$res['status'])),

				 'is_commission'=>$res['is_commission']

				);

			} else $this->msg=Yii::t("default","ERROR:");

		}

		

		public function OrderStatusList()

		{

			$this->OrderStatusListMerchant(false);

		}

		

		public function OrderStatusListMerchant($as_merchant=true)

		{

			$mtid=Yii::app()->functions->getMerchantID();

			$where="WHERE merchant_id='$mtid'";

			if ( $as_merchant==FALSE){

				$where='';

			}								

		    $slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{order_status}}

			$where

			ORDER BY description ASC

			";			

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[stats_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[stats_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date);	*/

				   $date=FormatDateTime($val['date_created']);

			   	   $feed_data['aaData'][]=array(

			   	      $val['stats_id'],

			   	      t($val['description']).$action,

			   	      $date

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();			

		}	



		public function addOrderStatus()

		{		   	

			$mtid=Yii::app()->functions->getMerchantID();

		    $Validator=new Validator;

			$req=array(

			  'description'=>Yii::t("default","Name is required")			  

			);		

			$Validator->required($req,$this->data);

			if ($Validator->validate()){

				$params=array(

				  'description'=>$this->data['description'],

				  'date_created'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR'],

				  'merchant_id'=>$mtid

				);

				if (isset($this->data['is_admin'])){

					unset($params['merchant_id']);

				}			

			   if (empty($this->data['id'])){	

			    	if ( $this->insertData("{{order_status}}",$params)){

			    		    $this->details=Yii::app()->db->getLastInsertID();

				    		$this->code=1;

				    		$this->msg=Yii::t("default","Successful");				    		

				    	}

				    } else {		    	

				    	unset($params['date_created']);

						$params['date_modified']=date('c');				

						$res = $this->updateData('{{order_status}}' , $params ,'stats_id',$this->data['id']);

						if ($res){

							$this->code=1;

			                $this->msg=Yii::t("default",'Status updated.');  

					} else $this->msg=Yii::t("default","ERROR: cannot update");

			    }	

			} else $this->msg=$Validator->getErrorAsHTML();				   	

		}

		

		

		public function updateClientProfile()

		{

			

			/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			}  	



			$client_id=Yii::app()->functions->getClientId();			

			if (!is_numeric($client_id)){

				$this->msg=Yii::t("default","ERROR: Your session has expired.");

				return ;

			}		

					

			$func=new FunctionsK();

			if ($func->CheckCustomerMobile($this->data['contact_phone'],$client_id)){

				$this->msg=t("Sorry but your mobile number is already exist in our records");

				return;

			}					

			

			$params=array(

			  'first_name'=>isset($this->data['first_name'])?$this->data['first_name']:'',

			  'last_name'=>isset($this->data['last_name'])?$this->data['last_name']:'',

			  'street'=>isset($this->data['street'])?$this->data['street']:'',

			  'city'=>isset($this->data['city'])?$this->data['city']:'',

			  'state'=>isset($this->data['state'])?$this->data['state']:'',

			  'zipcode'=>isset($this->data['zipcode'])?$this->data['zipcode']:'',

			  'contact_phone'=>isset($this->data['contact_phone'])?$this->data['contact_phone']:'',

			  'date_modified'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR']

			);

			

			

            /** update 2.3*/

	    	if (isset($this->data['custom_field1'])){

	    		$params['custom_field1']=!empty($this->data['custom_field1'])?$this->data['custom_field1']:'';

	    	}

	    	if (isset($this->data['custom_field2'])){

	    		$params['custom_field2']=!empty($this->data['custom_field2'])?$this->data['custom_field2']:'';

	    	}

	    			    				

			if (isset($this->data['password'])){

				if (!empty($this->data['password'])){

					$params['password']=md5($this->data['password']);

				}			

			}					

			

			if (isset($this->data['password'])){

				if (!empty($this->data['password'])){

					if (  $this->data['password']!=$this->data['cpassword'] ){

						$this->msg=t("Confirm password does not match.");

						return ;

					}

				}

			}		

						

			if ($this->updateData("{{client}}",$params,'client_id',$client_id)){

				$this->code=1;

				$this->msg=Yii::t("default","Profile Updated.");

			} else $this->msg=Yii::t("default","ERROR: cannot update profile.");

		}

		

		public function ClientCCList()

		{					   

			$slug=Yii::app()->request->baseUrl."/store";

			$stmt="SELECT * FROM

			{{client_cc}}		

			WHERE

			client_id ='".Yii::app()->functions->getClientId()."'	

			ORDER BY cc_id DESC

			";						

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Cards/Do/Edit/?id=$val[cc_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[cc_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   

			   	   $feed_data['aaData'][]=array(

			   	      $val['card_name'].$action,

			   	      Yii::app()->functions->maskCardnumber($val['credit_card_number']),

			   	      $val['expiration_month']."-".$val['expiration_yr']

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();			

		}			

		

		public function updateCreditCard()

		{

			if (!isset($this->data['cc_id'])){

				$this->msg="ERROR: Missing parameters";

				return ;

			}

			if (!is_numeric($this->data['cc_id'])){

				$this->msg=Yii::t("default","ERROR: Missing parameters");

				return ;

			}						

			$params=array(

			  'card_name'=>isset($this->data['card_name'])?$this->data['card_name']:'',

			  'credit_card_number'=>isset($this->data['credit_card_number'])?$this->data['credit_card_number']:'',

			  'expiration_month'=>isset($this->data['expiration_month'])?$this->data['expiration_month']:'',

			  'expiration_yr'=>isset($this->data['expiration_yr'])?$this->data['expiration_yr']:'',

			  'cvv'=>isset($this->data['cvv'])?$this->data['cvv']:'',

			  'billing_address'=>isset($this->data['billing_address'])?$this->data['billing_address']:'',

			  'date_modified'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR']

			);

			

			if ( $this->updateData("{{client_cc}}",$params,'cc_id',$this->data['cc_id'])){

				$this->code=1;$this->msg=Yii::t("default","Card successfully updated.");

			} else $this->msg=Yii::t("default","ERROR: Cannot update credit card info.");		

			

		}

		

	    public function contactSettings()

	    {	    	

	    	if (!isset($this->data['contact_content'])){

	        	$this->data['contact_content']='';

	        }

	        if (!isset($this->data['contact_map'])){

	        	$this->data['contact_map']='';

	        }

	        if (!isset($this->data['map_latitude'])){

	        	$this->data['map_latitude']='';

	        }

	        if (!isset($this->data['map_longitude'])){

	        	$this->data['map_longitude']='';

	        }

	        if (!isset($this->data['contact_email_receiver'])){

	        	$this->data['contact_email_receiver']='';

	        }

	        if (!isset($this->data['contact_field'])){

	        	$this->data['contact_field']='';

	        }

	    

	        if (is_array($this->data['contact_field']) && count($this->data['contact_field'])>=1){

	        yii::app()->functions->updateOptionAdmin('contact_content',$this->data['contact_content']);

	        yii::app()->functions->updateOptionAdmin('contact_map',$this->data['contact_map']);

	        yii::app()->functions->updateOptionAdmin('map_latitude',$this->data['map_latitude']);

	        yii::app()->functions->updateOptionAdmin('map_longitude',$this->data['map_longitude']);

	        yii::app()->functions->updateOptionAdmin('contact_email_receiver',$this->data['contact_email_receiver']);

	        yii::app()->functions->updateOptionAdmin('contact_field',json_encode($this->data['contact_field']));

	        $this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	        } else $this->msg=Yii::t("default","Contact field must have 1 or more fields");

	    }		

	    

	    public function adminSocialSettings()

	    {	    	

	    	yii::app()->functions->updateOptionAdmin('social_flag',isset($this->data['social_flag'])?$this->data['social_flag']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('fb_flag',isset($this->data['fb_flag'])?$this->data['fb_flag']:"");

	    	yii::app()->functions->updateOptionAdmin('fb_app_id',isset($this->data['fb_app_id'])?$this->data['fb_app_id']:"");

	    	yii::app()->functions->updateOptionAdmin('fb_app_secret',isset($this->data['fb_app_secret'])?$this->data['fb_app_secret']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('admin_fb_page',isset($this->data['admin_fb_page'])?$this->data['admin_fb_page']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('admin_twitter_page',isset($this->data['admin_twitter_page'])?$this->data['admin_twitter_page']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('admin_google_page',isset($this->data['admin_google_page'])?$this->data['admin_google_page']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('admin_merchant_share',isset($this->data['admin_merchant_share'])?$this->data['admin_merchant_share']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('google_client_id',

	    	isset($this->data['google_client_id'])?$this->data['google_client_id']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('google_client_secret',

	    	isset($this->data['google_client_secret'])?$this->data['google_client_secret']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('google_client_redirect_ulr',

	    	isset($this->data['google_client_redirect_ulr'])?$this->data['google_client_redirect_ulr']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('google_login_enabled',

	    	isset($this->data['google_login_enabled'])?$this->data['google_login_enabled']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('default_share_text',

	    	isset($this->data['default_share_text'])?$this->data['default_share_text']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('admin_intagram_page',

	    	isset($this->data['admin_intagram_page'])?$this->data['admin_intagram_page']:"");

	    	

	    	yii::app()->functions->updateOptionAdmin('admin_youtube_url',

	    	isset($this->data['admin_youtube_url'])?$this->data['admin_youtube_url']:"");

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	    }

	    

	    public function contacUsSubmit()

	    {	    	

	    	

	    	/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			}  	



	    	unset($this->data['action']);

	    	foreach ($this->data as $key=>$val) {    		

	    	    $required[$key]=ucwords($key). " ". Yii::t("default","is required");

	    	}    		    	

	    		    	

	    	$validator=new Validator;

	    	$validator->required($required,$this->data);

	    	if ($validator->validate()){    		

	    		$tpl="<p>".Yii::t("default","Hi admin")."</p>";

    		    $tpl.="<p>".Yii::t("default","There is someone fill the contact form")."<p>";

	    		$tpl.="<p>".Yii::t("default","see below information")."<p>";

	    		foreach ($this->data as $key=>$val) {    		

	    			$tpl.=ucwords($key)." : $val<br/>";

	    		}    			    		    		

	    		$subject=Yii::t("default","New Contact Us");	    		

	    		$to=yii::app()->functions->getOptionAdmin('contact_email_receiver');	    			    		

				//$from='no-reply@'.$_SERVER['HTTP_HOST'].".com";					

				//$from='no-reply@'.$_SERVER['HTTP_HOST'];

				$from="";

				

				if (empty($to)){

					$this->msg=Yii::t("default","ERROR: no email to send.");

					return ;

				}	    	

							

				if ( Yii::app()->functions->sendEmail($to,$from,$subject,$tpl) ){

					$this->code=1;    		

	    		    $this->msg=Yii::t("default","Your message was sent successfully. Thanks.");

				} else $this->msg=Yii::t("default","ERROR: Cannot sent email.");	    		

	    	} else $this->msg=$validator->getErrorAsHTML();

	    }

	    

	    public function ratingList()

	    {

			$slug=$_GET['slug'];

			$stmt="SELECT * FROM

			{{rating_meaning}}		

			ORDER BY rating_start ASC						

			";						

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   

			   	   $feed_data['aaData'][]=array(

			   	      $val['rating_start'].$action,

			   	      $val['rating_end'],

			   	      $val['meaning']

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();				    	

	    }

	    

	    public function addRatings()

	    {	    		

		    $Validator=new Validator;

			$req=array(

			  'meaning'=>Yii::t("default","Rating is required")

			);		

			$Validator->required($req,$this->data);

			if ($Validator->validate()){

				$params=array(

				  'rating_start'=>$this->data['rating_start'],

				  'rating_end'=>$this->data['rating_end'],

				  'meaning'=>$this->data['meaning'],

				  'date_created'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR']				  

				);				

			   if (empty($this->data['id'])){	

			    	if ( $this->insertData("{{rating_meaning}}",$params)){

			    		   $this->details=Yii::app()->db->getLastInsertID();

				    		$this->code=1;

				    		$this->msg=Yii::t("default","Successful");				    		

				    	}

				    } else {		    	

				    	unset($params['date_created']);

						$params['date_modified']=date('c');				

						$res = $this->updateData('{{rating_meaning}}' , $params ,'id',$this->data['id']);

						if ($res){

							$this->code=1;

			                $this->msg=Yii::t("default",'Rating updated.');  

					} else $this->msg=Yii::t("default","ERROR: cannot update");

			    }	

			} else $this->msg=$Validator->getErrorAsHTML();		

	    }

	    

	    public function FBRegister()

	    {	    		    	

	        if (isset($this->data['email'])){

	    		$params=array(

	    		  'first_name'=>addslashes($this->data['first_name']),

	    		  'last_name'=>addslashes($this->data['last_name']),

	    		  'email_address'=>addslashes($this->data['email']),

	    		  'social_strategy'=>'fb',

	    		  'password'=>md5(addslashes($this->data['id'])),

	    		  'date_created'=>date('c'),

	    		  'ip_address'=>$_SERVER['REMOTE_ADDR']    		

	    		);    		    			    		

	    		if ($social_info=yii::app()->functions->accountExistSocial($this->data['email'])){      	    			

	    			

	    			$client_id=$social_info[0]['client_id'];

	    			if(empty($social_info[0]['avatar'])){

	    				if($avatar = FunctionsV3::saveFbAvatarPicture($this->data['id'])){

		    				$db=new DbExt();

		    				$db->updateData("{{client}}",array('avatar'=>$avatar),'client_id',$client_id);

	    				}

	    			}	   	    		

	    				

	    		    /*AUTO LOGIN*/	            	    		    

		            $this->data['username']=addslashes($this->data['email']);

		            $this->data['password']=addslashes($social_info[0]['password']);

		            $this->data['password_md5']=addslashes($social_info[0]['password']);

		            $this->clientLogin();

	    	    } else {   	    	    	    

	    	    	

	    	    	 if($avatar = FunctionsV3::saveFbAvatarPicture($this->data['id'])){

	    	    	    $params['avatar']=$avatar;

	    	    	 }

	    	    	 

	    		     $command = Yii::app()->db->createCommand();

					if ($res=$command->insert('{{client}}',$params)){		

						

						/*POINTS PROGRAM*/		

						$client_id=Yii::app()->db->getLastInsertID();	  

						if (FunctionsV3::hasModuleAddon("pointsprogram")){

	    			       PointsProgram::signupReward($client_id);	

						}		            

	    			    				

			            $this->code=1;

			            $this->msg=Yii::t("default",'Information has been saved.');    

			            

			            /*AUTO LOGIN*/	            

			            $this->data['username']=addslashes($this->data['email']);

			            $this->data['password']=addslashes($this->data['id']);

			            $this->clientLogin();

			            			            

			        } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

	    	    }

	    	} else $this->msg=Yii::t("default","Unexpected response. please login again.");

	    }

	    

	    public function forgotPassword()

	    {

	    	

	    	$Validator=new Validator;

			$req=array(

			  'username-email'=>Yii::t("default","Email is required")

			);		

			$Validator->required($req,$this->data);

			if ($Validator->validate()){

				if ( $res=yii::app()->functions->isClientExist($this->data['username-email']) ){					

					$token=md5(date('c'));

					$params=array('lost_password_token'=>$token);					

					if ($this->updateData("{{client}}",$params,'client_id',$res['client_id'])){

						$this->code=1;						

						$this->msg=Yii::t("default","We sent your forgot password link, Please follow that link. Thank You.");

												

						//send email					

						$tpl=EmailTPL::forgotPass($res,$token);

					    //$sender=Yii::app()->functions->getOptionAdmin('website_contact_email');

					    $sender='';

		                $to=$res['email_address'];		                

		                if (!sendEmail($to,$sender,Yii::t("default","Forgot Password"),$tpl)){		    			                	

		                	$this->details="failed";

		                } else $this->details="mail ok";		

						

					} else $this->msg=Yii::t("default","ERROR: Cannot update records");				

				} else $this->msg=Yii::t("default","Sorry but your Email address does not exist in our records.");

			} else $this->msg=$Validator->getErrorAsHTML();

	    }

	    

	    public function changePassword()

	    {	    		    		    	

	    	$Validator=new Validator;

			$req=array(

			  'token'=>Yii::t("default","Token is missing")

			);		

			if ($this->data['password']!=$this->data['confirm_password']){

				$this->msg=Yii::t("default","Confirm password does not match.");

				return ;

			}	    

			$Validator->required($req,$this->data);

			if ($Validator->validate()){

	    		if ( $res=Yii::app()->functions->getLostPassToken($this->data['token'])){	    			

	    			$params=array(

	    			  'password'=>md5($this->data['password']),

	    			  'date_modified'=>date('c'),

	    			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    			  'lost_password_token'=>md5(date('c'))

	    			);	    		

	    			if ( $this->updateData("{{client}}",$params,'client_id',$res['client_id'])){

	    				$this->code=1;

	    				$this->msg=Yii::t("default","Successful. Your password has been changed.");

	    			} else $this->msg=Yii::t("default","ERROR");	    		

	    		} else $this->msg=Yii::t("default","ERROR: Cannot update password");

	    	} else $this->msg=Yii::t("default","Token is missing");

	    }

	    

	    public function adminProfile()

	    {	    

	    	/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			}  	



	    	$params=array(

	    	  'first_name'=>$this->data['first_name'],

	    	  'last_name'=>$this->data['last_name'],

	    	  'user_lang'=>isset($this->data['user_lang'])?$this->data['user_lang']:'',

	    	  'date_modified'=>date('c'),

	    	  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    	  'email_address'=>isset($this->data['email_address'])?$this->data['email_address']:''

	    	);

	    	if (isset($this->data['password'])){

	    		if (!empty($this->data['password'])){

	    			if ($this->data['password']!=$this->data['cpassword']){

	    				$this->msg=Yii::t("default","Confirm password does not match.");

	    			   	return ;

	    			} else {

	    				$params['password']=md5($this->data['password']);

	    			}	    				

	    		}	    	

	    	}

	    	$admin_id=Yii::app()->functions->getAdminId(); 

	    	if ($this->updateData("{{admin_user}}",$params,'admin_id',$admin_id)){

	    		$this->code=1;

	    		$this->msg=Yii::t("default","Profile updated.");

	    	} else $this->msg=Yii::t("default","ERROR Cannot update.");

	    }

	    

	    public function AdminUserList()

	    {

	    	

		    $slug=Yii::app()->request->baseUrl."/admin/".$_GET['slug'];

			$stmt="SELECT * FROM

			{{admin_user}}		

			ORDER BY admin_id ASC						

			";						

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[admin_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[admin_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		  

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date); 	   */

				   $date=FormatDateTime($val['date_created']);

			   	   $feed_data['aaData'][]=array(

			   	      $val['admin_id'],

			   	      $val['username'].$action,

			   	      $val['first_name']." ".$val['last_name'],

			   	      $date

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();			    	

	    }

	    

	    public function addAdminUser()

	    {	    

	    		    	

	       $p = new CHtmlPurifier();

	       $params=array(

	    	  'first_name'=> $p->purify($this->data['first_name']),

	    	  'last_name'=> $p->purify($this->data['last_name']),

	    	  'username'=> $p->purify($this->data['username']),

	    	  'date_created'=>date('c'),

	    	  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    	  'email_address'=>isset($this->data['email_address'])?$p->purify($this->data['email_address']):'',

	    	  'user_access'=>isset($this->data['user_access'])?json_encode($this->data['user_access']):''

	    	);

	    	

	    	/*dump($params);

	    	die();*/

	    		    	

	    	/*csrf validation*/

	    	if(!isset($_POST[Yii::app()->request->csrfTokenName])){

	    		$this->msg=t("The CSRF token is missing");

	    		return ;

	    	}	    

	    	if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

	    		$this->msg=t("The CSRF token could not be verified");

	    		return ;

	    	}  	

	    		    	

	    	if (isset($this->data['password'])){

	    		if (!empty($this->data['password'])){

	    			if ($this->data['password']!=$this->data['cpassword']){

	    				$this->msg=Yii::t("default","Confirm password does not match.");

	    			   	return ;

	    			} else {

	    				$params['password']=md5($this->data['password']);

	    			}	    				

	    		}	    	

	    	}

	    		    	

	    	if (empty($this->data['id'])){	

		    	if ( $this->insertData("{{admin_user}}",$params)){

		    		   $this->details=Yii::app()->db->getLastInsertID();

			    		$this->code=1;

			    		$this->msg=Yii::t("default","Successful");			    		

			    	}

			    } else {		    	

			    	unset($params['date_created']);

					$params['date_modified']=date('c');				

					$res = $this->updateData('{{admin_user}}' , $params ,'admin_id',$this->data['id']);

					if ($res){

						$this->code=1;

		                $this->msg=Yii::t("default",'User updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

		    }	

	    }

	    

	    public function customPageList()

	    {

	        $slug=Yii::app()->request->baseUrl."/admin/".$_GET['slug'];

			$stmt="SELECT * FROM

			{{custom_page}}		

			ORDER BY slug_name ASC						

			";						

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	  

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date);	 */

				   $date=FormatDateTime($val['date_created']);

			   	   $feed_data['aaData'][]=array(

			   	      $val['id'],

			   	      $val['slug_name'],

			   	      $val['page_name'].$action,

			   	      //Yii::app()->functions->limitDescription($val['content']),

			   	      '<div class="limit-content">'.$val['content'].'</div>',

			   	      $date."<br/>".$val['status']

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();			    		    		

	    }

	    

	    public function addCustomPage()

	    {	    	

            $params=array(

	    	  'page_name'=>$this->data['page_name'],

	    	  'content'=>$this->data['content'],	    	  

	    	  'date_created'=>date('c'),

	    	  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    	  'status'=>$this->data['status'],

	    	  'icons'=>$this->data['icons'],

	    	  'open_new_tab'=>isset($this->data['open_new_tab'])?$this->data['open_new_tab']:1

	    	);

	    	

	    	if (isset($this->data['seo_title'])){

	    		$params['seo_title']=$this->data['seo_title'];

	    	}

	    	if (isset($this->data['meta_description'])){

	    		$params['meta_description']=$this->data['meta_description'];

	    	}

	    	if (isset($this->data['meta_keywords'])){

	    		$params['meta_keywords']=$this->data['meta_keywords'];

	    	}	    		  

	    	    		    	

	    	if (empty($this->data['id'])){	

	    		$params['slug_name']=strtolower(Yii::app()->functions->customPageCreateSlug($this->data['page_name']));	    	

		    	if ( $this->insertData("{{custom_page}}",$params)){

		    		$this->details=Yii::app()->db->getLastInsertID();

			    		$this->code=1;

			    		$this->msg=Yii::t("default","Successful");			    		

			    	}

			    } else {					    	

			    	unset($params['date_created']);

					$params['date_modified']=date('c');				

					$res = $this->updateData('{{custom_page}}' , $params ,'id',$this->data['id']);

					if ($res){

						$this->code=1;

		                $this->msg=Yii::t("default",'Page updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

		    }		    	

	    }	

	    

	    public function assignCustomPage()

	    {	    	

	    	if (is_array($this->data['id']) && count($this->data['id'])>=1)

	    	{

	    		$x=1;

	    		foreach ($this->data['id'] as $key=>$id) {

	    			$params=array(

	    			  'assign_to'=>$this->data['assign_to'][$key],

	    			  'date_modified'=>date('c'),

	    			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    			  'sequence'=>$x

	    			);	    			

	    			$this->updateData("{{custom_page}}",$params,'id',$id);

	    			$x++;

	    		}

	    	}

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Successfully updated.");

	    }

	    

	    public function merchantForgotPass()

	    {	    	

	    	if ( isset($this->data['email_address'])){

	    		if ($res=yii::app()->functions->isMerchantExist($this->data['email_address']) ){	    			

	    			$params=array('lost_password_code'=> yii::app()->functions->generateCode());	    			

	    			if ( $this->updateData("{{merchant}}",$params,'merchant_id',$res[0]['merchant_id'])){

	    				$this->code=1;

	    				$this->msg=Yii::t("default","We have sent verification code in your email.");

	    				//send email	    				

	    				$tpl=EmailTPL::merchantForgotPass($res[0],$params['lost_password_code']);

	    				$sender=Yii::app()->functions->getOptionAdmin('website_contact_email');

		                $to=$res[0]['contact_email'];

		                if (!sendEmail($to,$sender,t("Merchant Forgot Password"),$tpl)){		    	

		                	$this->details="failed";

		                } else $this->details="ok mail";

	    				

	    			} else $this->msg=Yii::t("default","ERROR: Cannot update.");	    		

	    		} else $this->msg=Yii::t("default","Sorry but we cannot find your email address.");

	    	} else $this->msg=Yii::t("default","Email address is required");	    

	    }

	    

	    public function merchantChangePassword()

	    {	    	

	    	if (isset($this->data['lost_password_code'])){

	    		$stmt="SELECT * FROM

	    		{{merchant}}

	    		WHERE 

	    		lost_password_code='".$this->data['lost_password_code']."'

	    		AND

	    		contact_email='".$this->data['email']."'

	    		LIMIT 0,1

	    		";

	    		if ($res=$this->rst($stmt)){

	    			$merchant_id=$res[0]['merchant_id'];

	    			$params=array( 

	    			   'password'=>md5($this->data['new_password']),

	    			   'date_modified'=>date('c'),

	    			   'ip_address'=>$_SERVER['REMOTE_ADDR']

	    			); 

	    			if ($this->updateData("{{merchant}}",$params,'merchant_id',$merchant_id)){

	    				$this->msg=Yii::t("default","Change password succesful");

	    				$this->code=1;

	    			} else $this->msg=Yii::t("default","ERROR: cannot update records.");	    		

	    		} else $this->msg=Yii::t("default","Sorry but your verification code is incorrect");

	    	} else $this->msg=Yii::t("default","ERROR: missing parameters");    

	    }

	    

	    public function merchantResumeSignup()

	    {	    	

	    	$stmt="SELECT * FROM

	    	{{merchant}}

	    	WHERE

	    	contact_email='".$this->data['email_address']."'

	    	LIMIT 0,1

	    	";

	    	if ($res=$this->rst($stmt)){	    		

	    		if ($res[0]['status']=="active"){	    			

	    			$this->msg=Yii::t("default","Merchant is alread active.");

	    		} else {

	    		    $steps=$res[0]['payment_steps']+1;	    		    

	    		    //$url=Yii::app()->getBaseUrl(true)."/store/merchantsignup/Do/step$steps/token/".$res[0]['activation_token'];

	    		    

	    		    $url=Yii::app()->createUrl('/store/merchantsignup',array(

	    		       'Do'=>'step'.$steps,

	    		       'token'=>$res[0]['activation_token']

	    		    ));

	    		    

	    		    $this->code=1;

	    		    $this->msg=Yii::t("default","Successful");

	    		    $this->details=$url;

	    		}  

	    	} else $this->msg=Yii::t("default","Sorry but we cannot find your email address.");

	    }

	    

	    public function resendActivationCode()

	    {	    

	    	$stmt="SELECT * FROM

	    	{{merchant}}

	    	WHERE

	    	activation_token='".$this->data['token']."'

	    	LIMIT 0,1

	    	";

	    	if ($res=$this->rst($stmt)){	    		

	    		// send email activation key

		    	$tpl=EmailTPL::merchantActivationCode($res[0]);

	            $sender=Yii::app()->functions->getOptionAdmin('website_contact_email');

	            $to=$res[0]['contact_email'];	            

	            if (!sendEmail($to,$sender,Yii::t("default","Merchant Registration"),$tpl)){		    	

	            	$this->details="failed";

	            } else $this->details="ok mail";

	            $this->code=1;

	            $this->msg=Yii::t("default","We have sent the activation code to your email address.");

	    	} else $this->msg=Yii::t("default","Token is invalid.");

	    }

	    

		public function languageList()

		{

			

			$country_list=Yii::app()->functions->CountryList();

			$slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{languages}}

			ORDER BY date_created DESC

			";

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[lang_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[lang_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   

					

$country=array_key_exists($val['country_code'],(array)$country_list)?$country_list[$val['country_code']]:$val['country_code'];

					

                   /*$date=Yii::app()->functions->prettyDate($val['date_created']);  

                   $date=Yii::app()->functions->translateDate($date);*/

                   $date=FormatDateTime($val['date_created']);



			   	   $feed_data['aaData'][]=array(

			   	      $country.$action,

			   	      $val['language_code'],

			   	      $date."<div>".Yii::t("default","status")."</div>"

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

	    

		public function addLanguage()

		{			

			$this->data['id']=isset($this->data['id'])?$this->data['id']:'';

			$Validator=new Validator;

			$req=array(

			  'country_code'=>Yii::t("default","Country is required"),

			  'language_code'=>Yii::t("default","Language is required"),

			  'language_file'=>Yii::t("default","please upload language file"),

			);							

			

			if (!preg_match("/.php/i",$this->data['language_file'])) {

				$Validator->msg[]="Invalid language file must be a php file.";

			}

			

			$Validator->required($req,$this->data);

			if ($Validator->validate()){

				$params=array(

				  'country_code'=>$this->data['country_code'],

				  'language_code'=>$this->data['language_code'],

				  'date_created'=>date('c'),

				  'status'=>$this->data['status'],

				  'ip_address'=>$_SERVER['REMOTE_ADDR'],

				  'source_text'=>$this->data['language_file']

				);				

				if (is_numeric($this->data['id'])){

					unset($params['date_created']);

					$params['last_updated']=date('c');					

					if ($this->updateData("{{languages}}",$params,'lang_id',$this->data['id'])){

						$this->code=1;

						$this->msg=Yii::t("default","Successfully updared");

					} else $this->msg=Yii::t("default","ERROR: cannot update");				

				} else {

					if ( $this->insertData("{{languages}}",$params)){

						$this->details=Yii::app()->db->getLastInsertID();

			    		$this->code=1;

			    		$this->msg=Yii::t("default","Successful");			    		

			    	} else $this->msg=Yii::t("default","Failed. cannot insert records");

				}

			} else {

				$this->msg=$this->parseValidatorError($Validator->getError());

			}		

		}

		

		private function parseValidatorError($error='')

		{

			$error_string='';

			if (is_array($error) && count($error)>=1){

				foreach ($error as $val) {

					$error_string.="$val<br/>";

				}

			}

			return $error_string;		

		}	

		

		public function languageSettings()

		{

					

			yii::app()->functions->updateOptionAdmin('show_language',isset($this->data['show_language'])?$this->data['show_language']:"");			

			yii::app()->functions->updateOptionAdmin('default_language',isset($this->data['default_language'])?$this->data['default_language']:"");

			

			yii::app()->functions->updateOptionAdmin('set_lang_id',isset($this->data['set_lang_id'])?json_encode($this->data['set_lang_id']):"");

			

			yii::app()->functions->updateOptionAdmin('enabled_multiple_translation',

			isset($this->data['enabled_multiple_translation'])?$this->data['enabled_multiple_translation']:"");

	    	

			yii::app()->functions->updateOptionAdmin('default_language_backend',

			isset($this->data['default_language_backend'])?$this->data['default_language_backend']:"");

			

			yii::app()->functions->updateOptionAdmin('show_language_backend',

			isset($this->data['show_language_backend'])?$this->data['show_language_backend']:"");

			

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}		

		

		public function SeoSettings()

		{

			yii::app()->functions->updateOptionAdmin('seo_home',

			isset($this->data['seo_home'])?$this->data['seo_home']:"");	

			

			yii::app()->functions->updateOptionAdmin('seo_home_meta',

			isset($this->data['seo_home_meta'])?$this->data['seo_home_meta']:"");	

			

			yii::app()->functions->updateOptionAdmin('seo_home_keywords',

			isset($this->data['seo_home_keywords'])?$this->data['seo_home_keywords']:"");	

			

			yii::app()->functions->updateOptionAdmin('seo_search',

			isset($this->data['seo_search'])?$this->data['seo_search']:"");	

			yii::app()->functions->updateOptionAdmin('seo_search_meta',

			isset($this->data['seo_search_meta'])?$this->data['seo_search_meta']:"");	

			yii::app()->functions->updateOptionAdmin('seo_search_keywords',

			isset($this->data['seo_search_keywords'])?$this->data['seo_search_keywords']:"");	

			

			yii::app()->functions->updateOptionAdmin('seo_menu',

			isset($this->data['seo_menu'])?$this->data['seo_menu']:"");	

			yii::app()->functions->updateOptionAdmin('seo_menu_meta',

			isset($this->data['seo_menu_meta'])?$this->data['seo_menu_meta']:"");	

			yii::app()->functions->updateOptionAdmin('seo_menu_keywords',

			isset($this->data['seo_menu_keywords'])?$this->data['seo_menu_keywords']:"");	

			

			yii::app()->functions->updateOptionAdmin('seo_checkout',

			isset($this->data['seo_checkout'])?$this->data['seo_checkout']:"");	

			yii::app()->functions->updateOptionAdmin('seo_checkout_meta',

			isset($this->data['seo_checkout_meta'])?$this->data['seo_checkout_meta']:"");	

			yii::app()->functions->updateOptionAdmin('seo_checkout_keywords',

			isset($this->data['seo_checkout_keywords'])?$this->data['seo_checkout_keywords']:"");	

			

			yii::app()->functions->updateOptionAdmin('seo_contact',

			isset($this->data['seo_contact'])?$this->data['seo_contact']:"");	

			yii::app()->functions->updateOptionAdmin('seo_contact_meta',

			isset($this->data['seo_contact_meta'])?$this->data['seo_contact_meta']:"");	

			yii::app()->functions->updateOptionAdmin('seo_contact_keywords',

			isset($this->data['seo_contact_keywords'])?$this->data['seo_contact_keywords']:"");	

			

			yii::app()->functions->updateOptionAdmin('seo_merchantsignup',

			isset($this->data['seo_merchantsignup'])?$this->data['seo_merchantsignup']:"");	

			yii::app()->functions->updateOptionAdmin('seo_merchantsignup_meta',

			isset($this->data['seo_merchantsignup_meta'])?$this->data['seo_merchantsignup_meta']:"");	

			yii::app()->functions->updateOptionAdmin('seo_merchantsignup_keywords',

			isset($this->data['seo_merchantsignup_keywords'])?$this->data['seo_merchantsignup_keywords']:"");	

								

			$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}

		

		public function receiptSettings()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();

			

			Yii::app()->functions->updateOption("receipt_sender",

	    	isset($this->data['receipt_sender'])?$this->data['receipt_sender']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("receipt_subject",

	    	isset($this->data['receipt_subject'])?$this->data['receipt_subject']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("receipt_content",

	    	isset($this->data['receipt_content'])?$this->data['receipt_content']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_receipt_subject",

	    	isset($this->data['merchant_receipt_subject'])?$this->data['merchant_receipt_subject']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_receipt_content",

	    	isset($this->data['merchant_receipt_content'])?$this->data['merchant_receipt_content']:''

	    	,$merchant_id);

								

			$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}

		

		public function removeMerchantBg()

		{

			if (Yii::app()->functions->isMerchantLogin()){	    		

		    	$merchant_id=Yii::app()->functions->getMerchantID();	    

		    	Yii::app()->functions->updateOption("merchant_photo_bg","",$merchant_id);

		    	$this->code=1;$this->msg=Yii::t("default","Merchant background has been removed");

	    	} else $this->msg=Yii::t("default","ERROR: Your session has expired.");			

		}

		

		public function stripeSettings()

		{		

			$merchant_id=Yii::app()->functions->getMerchantID();

						

			Yii::app()->functions->updateOption("stripe_enabled",

	    	isset($this->data['stripe_enabled'])?$this->data['stripe_enabled']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("stripe_mode",

	    	isset($this->data['stripe_mode'])?$this->data['stripe_mode']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("stripe_enabled",

	    	isset($this->data['stripe_enabled'])?$this->data['stripe_enabled']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sanbox_stripe_secret_key",

	    	isset($this->data['sanbox_stripe_secret_key'])?$this->data['sanbox_stripe_secret_key']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sandbox_stripe_pub_key",

	    	isset($this->data['sandbox_stripe_pub_key'])?$this->data['sandbox_stripe_pub_key']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("live_stripe_secret_key",

	    	isset($this->data['live_stripe_secret_key'])?$this->data['live_stripe_secret_key']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("live_stripe_pub_key",

	    	isset($this->data['live_stripe_pub_key'])?$this->data['live_stripe_pub_key']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}

		

		public function adminStripeSettings()

		{		

					

			Yii::app()->functions->updateOptionAdmin("admin_stripe_enabled",

	    	isset($this->data['admin_stripe_enabled'])?$this->data['admin_stripe_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_stripe_mode",

	    	isset($this->data['admin_stripe_mode'])?$this->data['admin_stripe_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_stripe_enabled",

	    	isset($this->data['admin_stripe_enabled'])?$this->data['admin_stripe_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sanbox_stripe_secret_key",

	    	isset($this->data['admin_sanbox_stripe_secret_key'])?$this->data['admin_sanbox_stripe_secret_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sandbox_stripe_pub_key",

	    	isset($this->data['admin_sandbox_stripe_pub_key'])?$this->data['admin_sandbox_stripe_pub_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_live_stripe_secret_key",

	    	isset($this->data['admin_live_stripe_secret_key'])?$this->data['admin_live_stripe_secret_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_live_stripe_pub_key",

	    	isset($this->data['admin_live_stripe_pub_key'])?$this->data['admin_live_stripe_pub_key']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}		

		

		public function smsSettings()

		{						

			Yii::app()->functions->updateOptionAdmin("sms_sender_id",

	    	isset($this->data['sms_sender_id'])?$this->data['sms_sender_id']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("sms_account_id",

	    	isset($this->data['sms_account_id'])?$this->data['sms_account_id']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("sms_token",

	    	isset($this->data['sms_token'])?$this->data['sms_token']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("mechant_sms_enabled",

	    	isset($this->data['mechant_sms_enabled'])?$this->data['mechant_sms_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("mechant_sms_enabled",

	    	isset($this->data['mechant_sms_enabled'])?$this->data['mechant_sms_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("sms_provider",

	    	isset($this->data['sms_provider'])?$this->data['sms_provider']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("nexmo_sender_id",

	    	isset($this->data['nexmo_sender_id'])?$this->data['nexmo_sender_id']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("nexmo_key",

	    	isset($this->data['nexmo_key'])?$this->data['nexmo_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("nexmo_secret",

	    	isset($this->data['nexmo_secret'])?$this->data['nexmo_secret']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("nexmo_use_curl",

	    	isset($this->data['nexmo_use_curl'])?$this->data['nexmo_use_curl']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("privatesms_username",

	    	isset($this->data['privatesms_username'])?$this->data['privatesms_username']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("privatesms_password",

	    	isset($this->data['privatesms_password'])?$this->data['privatesms_password']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("privatesms_sender",

	    	isset($this->data['privatesms_sender'])?$this->data['privatesms_sender']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("clickatel_user",

	    	isset($this->data['clickatel_user'])?$this->data['clickatel_user']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("clickatel_password",

	    	isset($this->data['clickatel_password'])?$this->data['clickatel_password']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("clickatel_api_id",

	    	isset($this->data['clickatel_api_id'])?$this->data['clickatel_api_id']:'');

	    		    	

	    	Yii::app()->functions->updateOptionAdmin("clickatel_use_curl",

	    	isset($this->data['clickatel_use_curl'])?$this->data['clickatel_use_curl']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("nexmo_use_unicode",

	    	isset($this->data['nexmo_use_unicode'])?$this->data['nexmo_use_unicode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("clickatel_use_unicode",

	    	isset($this->data['clickatel_use_unicode'])?$this->data['clickatel_use_unicode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("clickatel_sender",

	    	isset($this->data['clickatel_sender'])?$this->data['clickatel_sender']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("mechant_sms_purchase_disabled",

	    	isset($this->data['mechant_sms_purchase_disabled'])?$this->data['mechant_sms_purchase_disabled']:'');

	    	

	    	/*Yii::app()->functions->updateOptionAdmin("mechant_sms_use_admin_credits",

	    	isset($this->data['mechant_sms_use_admin_credits'])?$this->data['mechant_sms_use_admin_credits']:'');*/

	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("bhashsms_user",

	    	isset($this->data['bhashsms_user'])?$this->data['bhashsms_user']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("bhashsms_pass",

	    	isset($this->data['bhashsms_pass'])?$this->data['bhashsms_pass']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("bhashsms_senderid",

	    	isset($this->data['bhashsms_senderid'])?$this->data['bhashsms_senderid']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("bhashsms_smstype",

	    	isset($this->data['bhashsms_smstype'])?$this->data['bhashsms_smstype']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("bhashsms_priority",

	    	isset($this->data['bhashsms_priority'])?$this->data['bhashsms_priority']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("bhashsms_use_curl",

	    	isset($this->data['bhashsms_use_curl'])?$this->data['bhashsms_use_curl']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("smsglobal_senderid",

	    	isset($this->data['smsglobal_senderid'])?$this->data['smsglobal_senderid']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("smsglobal_username",

	    	isset($this->data['smsglobal_username'])?$this->data['smsglobal_username']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("smsglobal_password",

	    	isset($this->data['smsglobal_password'])?$this->data['smsglobal_password']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("swift_accountkey",

	    	isset($this->data['swift_accountkey'])?$this->data['swift_accountkey']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("swift_usecurl",

	    	isset($this->data['swift_usecurl'])?$this->data['swift_usecurl']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("solutionsinfini_apikey",

	    	isset($this->data['solutionsinfini_apikey'])?$this->data['solutionsinfini_apikey']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("solutionsinfini_usecurl",

	    	isset($this->data['solutionsinfini_usecurl'])?$this->data['solutionsinfini_usecurl']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("solutionsinfini_useunicode",

	    	isset($this->data['solutionsinfini_useunicode'])?$this->data['solutionsinfini_useunicode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("solutionsinfini_sender",

	    	isset($this->data['solutionsinfini_sender'])?$this->data['solutionsinfini_sender']:'');

	    	

	    	

	    	Yii::app()->functions->updateOptionAdmin("plivo_auth_id",

	    	isset($this->data['plivo_auth_id'])?$this->data['plivo_auth_id']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("plivo_auth_token",

	    	isset($this->data['plivo_auth_token'])?$this->data['plivo_auth_token']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("plivo_sender_number",

	    	isset($this->data['plivo_sender_number'])?$this->data['plivo_sender_number']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("msg91_authkey",

	    	isset($this->data['msg91_authkey'])?$this->data['msg91_authkey']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("msg91_senderid",

	    	isset($this->data['msg91_senderid'])?$this->data['msg91_senderid']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("msg91_unicode",

	    	isset($this->data['msg91_unicode'])?$this->data['msg91_unicode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("msg91_route",

	    	isset($this->data['msg91_route'])?$this->data['msg91_route']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}	

		

		public function SMSpackagesList()

		{

					

			$slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{sms_package}}			

			ORDER BY sms_package_id DESC

			";

			if ( $res=$this->rst($stmt)){

				foreach ($res as $val) {	

					/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));  				

					$date=Yii::app()->functions->translateDate($date);*/

					

					$date=FormatDateTime($val['date_created']);

					

					$action="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[sms_package_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[sms_package_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

					$val['title']=ucwords($val['title']);

					$feed_data['aaData'][]=array(

					  $val['sms_package_id'],

					  $val['title'].$action,

					  Yii::app()->functions->limitDescription($val['description']),

					  Yii::app()->functions->standardPrettyFormat($val['price']),

					  Yii::app()->functions->standardPrettyFormat($val['promo_price']),

					  $val['sms_limit'],

					  $date."<div>".Yii::t("default",$val['status'])."</div>"					  

					);

				}

				$this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function smsPackageAdd()

		{		   

	       $params=array(

	         'title'=>$this->data['title'],

	         'description'=>$this->data['description'],

	         'price'=>$this->data['price'],

	         'promo_price'=>$this->data['promo_price'],

	         'sms_limit'=>$this->data['sms_limit'],

	         'status'=>$this->data['status'],

	         'date_created'=>date('c'),

	         'ip_address'=>$_SERVER['REMOTE_ADDR']

	       );	       

	       if (empty($this->data['id'])){	

		    	if ( $this->insertData("{{sms_package}}",$params)){

		    		$this->details=Yii::app()->db->getLastInsertID();

		    		$this->code=1;

		    		$this->msg=Yii::t("default","Successful");		    		

		    	}

		    } else {		    	

		    	unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $this->updateData('{{sms_package}}' , $params ,'sms_package_id',$this->data['id']);

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Package updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

		    }	

			

		}

		

	    public function getNewOrder()

	    {

	    	$list='';

	    	if ($res=Yii::app()->functions->newOrderList(1)){	    			    	

	    		$this->code=1;

	    		$this->msg=count($res);	    		

	    		//$this->details=$list;

	    		$order_list='';

	    		foreach ($res as $val) {	    			

	    			$order_list.="<div class=\"new-order-link\">";

	    			$order_list.="<a class=\"view-receipt\" data-id=\"$val[order_id]\" 

	    			href=\"javascript:;\">".t("Click here to view")." ". t("Reference #") .":". $val['order_id'] . "</a>";	    			

	    			$order_list.="<div>";

	    		}	    		

	    		$this->details=$order_list;

	    	} else $this->msg=Yii::t("default","No results");	    

	    }			

	    

	    public function initSelectPaymentProvider()

	    {	    		    	

	    	if (!isset($this->data['sms_package_id'])){

	    		$this->msg=Yii::t("default","Please select package");

	    		return ;

	    	}	    

	    	if (!isset($this->data['payment_opt'])){

	    		$this->msg=Yii::t("default","Please select Payment gateway.");

	    		return ;

	    	}	    

	    	

	    	$action='';

	    	$params='';

	    	switch ($this->data['payment_opt']) {

	    		case "pyp":

	    			$action="paypalInit";

	    			break;

	    	   case "ccr":

	    			$action="creditCardInit";

	    			break;

	    		case "stp":

	    			$action="stripeInit";

	    			break;	

	    		case "mcd":

	    			$action="mercadopagoInit";

	    			break;		

	    		case "pyl":

	    			$action="paylineinit";	    			

	    			break;			

	    		case "ide":

	    			$action="sisowinit";	    			

	    			break;					    		

	    		default:

	    			$action=$this->data['payment_opt']."init";	  

	    			break;

	    	}	    	

	    	$params="type=purchaseSMScredit&package_id=".$this->data['sms_package_id'];

	    	

	    	

	    	if ( $info=Yii::app()->functions->getSMSPackagesById($this->data['sms_package_id']) ){

	    		

	    		$price=$info['price'];

	    	    if ( $info['promo_price']>0){

                     $price=$info['promo_price'];

	    		}	    	

	    		if ($price<=0){

	    			

	    			$params=array(

	    			  'merchant_id'=>Yii::app()->functions->getMerchantID(),

	    			  'sms_package_id'=>$this->data['sms_package_id'],	    			  

	    			  'package_price'=>$price,

	    			  'sms_limit'=>$info['sms_limit'],

	    			  'date_created'=>date('c'),

	    			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    			  'status'=>"paid"

	    			);	    	    				    			

	    			if ( $this->insertData("{{sms_package_trans}}",$params)){

	    				$params="id=".Yii::app()->db->getLastInsertID();

	    			    $action="smsReceipt";	    			    

	    			} else {

	    				$this->msg=Yii::t("default","ERROR: Cannot insert record.");	

	    				return ;

	    			}

	    		}	    	

	    	}

	    	

	    	$this->code=1;

	    	$this->msg=t("Please wait while we redirect you");

	    	$this->details=Yii::app()->request->baseUrl."/".$this->data['controller']."/$action/?".$params;

	    }	

	    

	    public function payCC()

	    {	    		    

	    	if (!isset($this->data['cc_id'])){

	    		$this->msg=Yii::t("default","Please select credit card.");

	    		return ;

	    	}

	    	

	    	if (!isset($this->data['type'])){

	    		$this->msg=Yii::t("default","Payment type is required");

	    		return ;

	    	}	    

	    	

	    	if ($this->data['type']=="purchaseSMScredit"){

	    		$package_id=isset($this->data['package_id'])?$this->data['package_id']:'';	    		

	    		if ( $info=Yii::app()->functions->getSMSPackagesById($package_id) ){

	    			$price=$info['price'];

	    			if ( $info['promo_price']>0){

	    				$price=$info['promo_price'];

	    			}	    		

	    			$payment_code=Yii::app()->functions->paymentCode("creditcard");	

	    			$params=array(

	    			  'merchant_id'=>Yii::app()->functions->getMerchantID(),

	    			  'sms_package_id'=>$this->data['package_id'],

	    			  'payment_type'=>$payment_code,

	    			  'package_price'=>$price,

	    			  'sms_limit'=>$info['sms_limit'],

	    			  'date_created'=>date('c'),

	    			  'ip_address'=>$_SERVER['REMOTE_ADDR']

	    			);	    	    			

	    			if ( $this->insertData("{{sms_package_trans}}",$params)){

	    				$this->details=Yii::app()->db->getLastInsertID();

	    				$this->code=1;

	    				$this->msg=Yii::t("default","Successful");	    				

	    			} else $this->msg=Yii::t("default","ERROR: Cannot insert record.");	

	    		} else $this->msg=Yii::t("default","Package information not found.");

	    	} else $this->msg=Yii::t("default","Payment type is required");

	    	

	    }

	    

	    public function PayPaypal()

	    {

	    	

	    	if (!isset($this->data['type'])){

	    		$this->msg=Yii::t("default","Payment type is required");

	    		return ;

	    	}	    

	    	$package_id=isset($this->data['package_id'])?$this->data['package_id']:'';	    	

	    	

	    	if ($this->data['type']=="purchaseSMScredit"){

	    		$paypal_con=Yii::app()->functions->getPaypalConnectionAdmin();   			

                $paypal=new Paypal($paypal_con);

                

                if ($res_paypal=$paypal->getExpressDetail()){	            	

	            	

	            	$paypal->params['PAYERID']=$res_paypal['PAYERID'];

		            $paypal->params['AMT']=$res_paypal['AMT'];

		            $paypal->params['TOKEN']=$res_paypal['TOKEN'];

		            $paypal->params['CURRENCYCODE']=$res_paypal['CURRENCYCODE'];	            	           

		            

		            if ($res=$paypal->expressCheckout()){ 

		            	

		            	$info=Yii::app()->functions->getSMSPackagesById($package_id);

		            	

		            	$payment_code=Yii::app()->functions->paymentCode("paypal");

		                $params=array(

	    			      'merchant_id'=>Yii::app()->functions->getMerchantID(),

		    			  'sms_package_id'=>$package_id,

		    			  'payment_type'=>$payment_code,

		    			  'package_price'=>$res_paypal['AMT'],

		    			  'sms_limit'=>$info['sms_limit'],

		    			  'date_created'=>date('c'),

		    			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

		    			  'payment_gateway_response'=>json_encode($res),

		    			  'status'=>"paid"

		    			);	 

		    			

		    			if ( $this->insertData("{{sms_package_trans}}",$params)){

		    				$this->details=Yii::app()->request->baseUrl."/merchant/smsReceipt/id/".Yii::app()->db->getLastInsertID();

		    				$this->code=1;

		    				$this->msg=Yii::t("default","Successful");		    				

		    			} else $this->msg=Yii::t("default","ERROR: Cannot insert record.");	

			               

		            } else $this->msg=$paypal->getError();	

	            } else $this->msg=$paypal->getError();	           

                

	    	} /*end purchaseSMS*/    

	    		

	    }	

	    

	    public function SMSAlertSettings()

	    {

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    	

	    	Yii::app()->functions->updateOption("sms_enabled_alert",

	    	isset($this->data['sms_enabled_alert'])?$this->data['sms_enabled_alert']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sms_notify_number",

	    	isset($this->data['sms_notify_number'])?$this->data['sms_notify_number']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sms_alert_message",

	    	isset($this->data['sms_alert_message'])?$this->data['sms_alert_message']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sms_alert_customer",

	    	isset($this->data['sms_alert_customer'])?$this->data['sms_alert_customer']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("sms_alert_change_status",

	    	isset($this->data['sms_alert_change_status'])?$this->data['sms_alert_change_status']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

	    }

	    

	    public function SMSCreateBroadcast()

	    {	    	

	    	$available_credit=Yii::app()->functions->getMerchantSMSCredit(Yii::app()->functions->getMerchantID());	    	

	    	

	    	

	    	if ( $this->data['send_to']==1){	    		

	    		if ( $available_credit<$this->data['total_customer']){

	    			$this->msg=Yii::t("default","Sorry but your SMS Credits is low. Please purchase SMS credit to continue send SMS. Thank you");

	    			return ;

	    		}

	    		

	    		if ( $this->data['total_customer']<=0){

	    			$this->msg=Yii::t("default","No client found");

	    		    return ;	

	    		}	    	

	    		

	    	} elseif ($this->data['send_to']==2){

	    		if ( $available_credit<$this->data['total_customer_by_merchant']){

	    			$this->msg=Yii::t("default","Sorry but your SMS Credits is low. Please purchase SMS credit to continue send SMS. Thank you");

	    			return ;

	    		}

	    		

	    		if ( $this->data['total_customer_by_merchant']<=0){

	    			$this->msg=Yii::t("default","No client found");

	    		    return ;	

	    		}	    	

	    		

	    	} else {

	    		$list_mobile_number=isset($this->data['list_mobile_number'])?explode(",",$this->data['list_mobile_number']):0;

	    		if ( $available_credit<count($list_mobile_number)){

	    			$this->msg=Yii::t("default","Sorry but your SMS Credits is low. Please purchase SMS credit to continue send SMS. Thank you");

	    			return ;

	    		}

	    			    	

	    		if (empty($available_credit)){

	    			$this->msg=Yii::t("default","Mobile number is invalid");

	    		    return ;	

	    		}	    	

	    		

	    	}	    

	    	

	    		    	

	    	$params=array(

	    	  'send_to'=>$this->data['send_to'],

	    	  'list_mobile_number'=>$this->data['list_mobile_number'],

	    	  'sms_alert_message'=>$this->data['sms_alert_message'],

	    	  'date_created'=>date('c'),

	    	  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    	  'merchant_id'=>Yii::app()->functions->getMerchantID()

	    	);

	    	if ( $this->insertData("{{sms_broadcast}}",$params)){

	    		$this->code=1;

	    		$this->msg=Yii::t('default',"SMS Broadcast saved");

	    	} else $this->msg=Yii::t("default",'ERROR. cannot insert data.');	    

	    }

	    

	    public function smsBroadcastList()

	    {

	    	$send_status=Yii::app()->functions->SMSsendStatus();	    	

	    	$slug=$this->data['slug'];

	        $stmt="

			SELECT * FROM

			{{sms_broadcast}}

			WHERE			

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY broadcast_id  DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	     	    		

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[cook_id]\" class=\"chk_child\" >";   		

    	    		/*$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[cook_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[cook_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";*/

    	    		$link=Yii::app()->request->baseUrl."/merchant/smsBroadcast/Do/view/bid/".$val['broadcast_id'];

    	    		$view="<a href=\"$link\" >View</a>";

    	    		

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));  

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		

    	    		$status='';

    	    		if ( $val['status'] =="process"){

    	    			$status="<p class=\"uk-badge uk-badge-success\">".$val['status']."</p>";

    	    		} else $status="<p class=\"uk-badge uk-badge-danger\">".$val['status']."</p>";

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,

    	    		  $val['broadcast_id'],

    	    		  array_key_exists($val['send_to'],$send_status)?$send_status[$val['send_to']]:'',

    	    		  $val['sms_alert_message'],

    	    		  $status,

    	    		  $view,

    	    		  $date

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

	    }

	    

	    public function smsBroadcastListDetails()

	    {	    		    	

	    	$slug=$this->data['slug'];

	        $stmt="

			SELECT * FROM

			{{sms_broadcast_details}}

			WHERE						

			broadcast_id=".Yii::app()->db->quoteValue($this->data['bid'])."

			ORDER BY id  ASC

			";	        

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	     	    		

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[cook_id]\" class=\"chk_child\" >";     	    		

    	    		$date=date('M d,Y G:i:s',strtotime($val['date_created']));  

    	    		$date_process=$val['date_executed']=="0000-00-00 00:00:00"?"":date('M d,Y G:i:s',strtotime($val['date_executed']));  

    	    		$status='';

    	    		if ( $val['status'] =="process"){

    	    			$status="<p class=\"uk-badge uk-badge-success\">".$val['status']."</p>";

    	    		} else $status="<p class=\"uk-badge uk-badge-danger\">".$val['status']."</p>";

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,

    	    		  $val['id'],$val['client_name'],$val['contact_phone'],

    	    		  $val['sms_message'],$status,$date,$date_process

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

	    }

	    

		public function adminMercadoSettings()

		{											    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mercado_enabled",

	    	isset($this->data['admin_mercado_enabled'])?$this->data['admin_mercado_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mercado_mode",

	    	isset($this->data['admin_mercado_mode'])?$this->data['admin_mercado_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mercado_id",

	    	isset($this->data['admin_mercado_id'])?$this->data['admin_mercado_id']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mercado_key",

	    	isset($this->data['admin_mercado_key'])?$this->data['admin_mercado_key']:'');

	    		    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}			    

		

		public function merchantMercadoSettings()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();

			

			Yii::app()->functions->updateOption("merchant_mercado_enabled",

	    	isset($this->data['merchant_mercado_enabled'])?$this->data['merchant_mercado_enabled']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mercado_mode",

	    	isset($this->data['merchant_mercado_mode'])?$this->data['merchant_mercado_mode']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mercado_id",

	    	isset($this->data['merchant_mercado_id'])?$this->data['merchant_mercado_id']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mercado_key",

	    	isset($this->data['merchant_mercado_key'])?$this->data['merchant_mercado_key']:'',$merchant_id);

	    		    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");

		}	

		

		public function getLimitSellStatus()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();			

			if ( !Yii::app()->functions->validateSellLimit($merchant_id)){

				$link="<a href=\"".Yii::app()->request->baseUrl."/merchant/MerchantStatus/"."\">".Yii::t("default","click here to upgrade")."</a>";

                $this->msg=Yii::t("default","You have reach the maximum limit of selling item. Please upgrade your membership.")." $link";

			} else $this->code=1;

		}

		

		public function MerchantUserList()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();

		    $slug=$this->data['slug'];

	        $stmt="

			SELECT * FROM

			{{merchant_user}}

			WHERE						

			merchant_id=".Yii::app()->db->quoteValue($merchant_id)."

			ORDER BY merchant_user_id DESC

			";	        

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	     	    		

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[merchant_user_id]\" class=\"chk_child\" >";     	    		

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));      	    		

$last_login=$val['last_login']=="0000-00-00 00:00:00"?"":date('M d,Y G:i:s',strtotime($val['last_login']));  */

    	    		

    	    		$last_login=FormatDateTime($val['last_login']);

    	    		

    	    		$status='';

    	    		if ( $val['status'] =="active"){

    	    			$status="<p class=\"uk-badge uk-badge-success\">".$val['status']."</p>";

    	    		} else $status="<p class=\"uk-badge uk-badge-danger\">".$val['status']."</p>";

    	    		

    	    		$action="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[merchant_user_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[merchant_user_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		

    	    		$last_login=Yii::app()->functions->translateDate($last_login);

    	    		$feed_data['aaData'][]=array(

    	    		  $chk,

    	    		  $val['first_name']." ".$val['last_name'].$action,

    	    		  $val['status'],

    	    		  $last_login,$val['ip_address']

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

		}

		

		public function addMerchantUser()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();		    

		    $params=array(

		      'merchant_id'=>$merchant_id,

		      'first_name'=>$this->data['first_name'],

		      'last_name'=>$this->data['last_name'],

		      'username'=>$this->data['username'],

		      //'password'=>md5($this->data['password']),

		      'user_access'=>json_encode($this->data['access']),

		      'date_created'=>date('c'),

		      'status'=>$this->data['status'],

		      'ip_address'=>$_SERVER['REMOTE_ADDR'],

		      'contact_email'=>$this->data['contact_email']

		    );	

		    		    

		    if (isset($this->data['password'])){

		    	if ( !empty($this->data['password'])){

		    	    $params['password']=md5($this->data['password']);

		    	}

		    }		

		    

		    /*dump($params);

		    die();

		    */

		    $FunctionsK=new FunctionsK;

		    if (empty($this->data['id'])){		

		    	if (empty($params['password'])){

		    		$this->msg="Password is required.";

		    		return ;

		    	}		   

		    			    	

		    	if ( $err_msg=$FunctionsK->validateMerchantUserAccount($this->data['username'],

		    	     $this->data['contact_email'])){

		    		 $this->msg=$err_msg;

		    		 return ;

		    	}		    		    	

		    	

		    	if ( !yii::app()->functions->validateMerchantUSername($this->data['username'])){	    	

			    	if ( $this->insertData("{{merchant_user}}",$params)){

			    		$this->details=Yii::app()->db->getLastInsertID();

			    		$this->code=1;

			    		$this->msg=Yii::t("default","Successful");			    		

			    	}

		    	} else $this->msg="Sorry your username is already exist. Please choose another username.";

		    } else {		    	

		    	unset($params['date_created']);

				$params['date_modified']=date('c');								

				

				if (!empty($params['username'])){

					if ( Yii::app()->functions->validateMerchantUser($params['username'],$merchant_id) ){

						$this->msg=Yii::t("default","Merchant Username is already been taken");

						return ;

					}

				}		    

								

				if ( $err_msg=$FunctionsK->validateMerchantUserAccount($this->data['username'],

		    	     $this->data['contact_email'],$this->data['id'])){

		    		 $this->msg=$err_msg;

		    		 return ;

		    	}

		    												

				$res = $this->updateData('{{merchant_user}}' , $params ,'merchant_user_id',$this->data['id']);

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Merchant User updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

		    }			    

		}

		

		public function VoucherList()

		{

			$slug=$this->data['slug'];

			

			$merchant_id=Yii::app()->functions->getMerchantID();		    

		    $stmt="

			SELECT a.*,

			 (

			 select count(*) as total

			 from

			 {{voucher_list}}

			 where

			 voucher_id=a.voucher_id

			 and

			 client_id>=1

			 ) as total_used

			 FROM

			{{voucher}} a

			WHERE

			merchant_id=".Yii::app()->db->quoteValue($merchant_id)."

			ORDER BY voucher_id DESC

			";	   		    		    	

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	        	        	        	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	    	    		

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[voucher_id]\" class=\"chk_child\" >";   		

    	    		$action="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[voucher_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[voucher_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		

    	    		if ($val['voucher_type']=="percentage"){

    	    			$amt=$val['amount']. " %";

    	    		} else $amt=$val['amount'];    		

    	    		

    	    		/*$date=date('D m,Y G:i:s',strtotime($val['date_created']));

    	    		$date=Yii::app()->functions->translateDate($date);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $val['voucher_id'],

    	    		  $val['voucher_name'].$action,

    	    		  "<a class=\"view_vouchers\" data-id=\"".$val['voucher_id']."\" href=\"javascript:;\">".$val['number_of_voucher']."</a>",

    	    		  $val['voucher_type'],

    	    		  $amt,    	    		  

    	    		  $val['number_of_voucher']." / ".$val['total_used'],

    	    		  $date."<div>".Yii::t("default",$val['status'])."</div>"

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();	

		}

		

		public function addVoucher()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();		    

		    $db_ext=new DbExt;

			$voucher_code='';

						

			$validator=new Validator;

	    	$req=array(

	    	  'voucher_name'=>Yii::t("default","Voucher name is required"),

	    	  'amount'=>Yii::t("default","Amount is required"),

	    	  'number_of_voucher'=>Yii::t("default","Number of voucher is required"),

	    	  'status'=>Yii::t("default","Status is required")	    	  

	    	);	    	

	    	if ( !empty($this->data['id'])){

	    		unset($req['number_of_voucher']);

	    	}	    

	    	$validator->required($req,$this->data);

	    	if ($validator->validate()){

	    		$params=array(

	    		  'voucher_name'=>$this->data['voucher_name'],

	    		  'number_of_voucher'=>$this->data['number_of_voucher'],

	    		  'amount'=>$this->data['amount'],

	    		  'status'=>$this->data['status'],

	    		  'date_created'=>date('c'),

	    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    		  'voucher_type'=>$this->data['voucher_type'],

	    		  'merchant_id'=>$merchant_id

	    		);

	    		

	    		if ( !empty($this->data['id'])){

	    			unset($params['number_of_voucher']);

	    			unset($params['date_created']);

	    			$params['date_modified']=date('c');	    				    		

	    			$res =$db_ext->updateData("{{voucher}}",$params,'voucher_id',$this->data['id']);

					if ($res){

						$this->code=1;

		                $this->msg=Yii::t("default",'Voucher updated.');  

					} else $this->msg=Yii::t("default","ERROR: cannot updated.");

	    			

	    		} else {	    			    		

		    		$db_ext->insertData("{{voucher}}",$params);

		    		$voucher_id=Yii::app()->db->getLastInsertID();		    

		    		for ($i = 1; $i <= $this->data['number_of_voucher']; $i++) {                     

	                     //$voucher_code=$i.date('YmdGis');

	                     //$voucher_code=generateCouponCode(3).date("YmdHis");                 

	                     $voucher_code=generateCouponCode(3).date("Ymd").$voucher_id.$i;

	                     $params_voucher=array(

	                       'voucher_id'=>$voucher_id,

	                       'voucher_code'=>$voucher_code                       

	                     );

	                     $db_ext->insertData("{{voucher_list}}",$params_voucher);

	                }

	                $this->code=1;

	                $this->msg=Yii::t("default","Voucher successfully generated");

	    		}

	    	} else $this->msg=$validator->getErrorAsHTML();

		}

		

		public function voucherdetails()

		{

			require_once 'voucherdetails.php';

		}

		

		public function applyVoucher()

		{			

			//dump($this->data);

			

			/*POINTS PROGRAM*/			

			/*check if already applied a point redeem*/

			if (FunctionsV3::hasModuleAddon("pointsprogram")){

				if (isset($_SESSION['pts_redeem_amt']) && $_SESSION['pts_redeem_amt']>0.01){

					$this->msg=t("Sorry but you cannot apply voucher when you have already redeem a points");

					return ;

				}

			}

			

			if ( !Yii::app()->functions->isClientLogin()){

				$this->msg=t("You must be login to use the voucher");

				return ;

			}

			

			$_SESSION['voucher_code']='';				

			if (isset($this->data['voucher_code'])){

				if ( $res=Yii::app()->functions->getVoucherCodeNew($this->data['voucher_code'],$this->data['merchant_id']) ){

					$res['voucher_code']=$res['voucher_name'];

					

					/*check if voucher code can be used only once*/

					if ( $res['used_once']==2){

						if ( $res['number_used']>0){

							$this->msg=t("Sorry this voucher code has already been used");

							return ;

						}

					}

					

					if ( !empty($res['expiration'])){						

						$expiration=$res['expiration'];

						$now=date('Y-m-d');						

						$date_diff=date_diff(date_create($now),date_create($expiration));						

						if (is_object($date_diff)){

							if ( $date_diff->invert==1){

								if ( $date_diff->d>0){

									$this->msg=t("Voucher code has expired");

									return ;

								}

							}

						}

					}

										

					if ( $res['found']<=0){

						$this->code=1;

						$this->msg="OK";

					    $_SESSION['voucher_code']=$res;				

					} else $this->msg=Yii::t("default","Sorry but you have already use this voucher code");

					

				} else {

					 if ( $res=Yii::app()->functions->getVoucherCodeAdmin($this->data['voucher_code'])){					 	

					 	$res['voucher_code']=$res['voucher_name'];

					 	

					 	//dump($res);

					 	if ( !empty($res['expiration'])){						

							$expiration=$res['expiration'];

							$now=date('Y-m-d');						

							$date_diff=date_diff(date_create($now),date_create($expiration));						

							if (is_object($date_diff)){

								if ( $date_diff->invert==1){

									if ( $date_diff->d>0){

										$this->msg=t("Voucher code has expired");

										return ;

									}

								}

							}

						}

						

						/*check if voucher code can be used only once*/

						if ( $res['used_once']==2){

							if ( $res['number_used']>0){

								$this->msg=t("Sorry this voucher code has already been used");

								return ;

							}

						}

												

						if (!empty($res['joining_merchant'])){							

							$joining_merchant=json_decode($res['joining_merchant']);							

							if (in_array($this->data['merchant_id'],(array)$joining_merchant)){								

							} else {

								$this->msg=t("Sorry this voucher code cannot be used on this merchant");

								return ;

							}

						} else {

							/*$this->msg=t("Sorry this voucher code cannot be used on this merchant");

							return ;*/

						}					 

					 	

						if ( $res['found']<=0){

							$this->code=1;

							$this->msg="OK";

						    $_SESSION['voucher_code']=$res;				

						} else $this->msg=Yii::t("default","Sorry but you have already use this voucher code");

						

					 } else $this->msg=Yii::t("default","Voucher code not found");					 

				}

			} else $this->msg=Yii::t("default","Missing parameters");

		}

		

		public function removeVoucher()

		{

			$this->code=1;

			$_SESSION['voucher_code']='';

		}	

		

		public function geoReverse()

		{			

			$url="http://".GOOGLE_MAP_URL."/maps/api/geocode/json?latlng=".$this->data['lat'].",".$this->data['lng']."&&sensor=false";

			$resp=@file_get_contents($url);

			if ($resp){

				$resp=json_decode($resp,true);				

				$this->code=1;

				$this->msg="OK";

				$this->details=$resp['results'][0]['address_components'][0]['short_name'];

			} else $this->msg=Yii::t("default","Failed response from google API");

		}	

		

		public function customerReviews()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();		    

			$slug=$this->data['slug'];

			$stmt="SELECT a.*,

			(

			select concat(first_name,' ',last_name)

			from {{client}}

			where

			client_id=a.client_id

			) client_name

			 FROM

			{{review}} a

			WHERE

			merchant_id='$merchant_id'

			ORDER BY id DESC

			";						

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	  

					

					if ( $this->data['currentController']=="admin"){						

					} else {					

						if ( Yii::app()->functions->getOptionAdmin('merchant_can_edit_reviews')=="yes"){

							$action='';

						}

					}

					

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date); */

				   $date=FormatDateTime($val['date_created']);

				   

			   	   $feed_data['aaData'][]=array(

			   	      ucwords($val['client_name']).$action,

			   	      $val['review'],

			   	      /*$val['order_id'],*/

			   	      $val['rating'],

			   	      $date."<br/><div class=\"uk-badge $class\">".strtoupper(Yii::t("default",$val['status']))."</div>"

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();	

		}

		

		public function UpdateCustomerReviews()

		{

			$db_ext=new DbExt;			

			if (isset($this->data['id'])){

				$params=array(

				  'review'=>$this->data['review'],

				  'status'=>$this->data['status'],

				  'ip_address'=>$_SERVER['REMOTE_ADDR']

				);

				if ($db_ext->updateData("{{review}}",$params,'id',$this->data['id'])){

					$this->code=1;

					$this->msg=Yii::t("default","Successful");

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else $this->msg="";		

		}

		

		public function enterAddress()

		{

		   require_once "enter-address.php";

		}

		public function enterMetro()

		{

			require_once "enter-metro.php";

		}


		/**
		 * update kr_search_address,client_location
		 */
		public function setAddress()

		{
			if(isset($this->data['address_book_id'])){
				$address_book=Yii::app()->functions->getAddressBookByID($this->data['address_book_id']);
				$_SESSION['kr_search_address'] = $address_book['street'];
				$_SESSION['use_new_address']=false;

			} else if (isset($this->data['client_address'])) {
				$_SESSION['kr_search_address']=$this->data['client_address'];
				//show custom address and hide address book
				$_SESSION['use_new_address']=true;
			}

			if (isset($_SESSION['kr_search_address'])){

				if ($lat_res=Yii::app()->functions->geodecodeAddress($_SESSION['kr_search_address'])){

					$merchant_id=$_SESSION['kr_merchant_id'];
					$mt_delivery_miles=Yii::app()->functions->getOption("merchant_delivery_miles",$merchant_id);
					$merchant_info=FunctionsV3::getMerchantById($merchant_id);
					$distance_type=FunctionsV3::getMerchantDistanceType($merchant_id);
					$distance_type_raw = $distance_type=="M"?"miles":"kilometers";
					$distance=FunctionsV3::getDistanceBetweenPlot(
						$lat_res['lat'],
						$lat_res['long'],
						$merchant_info['latitude'],$merchant_info['lontitude'],$distance_type
					);

					$merchant_delivery_distance=getOption($merchant_id,'merchant_delivery_miles');
					if ( $distance>$merchant_delivery_distance) {
						if ($distance_type_raw == "ft" || $distance_type_raw == "meter" || $distance_type_raw == "mt") {
							;
						}
						$this->msg=Yii::t("default","Sorry but this merchant delivers only with in");
						$this->msg=t("Sorry but this merchant delivers only with in ").$mt_delivery_miles." $distance_type_raw";
					} else {
						$_SESSION['client_location']=array(

							'lat'=>$lat_res['lat'],

							'long'=>$lat_res['long']

						);

						$this->code=1;$this->msg=Yii::t("default","Successful");
					}


		    	} else $this->msg=Yii::t("default","Invalid adress");


			} else $this->msg=Yii::t("default","Address is required");		

		}


		/**
		 * update client_location,kr_search_address
		 */
		public function setMetro()

		{

			if (isset($this->data['client_metro'])){

				$_SESSION['kr_search_address']=$this->data['client_metro'];

				//get metro station location
				$json = file_get_contents(ROOTPATH."/assets/resources/metro-stops.json");
				$stations = json_decode($json);
				$lat_res['lat']=NULL;
				$lat_res['long']=NULL;
				$lat_res['lines']= array();

				foreach ($stations as $station) {
					if (strtolower($station->name) == strtolower($this->data['client_metro'])) {
						$lat_res['lat']=$station->latitude;
						$lat_res['long']=$station->longitude;

						foreach ($station->lines as $lineInfo) {
							array_push($lat_res['lines'], $lineInfo->line);
						}
						break;
					}
				}

				if (isset($lat_res['lat'],$lat_res['long'])){

					$merchant_id=$_SESSION['kr_merchant_id'];
					$mt_delivery_miles=Yii::app()->functions->getOption("merchant_delivery_miles",$merchant_id);
					$merchant_info=FunctionsV3::getMerchantById($merchant_id);
					$distance_type=FunctionsV3::getMerchantDistanceType($merchant_id);
					$distance_type_raw = $distance_type=="M"?"miles":"kilometers";
					$distance=FunctionsV3::getDistanceBetweenPlot(
						$lat_res['lat'],
						$lat_res['long'],
						$merchant_info['latitude'],$merchant_info['lontitude'],$distance_type
					);

					$merchant_delivery_distance=getOption($merchant_id,'merchant_delivery_miles');
					if ( $distance>$merchant_delivery_distance) {
						if ($distance_type_raw == "ft" || $distance_type_raw == "meter" || $distance_type_raw == "mt") {
							;
						}
						$this->msg=Yii::t("default","Sorry but this merchant delivers only with in");
						$this->msg=t("Sorry but this merchant delivers only with in ").$mt_delivery_miles." $distance_type_raw";
					} else {

						//update session info

						$_SESSION['client_location']=array(

							'lat'=>$lat_res['lat'],

							'long'=>$lat_res['long'],

							'lines'=>$lat_res['lines']
						);


						$this->code=1;$this->msg=Yii::t("default","Successful");
					}

				} else $this->msg=Yii::t("default","Invalid metro name");


			} else $this->msg=Yii::t("default","Metro name is required");

		}

		public function smsTransactionList()

		{

		    $slug=$this->data['slug'];

			$stmt="SELECT a.*,

			(

			select restaurant_name

			from

			{{merchant}} 

			where

			merchant_id=a.merchant_id

			) merchant_name,

			

			(

			select title

			from

			{{sms_package}}

			where

			sms_package_id=a.sms_package_id

			) sms_package_name

			

			 FROM

			{{sms_package_trans}} a

			ORDER BY id DESC

			";

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	

					$class='';

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);

				   $date=Yii::app()->functions->translateDate($date);   */

				   $date=FormatDateTime($val['date_created']);

			   	   $feed_data['aaData'][]=array(

			   	      $val['id'],

			   	      stripslashes($val['merchant_name']).$action,

			   	      $val['sms_package_name'],

			   	      standardPrettyFormat($val['package_price']),

			   	      $val['sms_limit'],

			   	      FunctionsV3::prettyPaymentType('sms_package_trans',$val['payment_type'],$val['id']),

			   	      $date."<br/><div class=\"uk-badge $class\">".strtoupper(Yii::t("default",$val['status']))."</div>"

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function updateSMSTransaction()

		{						

			if (empty($this->data['id'])){

				if ( $res=Yii::app()->functions->getSMSPackagesById($this->data['sms_package_id'])){

					if ( $res['promo_price']>=1){

						$package_price=$res['promo_price'];

					} else $package_price=$res['price'];

				}

				$params=array(

				  'merchant_id'=>$this->data['merchant_id'],

				  'sms_package_id'=>$this->data['sms_package_id'],

				  'package_price'=>$package_price,

				  'sms_limit'=>$this->data['sms_limit'],

				  'status'=>$this->data['status'],

				  'date_created'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR'],

				  'payment_type'=>"manual"

				);				

				if ( $this->insertData("{{sms_package_trans}}",$params)){

					$this->details=Yii::app()->db->getLastInsertID();					

					$this->code=1;

					$this->msg=t("Successful");

				} else $this->msg=t("ERROR. cannot insert data.");

			} else {		

				$params=array( 

				  'sms_limit'=>$this->data['sms_limit'],

				  'status'=>$this->data['status'],

				  'ip_address'=>$_SERVER['REMOTE_ADDR']

				);

				if ( $this->updateData("{{sms_package_trans}}",$params,'id',$this->data['id']) ){

					$this->code=1;

					$this->msg=Yii::t("default","Successful");

				} else $this->msg=Yii::t("default","ERROR: cannot update");		

			}

		}	

		

		public function merchantPaylineSettings()

		{			

			$merchant_id=Yii::app()->functions->getMerchantID();

						

			Yii::app()->functions->updateOption("merchant_payline_enabled",

	    	isset($this->data['merchant_payline_enabled'])?$this->data['merchant_payline_enabled']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_payline_mode",

	    	isset($this->data['merchant_payline_mode'])?$this->data['merchant_payline_mode']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_payline_api",

	    	isset($this->data['merchant_payline_api'])?$this->data['merchant_payline_api']:'',$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Successful");

		}

		

		public function adminPaylineSettings()

		{								

			Yii::app()->functions->updateOptionAdmin("admin_payline_enabled",

	    	isset($this->data['admin_payline_enabled'])?$this->data['admin_payline_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_payline_mode",

	    	isset($this->data['admin_payline_mode'])?$this->data['admin_payline_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_payline_api",

	    	isset($this->data['admin_payline_api'])?$this->data['admin_payline_api']:'');

	    		    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Successful");

		}

		

		public function adminSisowSettings()

		{			

			Yii::app()->functions->updateOptionAdmin("admin_sisow_enabled",

	    	isset($this->data['admin_sisow_enabled'])?$this->data['admin_sisow_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sisow_mode",

	    	isset($this->data['admin_sisow_mode'])?$this->data['admin_sisow_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sanbox_sisow_secret_key",

	    	isset($this->data['admin_sanbox_sisow_secret_key'])?$this->data['admin_sanbox_sisow_secret_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sandbox_sisow_pub_key",

	    	isset($this->data['admin_sandbox_sisow_pub_key'])?$this->data['admin_sandbox_sisow_pub_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sandbox_sisow_shopid",

	    	isset($this->data['admin_sandbox_sisow_shopid'])?$this->data['admin_sandbox_sisow_shopid']:'');

	    		    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");	    		

		}	

		

		public function merchantSisowSettings()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();

			

			Yii::app()->functions->updateOption("merchant_sisow_enabled",

	    	isset($this->data['merchant_sisow_enabled'])?$this->data['merchant_sisow_enabled']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sisow_mode",

	    	isset($this->data['merchant_sisow_mode'])?$this->data['merchant_sisow_mode']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sanbox_sisow_secret_key",

	    	isset($this->data['merchant_sanbox_sisow_secret_key'])?$this->data['merchant_sanbox_sisow_secret_key']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_sisow_pub_key",

	    	isset($this->data['merchant_sandbox_sisow_pub_key'])?$this->data['merchant_sandbox_sisow_pub_key']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_sisow_shopid",

	    	isset($this->data['merchant_sandbox_sisow_shopid'])?$this->data['merchant_sandbox_sisow_shopid']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");	    		

		}

		

		public function adminPayUMoney()

		{	

			Yii::app()->functions->updateOptionAdmin("admin_payu_enabled",

	    	isset($this->data['admin_payu_enabled'])?$this->data['admin_payu_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_payu_mode",

	    	isset($this->data['admin_payu_mode'])?$this->data['admin_payu_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_payu_key",

	    	isset($this->data['admin_payu_key'])?$this->data['admin_payu_key']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_payu_salt",

	    	isset($this->data['admin_payu_salt'])?$this->data['admin_payu_salt']:'');

	    		    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");	    		

		}

		

		public function merchantPayUMoney()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();

		    Yii::app()->functions->updateOption("merchant_payu_enabled",

	    	isset($this->data['merchant_payu_enabled'])?$this->data['merchant_payu_enabled']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_payu_mode",

	    	isset($this->data['merchant_payu_mode'])?$this->data['merchant_payu_mode']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_payu_key",

	    	isset($this->data['merchant_payu_key'])?$this->data['merchant_payu_key']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_payu_salt",

	    	isset($this->data['merchant_payu_salt'])?$this->data['merchant_payu_salt']:'',$merchant_id);

	    		    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");	    		

		}	

		

		public function getGoogleCordinateStatus()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();			

			$merchant_latitude=Yii::app()->functions->getOption("merchant_latitude",$merchant_id);

            $merchant_longtitude=Yii::app()->functions->getOption("merchant_longtitude",$merchant_id);            

            

            if (empty($merchant_longtitude) || empty($merchant_latitude)){

            	$this->msg=Yii::t("default","Your merchant might not be searchable fixed this by adding coordinates on google map under merchant information");

            } else $this->code=1;

		}	

		

		public function bookATable()

		{

									

			$merchant_id=isset($this->data['merchant-id'])?$this->data['merchant-id']:'';			

						

			$full_booking_time=$this->data['date_booking']." ".$this->data['booking_time'];

			$full_booking_day=strtolower(date("D",strtotime($full_booking_time)));			

			$booking_time=date('h:i A',strtotime($full_booking_time));			

								

			if ( !Yii::app()->functions->isMerchantOpenTimes($merchant_id,$full_booking_day,$booking_time)){

				$this->msg=t("Sorry but we are closed on"." ".date("F,d Y h:ia",strtotime($full_booking_time))).

				"<br/>".t("Please check merchant opening hours");

			    return ;

			}					

					

			$now=isset($this->data['date_booking'])?$this->data['date_booking']:'';			

			$merchant_close_msg_holiday='';

		    $is_holiday=false;

		    if ( $m_holiday=Yii::app()->functions->getMerchantHoliday($merchant_id)){

	      	    if (in_array($now,(array)$m_holiday)){

	      	   	    $is_holiday=true;

	      	    }

		    }

		    if ( $is_holiday==true){

		    	$merchant_close_msg_holiday=!empty($merchant_close_msg_holiday)?$merchant_close_msg_holiday:t("Sorry but we are on holiday on")." ".date("F d Y",strtotime($now));

		    	$this->msg=$merchant_close_msg_holiday;

		    	return ;

		    }		    

		    		    

		    $fully_booked_msg=Yii::app()->functions->getOption("fully_booked_msg",$merchant_id);

		    if (!Yii::app()->functions->bookedAvailable($merchant_id)){

		    	if (!empty($fully_booked_msg)){

		    		$this->msg=t($fully_booked_msg);

		    	} else $this->msg=t("Sorry we are fully booked for that day");			 	

			 	return ;

			}

						

			$db_ext=new DbExt;					

			$params=array(

			  'merchant_id'=>isset($this->data['merchant-id'])?$this->data['merchant-id']:'',

			  'number_guest'=>isset($this->data['number_guest'])?$this->data['number_guest']:'',

			  'date_booking'=>isset($this->data['date_booking'])?$this->data['date_booking']:'',

			  'booking_time'=>isset($this->data['booking_time'])?$this->data['booking_time']:'',

			  'booking_name'=>isset($this->data['booking_name'])?$this->data['booking_name']:'',

			  'email'=>isset($this->data['email'])?$this->data['email']:'',

			  'mobile'=>isset($this->data['mobile'])?$this->data['mobile']:'',

			  'booking_notes'=>isset($this->data['booking_notes'])?$this->data['booking_notes']:'',

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR']

			);			

			

			if ( Yii::app()->functions->isClientLogin()){

				$params['client_id']= Yii::app()->functions->getClientId();

			}

					

			$merchant_booking_receiver=Yii::app()->functions->getOption("merchant_booking_receiver",$merchant_id);

			$merchant_booking_tpl=Yii::app()->functions->getOption("merchant_booking_tpl",$merchant_id);

			

			if (empty($merchant_booking_tpl)){

			    $merchant_booking_tpl=EmailTPL::bookingTPL();

			}

			$merchant_booking_receive_subject=Yii::app()->functions->getOption("merchant_booking_receive_subject",

			$merchant_id);

			

			$sender='no-reply@'.$_SERVER['HTTP_HOST'];			

			

			if ( !$merchant_info=Yii::app()->functions->getMerchant($merchant_id)){			

				$merchant_info['restaurant_name']=t("None");

			}

			

			$h='';

			$h.='<table border="0">';

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Restaurant name").'</td>';

			$h.='<td>: '.ucwords($merchant_info['restaurant_name']).'</td>';

			$h.='</tr>';

			

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Number Of Guests").'</td>';

			$h.='<td>: '.$params['number_guest'].'</td>';

			$h.='</tr>';

			

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Date Of Booking").'</td>';

			$h.='<td>: '.$params['date_booking'].'</td>';

			$h.='</tr>';

			

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Time").'</td>';

			$h.='<td>: '.$params['booking_time'].'</td>';

			$h.='</tr>';

			

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Name").'</td>';

			$h.='<td>: '.$params['booking_name'].'</td>';

			$h.='</tr>';

			

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Email").'</td>';

			$h.='<td>: '.$params['email'].'</td>';

			$h.='</tr>';

			

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Mobile").'</td>';

			$h.='<td>: '.$params['mobile'].'</td>';

			$h.='</tr>';

			

			$h.='<tr>';

			$h.='<td>'.Yii::t("default","Message").'</td>';

			$h.='<td>: '.$params['booking_notes'].'</td>';			

			$h.='</tr>';

			

			$h.='</table>';

						

			

			$template=Yii::app()->functions->smarty("booking-information",$h,$merchant_booking_tpl);

									

			if ( $db_ext->insertData('{{bookingtable}}',$params)){

			    $this->code=1;

			    $this->msg=Yii::t("default","Thank you your booking has been received");			    			    

			    

			    if (!empty($merchant_booking_receiver) && !empty($template)){

			       if (!sendEmail($merchant_booking_receiver,$sender,$merchant_booking_receive_subject,$template)){

					} 

			    }			    

			} else $this->msg=Yii::t("default","Something went wrong during processing your request. Please try again later.");

		}

		

		public function tableBookingList()

		{

			$slug=$this->data['slug'];

			$stmt="

			SELECT * FROM

			{{bookingtable}}

			WHERE

			merchant_id='".Yii::app()->functions->getMerchantID()."'

			ORDER BY booking_id DESC

			";

			$connection=Yii::app()->db;

    	    $rows=$connection->createCommand($stmt)->queryAll();     	    

    	    if (is_array($rows) && count($rows)>=1){

    	    	foreach ($rows as $val) {    	 

    	    		$chk="<input type=\"checkbox\" name=\"row[]\" value=\"$val[booking_id]\" class=\"chk_child\" >";   		

    	    		$option="<div class=\"options\">

    	    		<a href=\"$slug/id/$val[booking_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[booking_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";

    	    		

    	    		/*$date=date('M d,Y G:i:s',strtotime($val['date_created']));

    	    		$date=Yii::app()->functions->translateDate($date);

    	    		$dateb=date('M d,Y',strtotime($val['date_booking']));

    	    		$dateb=Yii::app()->functions->translateDate($dateb);*/

    	    		$date=FormatDateTime($val['date_created']);

    	    		$dateb=FormatDateTime($val['date_booking'],false);

    	    		

    	    		$stats='';

    	    		switch ($val['status']) {

    	    			case 'pending':

    	    				//$stats='<div class="uk-badge uk-badge-warning">'.$val['status'].'</div>';

    	    				$stats='<div class="uk-badge">'.$val['status'].'</div>';

    	    				break;

    	    			case "approved":	

    	    			    $stats='<div class="uk-badge uk-badge-success">'.$val['status'].'</div>';

    	    				 break;

    	    			case 'denied':

    	    				$stats='<div class="uk-badge uk-badge-danger">'.$val['status'].'</div>';

    	    				 break;

    	    			default:

    	    				$stats='<div class="uk-badge">'.$val['status'].'</div>';

    	    				break;

    	    		}

    	    		

    	    		$feed_data['aaData'][]=array(

    	    		  $val['booking_id'],stripslashes($val['booking_name']).$option,

    	    		  $dateb."@".$val['booking_time'],

    	    		  $val['number_guest'],

    	    		  $val['mobile'],

    	    		  $val['booking_notes'],

    	    		  $date."<br/>".$stats

    	    		);

    	    	}

    	    	$this->otableOutput($feed_data);

    	    }     	    

    	    $this->otableNodata();

		}

		

		public function bookATableMerchant()

		{

			

			$merchant_id=Yii::app()->functions->getMerchantID();			

			$params=array(

			  'merchant_id'=>$merchant_id,

			  'number_guest'=>isset($this->data['number_guest'])?$this->data['number_guest']:'',

			  'date_booking'=>isset($this->data['date_booking'])?$this->data['date_booking']:'',

			  'booking_time'=>isset($this->data['booking_time'])?$this->data['booking_time']:'',

			  'booking_name'=>isset($this->data['booking_name'])?$this->data['booking_name']:'',

			  'email'=>isset($this->data['email'])?$this->data['email']:'',

			  'mobile'=>isset($this->data['mobile'])?$this->data['mobile']:'',

			  'booking_notes'=>isset($this->data['booking_notes'])?$this->data['booking_notes']:'',

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'status'=>isset($this->data['status'])?$this->data['status']:'',

			  'viewed'=>2

			);			

			

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{bookingtable}}' , $params , 

				'booking_id=:booking_id' , array(':booking_id'=> addslashes($this->data['id']) ));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Booking updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{bookingtable}}',$params)){

					$this->details=Yii::app()->db->getLastInsertID();	                

					//dump($this->details);

	                $this->code=1;

	                $this->msg=Yii::t("default",'Booking added.');  	                

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}

			

			$this->details=3;

			//dump($this->data);

			

			if (isset($this->data['send_email'])){

				if ($this->code==1 || $this->data['send_email']==1){					

					$tp1=Yii::app()->functions->getOption("merchant_booking_approved_tpl",$merchant_id);

                    $tp2=Yii::app()->functions->getOption("merchant_booking_denied_tpl",$merchant_id);

                    $subject=Yii::app()->functions->getOption("merchant_booking_subject",$merchant_id);

                    $sender=Yii::app()->functions->getOption("merchant_booking_sender",$merchant_id);

                    $to=$this->data['email'];

                    $subject=empty($subject)?"Booking Table":$subject;

                    

                    

                    if ( empty($tp1)){

						$tp1=EmailTPL::bookingApproved();

					}

					if ( empty($tp2)){

						$tp2=EmailTPL::bookingDenied();

					}

					

						$h='';

						$h.='<table border="0">';

						$h.='<tr>';

						$h.='<td>'.Yii::t("default","Number Of Guests").'</td>';

						$h.='<td>: '.$params['number_guest'].'</td>';

						$h.='</tr>';

						

						$h.='<tr>';

						$h.='<td>'.Yii::t("default","Date Of Booking").'</td>';

						$h.='<td>: '.$params['date_booking'].'</td>';

						$h.='</tr>';

						

						$h.='<tr>';

						$h.='<td>'.Yii::t("default","Time").'</td>';

						$h.='<td>: '.$params['booking_time'].'</td>';

						$h.='</tr>';

						

						$h.='<tr>';

						$h.='<td>'.Yii::t("default","Name").'</td>';

						$h.='<td>: '.$params['booking_name'].'</td>';

						$h.='</tr>';

						

						$h.='<tr>';

						$h.='<td>'.Yii::t("default","Email").'</td>';

						$h.='<td>: '.$params['email'].'</td>';

						$h.='</tr>';

						

						$h.='<tr>';

						$h.='<td>'.Yii::t("default","Mobile").'</td>';

						$h.='<td>: '.$params['mobile'].'</td>';

						$h.='</tr>';

						

						$h.='<tr>';

						$h.='<td>'.Yii::t("default","Message").'</td>';

						$h.='<td>: '.$this->data['message'].'</td>';			

						$h.='</tr>';

						

						$h.='</table>';

			                                        

					if ($this->data['status']=="approved"){

						$template=Yii::app()->functions->smarty("customer-name",$params['booking_name'],$tp1);

						$template=Yii::app()->functions->smarty("message",$this->data['message'],$template);

						$template=Yii::app()->functions->smarty("booking-information",$h,$template);

												

					} elseif ($this->data['status']=="denied"){

						$template=Yii::app()->functions->smarty("customer-name",$params['booking_name'],$tp2);

						$template=Yii::app()->functions->smarty("message",$this->data['message'],$template);

						$template=Yii::app()->functions->smarty("booking-information",$h,$template);

					}				



					

					if (!sendEmail($to,$sender,$subject,$template)){						

						$this->details=2;

					} else $this->details=1;

				}		

			}

		}	

		

		public function analyticsSetting()

		{

		

			Yii::app()->functions->updateOptionAdmin("admin_header_codes",

	    	isset($this->data['admin_header_codes'])?$this->data['admin_header_codes']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");	

		}	

		

		public function customerList()

		{

			

			$slug=Yii::app()->request->baseUrl."/admin/".$_GET['slug'];

			$stmt="SELECT * FROM

			{{client}}		

			ORDER BY client_id DESC						

			";						

			$_SESSION['kr_export_stmt']=$stmt;

			

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[client_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[client_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	  

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date);	 */

				   $date=FormatDateTime($val['date_created']);

				   $address=$val['street']." ".$val['city']." ".$val['state']." ".$val['zipcode']." ".$val['country_code'];

			   	   $feed_data['aaData'][]=array(

			   	      $val['client_id'],

			   	      $val['first_name']." ".$val['last_name'].$action,

			   	      $val['email_address'],

			   	      $val['contact_phone'],

			   	      $address,

			   	      $date."<br/>".$val['status']

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();			    		    		

			

		}	

		

		public function customerAdd()

		{			

			

			/*csrf validation*/

			if(!isset($_POST[Yii::app()->request->csrfTokenName])){

				$this->msg=t("The CSRF token is missing");

				return ;

			}	    

			if ( $_POST[Yii::app()->request->csrfTokenName] != Yii::app()->getRequest()->getCsrfToken()){

				$this->msg=t("The CSRF token could not be verified");

				return ;

			}  	



			$p = new CHtmlPurifier();

			

			$params=array(

			  'first_name'=>$p->purify($this->data['first_name']),

			  'last_name'=>$p->purify($this->data['last_name']),

			  'email_address'=>$p->purify($this->data['email_address']),

			  'street'=>$p->purify($this->data['street']),

			  'city'=>$p->purify($this->data['city']),

			  'state'=>$p->purify($this->data['state']),

			  'zipcode'=>$p->purify($this->data['zipcode']),

			  'status'=>$this->data['status'],

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR']			  

			);	

			

				

			if (isset($this->data['password'])){

				if (!empty($this->data['password'])){

					$params['password']=md5($this->data['password']);

				}

			}			

						

           /** update 2.3*/

	    	if (isset($this->data['custom_field1'])){

	    		$params['custom_field1']=!empty($this->data['custom_field1'])?$this->data['custom_field1']:'';

	    	}

	    	if (isset($this->data['custom_field2'])){

	    		$params['custom_field2']=!empty($this->data['custom_field2'])?$this->data['custom_field2']:'';

	    	}	    			    

			

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{client}}' , $params , 

				'client_id=:client_id' , array(':client_id'=>addslashes($this->data['id'])));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Client updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

			} else {				

				if ($res=$command->insert('{{client}}',$params)){

					$this->details=Yii::app()->db->getLastInsertID();	

	                $this->code=1;

	                $this->msg=Yii::t("default",'Client added.');  	                

	            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

			}

			

		}

		

		public function bookingAlertSettings()

		{		

			$merchant_id=Yii::app()->functions->getMerchantID();

						

			Yii::app()->functions->updateOption("merchant_booking_alert",

	    	isset($this->data['merchant_booking_alert'])?$this->data['merchant_booking_alert']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_booking_approved_tpl",

	    	isset($this->data['merchant_booking_approved_tpl'])?$this->data['merchant_booking_approved_tpl']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_booking_denied_tpl",

	    	isset($this->data['merchant_booking_denied_tpl'])?$this->data['merchant_booking_denied_tpl']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_booking_subject",

	    	isset($this->data['merchant_booking_subject'])?$this->data['merchant_booking_subject']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_booking_sender",

	    	isset($this->data['merchant_booking_sender'])?$this->data['merchant_booking_sender']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_booking_receiver",

	    	isset($this->data['merchant_booking_receiver'])?$this->data['merchant_booking_receiver']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_booking_tpl",

	    	isset($this->data['merchant_booking_tpl'])?$this->data['merchant_booking_tpl']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_booking_receive_subject",

	    	isset($this->data['merchant_booking_receive_subject'])?$this->data['merchant_booking_receive_subject']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("max_booked",

	    	isset($this->data['max_booked'])?json_encode($this->data['max_booked']):''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("fully_booked_msg",

	    	isset($this->data['fully_booked_msg'])?$this->data['fully_booked_msg']:''

	    	,$merchant_id);	    	

	    	

	    	Yii::app()->functions->updateOption("accept_booking_sameday",

	    	isset($this->data['accept_booking_sameday'])?$this->data['accept_booking_sameday']:''

	    	,$merchant_id);	    	

	    	

	    	Yii::app()->functions->updateOption("merchant_table_booking",

	    	isset($this->data['merchant_table_booking'])?$this->data['merchant_table_booking']:''

	    	,$merchant_id);	    	

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Settings saved.");	

		}

		

		public function getNewBooking()

		{

			$list='';

	    	if ($res=Yii::app()->functions->newTableBooking(1)){	    			    	

	    		$this->code=1;

	    		$link="<a style=\"color:#fff;display:block;\" href=\"".websiteUrl()."/merchant/tablebooking"."\">".t("Click here to view")."</a>";

	    		$this->msg=count($res)." ".t("New Booking Table")." $link";

	    		$this->details=$list;

	    	} else $this->msg=Yii::t("default","No results");	    

		}	

		

		public function rptIncomingOrders()

		{		

			$and='';  

	    	if (isset($this->data['start_date']) && isset($this->data['end_date']))	{

	    		if (!empty($this->data['start_date']) && !empty($this->data['end_date'])){

	    		$and=" AND date_created BETWEEN  '".$this->data['start_date']." 00:00:00' AND 

	    		        '".$this->data['end_date']." 23:59:00'

	    		 ";

	    		}

	    	}

	    	

	    	$order_status_id='';

	    	$or='';

	    	if (isset($this->data['stats_id'])){

		    	if (is_array($this->data['stats_id']) && count($this->data['stats_id'])>=1){

		    		foreach ($this->data['stats_id'] as $stats_id) {		    			

		    			$order_status_id.="'$stats_id',";

		    		}

		    		if ( !empty($order_status_id)){

		    			$order_status_id=substr($order_status_id,0,-1);

		    		}		    	

		    	}	    

	    	}

	    	

	    	if ( !empty($order_status_id)){	    		

	    		$and.= " AND status IN ($order_status_id)";

	    	}	    	    	

	    	 

	    	//dump($and);	    	

	    	

	    	$DbExt=new DbExt;

	    	$merchant_id=Yii::app()->functions->getMerchantID();	    

	    	$stmt="SELECT a.*,

	    	(

	    	select concat(first_name,' ',last_name)

	    	from

	    	{{client}}

	    	where

	    	client_id=a.client_id

	    	) as client_name,

	    	

	    	(

	    	select group_concat(item_name)

	    	from

	    	{{order_details}}

	    	where

	    	order_id=a.order_id

	    	) as item,

	    	

	    	(

	    	select restaurant_name

	    	from

	    	{{merchant}}

	    	where

	    	merchant_id=a.merchant_id

	    	) as merchant_name

	    	

	    	FROM

	    	{{order}} a	    	

	    	

	    	WHERE a.status NOT IN ('".initialStatus()."')

	    	

	    	AND date_created LIKE '".date("Y-m-d")."%'

	    	

	    	ORDER BY order_id DESC

	    	LIMIT 0,100

	    	";

	    		    		    

	    	//dump($stmt);

	    	if ( $res=$DbExt->rst($stmt)){

	    		foreach ($res as $val) {	

	    			

	    			$merchant_id=$val['merchant_id'];

	    			

	    			$action="<a data-id=\"".$val['order_id']."\" class=\"edit-order\" href=\"javascript:\">".Yii::t("default","Edit")."</a>";

	    			$action="<br/><a data-id=\"".$val['order_id']."\" class=\"view-receipt\" href=\"javascript:\">".Yii::t("default","View")."</a>";

	    			$date=FormatDateTime($val['date_created']);

	    			$date=Yii::app()->functions->translateDate($date);

	    			

	    			$item=FunctionsV3::translateFoodItemByOrderId(

	    			  $val['order_id'],

	    			  'kr_admin_lang_id'

	    			);

	    			

	    			$feed_data['aaData'][]=array(

	    			  $val['order_id'],

	    			  ucwords($val['merchant_name']),

	    			  ucwords($val['client_name']),

	    			  $item,

	    			  ucwords(Yii::t("default",$val['trans_type'])),

	    			  //strtoupper(Yii::t("default",$val['payment_type'])),

	    			  FunctionsV3::prettyPaymentType('payment_order',$val['payment_type'],$val['order_id']),

	    			  prettyFormat($val['sub_total'],$merchant_id),

	    			  prettyFormat($val['taxable_total'],$merchant_id),

	    			  prettyFormat($val['total_w_tax'],$merchant_id),

	    			  ucwords($val['status']),

	    			  $date.$action,

	    			  $action

	    		    );

	    		}

	    		$this->otableOutput($feed_data);

	    	}	   

	    	$this->otableNodata();

		}	

		

		public function rptAdminSalesRpt()

		{

	    	$and='';  

	    	if (isset($this->data['start_date']) && isset($this->data['end_date']))	{

	    		if (!empty($this->data['start_date']) && !empty($this->data['end_date'])){

	    		$and=" AND date_created BETWEEN  '".$this->data['start_date']." 00:00:00' AND 

	    		        '".$this->data['end_date']." 23:59:00'

	    		 ";

	    		}

	    	}

	    	

	    	$order_status_id='';

	    	$or='';

	    	if (isset($this->data['stats_id'])){

		    	if (is_array($this->data['stats_id']) && count($this->data['stats_id'])>=1){

		    		foreach ($this->data['stats_id'] as $stats_id) {		    			

		    			$order_status_id.="'$stats_id',";

		    		}

		    		if ( !empty($order_status_id)){

		    			$order_status_id=substr($order_status_id,0,-1);

		    		}		    	

		    	}	    

	    	}

	    	

	    	if ( !empty($order_status_id)){	    		

	    		$and.= " AND status IN ($order_status_id)";

	    	}	    	    	

	    	 	    	

	    	$DbExt=new DbExt;

	    	$merchant_id= isset($this->data['merchant_id'])?$this->data['merchant_id']:'';

	    	

	    	$stmt="SELECT a.*,

	    	(

	    	select concat(first_name,' ',last_name)

	    	from

	    	{{client}}

	    	where

	    	client_id=a.client_id

	    	) as client_name,

	    	

	    	(

	    	select group_concat(item_name)

	    	from

	    	{{order_details}}

	    	where

	    	order_id=a.order_id

	    	) as item

	    	

	    	FROM

	    	{{order}} a

	    	WHERE

	    	merchant_id='$merchant_id'

	    	AND status NOT IN ('".initialStatus()."')	    	

	    	$and

	    	ORDER BY order_id DESC

	    	LIMIT 0,2000

	    	";

	    	/*dump($this->data);

	    	dump($stmt);*/

	    	

	    	$_SESSION['kr_export_stmt']=$stmt;	    	

	    		    		    	

	    	if ( $res=$DbExt->rst($stmt)){

	    		foreach ($res as $val) {	    			    			

	    			$action="<a data-id=\"".$val['order_id']."\" class=\"edit-order\" href=\"javascript:\">".Yii::t("default","Edit")."</a>";

	    			$action.="<a data-id=\"".$val['order_id']."\" class=\"view-receipt\" href=\"javascript:\">".Yii::t("default","View")."</a>";

	    			/*$date=prettyDate($val['date_created'],true);

	    			$date=Yii::app()->functions->translateDate($date);*/

	    			$date=FormatDateTime($val['date_created']);

	    			

	    			$feed_data['aaData'][]=array(

	    			  $val['order_id'],

	    			  ucwords($val['client_name']),

	    			  $val['item'],

	    			  ucwords(Yii::t("default",$val['trans_type'])),

	    			  strtoupper(Yii::t("default",$val['payment_type'])),

	    			  prettyFormat($val['sub_total'],$merchant_id),

	    			  prettyFormat($val['tax'],$merchant_id),

	    			  prettyFormat($val['total_w_tax'],$merchant_id),

	    			  ucwords($val['status']),

	    			  $date

	    			  //$action

	    		    );

	    		}

	    		$this->otableOutput($feed_data);

	    	}	   

	    	$this->otableNodata();			

		}

		

		public function adminBankDeposit()

		{

			Yii::app()->functions->updateOptionAdmin("admin_bankdeposit_enabled",

	    	isset($this->data['admin_bankdeposit_enabled'])?$this->data['admin_bankdeposit_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_deposit_instructions",

	    	isset($this->data['admin_deposit_instructions'])?$this->data['admin_deposit_instructions']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_deposit_sender",

	    	isset($this->data['admin_deposit_sender'])?$this->data['admin_deposit_sender']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_deposit_subject",

	    	isset($this->data['admin_deposit_subject'])?$this->data['admin_deposit_subject']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}

		

		public function bankDepositVerification()

		{

			$DbExt=new DbExt;

			 if ($res=Yii::app()->functions->getMerchantByToken($this->data['ref'])){			 	

				$params=array(				

				  'merchant_id'=>$res['merchant_id'],

				  'branch_code'=>$this->data['branch_code'],

				  'date_of_deposit'=>$this->data['date_of_deposit'],

				  'time_of_deposit'=>$this->data['time_of_deposit'],

				  'amount'=>$this->data['amount'],

				  'scanphoto'=>isset($this->data['photo'])?$this->data['photo']:'',

				  'date_created'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR']

				);				

				if ($DbExt->insertData("{{bank_deposit}}",$params)){

					$this->code=1;

					$this->msg=Yii::t("default","Thank you. Your information has been receive please wait 1 or 2 days to verify your payment.");

					

					/*send email to admin owner*/

					$from='no-reply@'.$_SERVER['HTTP_HOST'];

    	            $subject=Yii::t("default","New Bank Deposit");

    	            $to=Yii::app()->functions->getOptionAdmin('website_contact_email');

    	            $tpl=EmailTPL::bankDepositedReceive();

    	            if (!empty($to)){

    	                Yii::app()->functions->sendEmail($to,$from,$subject,$tpl);

    	            }

					

				} else $this->msg=Yii::t("default","Something went wrong during processing your request. Please try again later.");

			 } else $this->msg=Yii::t("default","Reference number not found");

		}	

		

		public function BankDepositList()

		{

			$slug=$this->data['slug'];

			$stmt="SELECT a.*,

			(

			select restaurant_name from

			{{merchant}}

			where merchant_id=a.merchant_id

			) as merchant_name

			 FROM

			{{bank_deposit}} a

			ORDER BY id DESC

			";

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					/*$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[cuisine_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[cuisine_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   */

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);

				   $date=Yii::app()->functions->translateDate($date);*/

				   $date=FormatDateTime($val['date_created']);

				   

				   if (!empty($val['scanphoto'])){

				      $img=Yii::app()->request->baseUrl."/upload/$val[scanphoto]";

				      $scanphoto="<a href=\"$img\" target=\"_blank\">";

    	    		  $scanphoto.="<img class=\"uk-thumbnail uk-thumbnail-mini\" src=\"$img\" >";	

    	    		  $scanphoto.="</a>";

				   } else $scanphoto='';

				   

			   	   $feed_data['aaData'][]=array(

			   	      $val['id'],

			   	      t($val['transaction_type']),

			   	      stripslashes(ucwords($val['merchant_name'])),

			   	      $val['branch_code'],

			   	      FormatDateTime($val['date_of_deposit'],false),

			   	      $val['time_of_deposit'],

			   	      Yii::app()->functions->standardPrettyFormat($val['amount']),

			   	      $scanphoto,

			   	      $date

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function adminPayseraSettings()

		{

			Yii::app()->functions->updateOptionAdmin("admin_paysera_enabled",

	    	isset($this->data['admin_paysera_enabled'])?$this->data['admin_paysera_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_paysera_mode",

	    	isset($this->data['admin_paysera_mode'])?$this->data['admin_paysera_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_paysera_project_id",

	    	isset($this->data['admin_paysera_project_id'])?$this->data['admin_paysera_project_id']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_paysera_password",

	    	isset($this->data['admin_paysera_password'])?$this->data['admin_paysera_password']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_paysera_lang",

	    	isset($this->data['admin_paysera_lang'])?$this->data['admin_paysera_lang']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_paysera_country",

	    	isset($this->data['admin_paysera_country'])?$this->data['admin_paysera_country']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}

		

		public function merchantPayseraSettings()

	    {

	    	$merchant_id=Yii::app()->functions->getMerchantID();

	    	

	    	Yii::app()->functions->updateOption("merchant_paysera_enabled",

	    	isset($this->data['merchant_paysera_enabled'])?$this->data['merchant_paysera_enabled']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_paysera_mode",

	    	isset($this->data['merchant_paysera_mode'])?$this->data['merchant_paysera_mode']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_paysera_project_id",

	    	isset($this->data['merchant_paysera_project_id'])?$this->data['merchant_paysera_project_id']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_paysera_password",

	    	isset($this->data['merchant_paysera_password'])?$this->data['merchant_paysera_password']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_paysera_country",

	    	isset($this->data['merchant_paysera_country'])?$this->data['merchant_paysera_country']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_paysera_lang",

	    	isset($this->data['merchant_paysera_lang'])?$this->data['merchant_paysera_lang']:'',$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

	    }				   

	    

		public function SMSbankDepositVerification()

		{			

			if (isset($this->data['photo'])){

				$req=array('ref'=>t("reference number is required"));

			} else {

		        $req=array(

		          'branch_code'=>t("branch code is required"),

		          'date_of_deposit'=>t("date of deposit is required"),

		          'time_of_deposit'=>t("time of deposit is required"),

		          'amount'=>t("amount is required"),

		        );

			}

			$Validator=new Validator;			

			$Validator->required($req,$this->data);

						

			if ($Validator->validate()){

				$DbExt=new DbExt;

				 if ($res=Yii::app()->functions->mercadoGetPayment($this->data['ref'])){			 	

				 	

					$params=array(				

					  'merchant_id'=>$res[0]['merchant_id'],

					  'branch_code'=>$this->data['branch_code'],

					  'date_of_deposit'=>$this->data['date_of_deposit'],

					  'time_of_deposit'=>$this->data['time_of_deposit'],

					  'amount'=>$this->data['amount'],

					  'scanphoto'=>isset($this->data['photo'])?$this->data['photo']:'',

					  'date_created'=>date('c'),

					  'ip_address'=>$_SERVER['REMOTE_ADDR'],

					  'transaction_type'=>"sms_purchase"

					);									

					if ($DbExt->insertData("{{bank_deposit}}",$params)){

						$this->code=1;

						$this->msg=Yii::t("default","Thank you. Your information has been receive please wait 1 or 2 days to verify your payment.");

						

						/*send email to admin owner*/

						$from='no-reply@'.$_SERVER['HTTP_HOST'];

	    	            $subject=Yii::t("default","New Bank Deposit");

	    	            $to=Yii::app()->functions->getOptionAdmin('website_contact_email');

	    	            $tpl=EmailTPL::bankDepositedReceive();

	    	            if (!empty($to)){

	    	                Yii::app()->functions->sendEmail($to,$from,$subject,$tpl);

	    	            }

						

					} else $this->msg=Yii::t("default","Something went wrong during processing your request. Please try again later.");

				 } else $this->msg=Yii::t("default","Reference number not found");

			} else $this->msg=$Validator->getErrorAsHTML();

		}		    

				

		public function emailSettings()

		{

			if (!isset($this->data['email_provider'])){

				$this->msg=t("please select email provider");

				return ;

			}		

			Yii::app()->functions->updateOptionAdmin("smtp_host",

	    	isset($this->data['smtp_host'])?$this->data['smtp_host']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("smtp_port",

	    	isset($this->data['smtp_port'])?$this->data['smtp_port']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("smtp_username",

	    	isset($this->data['smtp_username'])?$this->data['smtp_username']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("smtp_password",

	    	isset($this->data['smtp_password'])?$this->data['smtp_password']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("email_provider",

	    	isset($this->data['email_provider'])?$this->data['email_provider']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("mandrill_api_key",

	    	isset($this->data['mandrill_api_key'])?$this->data['mandrill_api_key']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");		  

		}
       
	    public function addRMBrate()
	    {
		   Yii::app()->functions->updateOptionAdmin("euro_amount",

	    	isset($this->data['euro_amount'])?$this->data['euro_amount']:'');
			
			
			Yii::app()->functions->updateOptionAdmin("rmb_amount",

	    	isset($this->data['rmb_amount'])?$this->data['rmb_amount']:'');
			
			
	    	$this->code=1;

	    	$this->msg=Yii::t("default","Amount saved");
	    }
		

		public function emailTplSettings()

		{

			Yii::app()->functions->updateOptionAdmin("email_tpl_activation",

	    	isset($this->data['email_tpl_activation'])?$this->data['email_tpl_activation']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("email_tpl_forgot",

	    	isset($this->data['email_tpl_forgot'])?$this->data['email_tpl_forgot']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("email_tpl_customer_reg",

	    	isset($this->data['email_tpl_customer_reg'])?$this->data['email_tpl_customer_reg']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("email_tpl_customer_subject",

	    	isset($this->data['email_tpl_customer_subject'])?$this->data['email_tpl_customer_subject']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");		  

		}	

		

		public function paymentgatewaySettings()

		{		

			Yii::app()->functions->updateOptionAdmin("paymentgateway",

	    	isset($this->data['paymentgateway'])?json_encode($this->data['paymentgateway']):'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");		  

		}	

				

		public function gallerySettings()

		{			

			$merchant_id=Yii::app()->functions->getMerchantID();

						

			Yii::app()->functions->updateOption("merchant_gallery",

	    	isset($this->data['photo'])?json_encode($this->data['photo']):''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("gallery_disabled",

	    	isset($this->data['gallery_disabled'])?$this->data['gallery_disabled']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}						

		

	    public function adminForgotPass()

	    {	    	

	    	if ( isset($this->data['email_address'])){

	    		if ($res=yii::app()->functions->isAdminExist($this->data['email_address']) ){	    			

	    			$new_pass=yii::app()->functions->generateCode();

	    			$params=array(

	    			  'lost_password_code'=> $new_pass,

	    			  'password'=>md5($new_pass)

	    			  );	    			

	    			if ( $this->updateData("{{admin_user}}",$params,'admin_id',$res[0]['admin_id'])){

	    				$this->code=1;

	    				$this->msg=Yii::t("default","An email address was sent to your.");		    				

	    				$tpl=EmailTPL::adminForgotPassword($new_pass);	    					    			

	    				$sender=Yii::app()->functions->getOptionAdmin('website_contact_email');

		                $to=$res[0]['email_address'];		                

		                if (!sendEmail($to,$sender,t("Admin Forgot Password"),$tpl)){		    	

		                	$this->details="failed $new_pass";

		                } else $this->details="ok mail $new_pass";

	    				

	    			} else $this->msg=Yii::t("default","ERROR: Cannot update.");	    		

	    		} else $this->msg=Yii::t("default","Sorry but we cannot find your email address.");

	    	} else $this->msg=Yii::t("default","Email address is required");	    

	    }	    	

	    

	    public function addPayonDeliver()

	    {	    	

	    	$req=array(

	    	  'photo'=>t("Payment Logo is required"),

	    	  'card_name'=>t("Payment Name is required")

	    	);

	    	$Validator=new Validator;

	    	$Validator->required($req,$this->data);

			if ($Validator->validate()){

				$params=array(

				  'payment_name'=>$this->data['card_name'],

				  'payment_logo'=>$this->data['photo'],

				  'date_created'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR'],

				  'status'=>$this->data['status']

				);

				if (isset($this->data['id']) && is_numeric($this->data['id'])){	

					$params['date_modified']=date('c');

					unset($params['date_created']);

					if ( $this->updateData("{{payment_provider}}",$params,'id',$this->data['id'])){

						$this->code=1;

						$this->msg=t("Successful updated");

					} else $this->msg=t("ERROR: cannot update");

				} else {

					if ( $this->insertData("{{payment_provider}}",$params)){

						$this->details=Yii::app()->db->getLastInsertID();						

						$this->code=1;

						$this->msg=t("Successful");

					} else $this->msg=t("ERROR. cannot insert data.");

				}	

			} else $this->msg=$Validator->getErrorAsHTML();

	    }

		

		public function paymentProviderList()

		{

		    $slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{payment_provider}}

			ORDER BY id DESC

			";

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	   

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);

				   $date=Yii::app()->functions->translateDate($date);*/

				   $date=FormatDateTime($val['date_created']);

			   	   $feed_data['aaData'][]=array(

			   	      $val['id'],

			   	      $val['payment_name'].$action,

			   	      '<img src="'.uploadURL()."/".$val['payment_logo'].'" class="uk-thumbnail uk-thumbnail-mini" >',

			   	      $date."<br/>".$val['status']

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();

		}

		

		public function payOnDeliveryMerchant()

		{			

			$merchant_id=Yii::app()->functions->getMerchantID();

			

			Yii::app()->functions->updateOption("payment_provider",

	    	isset($this->data['payment_provider'])?json_encode($this->data['payment_provider']):''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_payondeliver_enabled",

	    	isset($this->data['merchant_payondeliver_enabled'])?$this->data['merchant_payondeliver_enabled']:''

	    	,$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}

		

		public function merchantOffers()

		{

			$class='';

			$merchant_id=Yii::app()->functions->getMerchantID();		    

			$slug=$this->data['slug'];

			$stmt="SELECT * FROM

			{{offers}}

			WHERE

			merchant_id=".$this->q($merchant_id)."

			ORDER BY offers_id DESC

			";						

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[offers_id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[offers_id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	  

					

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date); */

				   $date=FormatDateTime($val['date_created']);

				   

			   	   $feed_data['aaData'][]=array(

			   	      $val['offers_id'],

			   	      standardPrettyFormat($val['offer_percentage'])." %".$action,

			   	      standardPrettyFormat($val['offer_price']),

			   	      FormatDateTime($val['valid_from'],false),

			   	      FormatDateTime($val['valid_to'],false),

			   	      

			   	      $date."<br/><div class=\"uk-badge $class\">".strtoupper(Yii::t("default",$val['status']))."</div>"

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();	

		}		

		

		public function q($data='')

		{

			return Yii::app()->db->quoteValue($data);

		}	

		

	    public function addOffers()

	    {

	    	$merchant_id=Yii::app()->functions->getMerchantID();

		    $params=array(

			  'offer_percentage'=>$this->data['offer_percentage'],			  

			  'offer_price'=>$this->data['offer_price'],

			  'valid_from'=>$this->data['valid_from'],

			  'valid_to'=>$this->data['valid_to'],

			  'date_created'=>date('c'),

			  'ip_address'=>$_SERVER['REMOTE_ADDR'],

			  'merchant_id'=>$merchant_id,

			  'status'=>$this->data['status']

			);					

			$command = Yii::app()->db->createCommand();

			if (isset($this->data['id']) && is_numeric($this->data['id'])){	

				

				if (!Yii::app()->functions->getMerchantOffers($merchant_id,

				$this->data['valid_from']." 00:00:00",$this->data['valid_to']." 00:00:00",$this->data['id'])){

				

				unset($params['date_created']);

				$params['date_modified']=date('c');				

				$res = $command->update('{{offers}}' , $params , 

				'offers_id=:offers_id' , array(':offers_id'=>addslashes($this->data['id'])));

				if ($res){

					$this->code=1;

	                $this->msg=Yii::t("default",'Offers updated');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

				} else $this->msg=t("Already one offer is their in particular period of time.please delete or change the status to draft that offer then you can add another One offer");

			} else {							

if (!Yii::app()->functions->getMerchantOffers($merchant_id,$this->data['valid_from']." 00:00:00",$this->data['valid_to']." 00:00:00")){

					if ($res=$command->insert('{{offers}}',$params)){

						$this->details=Yii::app()->db->getLastInsertID();	

		                $this->code=1;

		                $this->msg=Yii::t("default",'Offers added');  	                

		            } else $this->msg=Yii::t("default",'ERROR. cannot insert data.');

				} else $this->msg=t("Already one offer is their in particular period of time.please delete or change the status to draft that offer then you can add another One offer");

			}	    	

	    }			

	    

	    public function sendEmailMerchant()

	    {

	    	require_once 'sendemail-merchant.php';

	    	die();

	    }	

	    

	    public function sendEmailToMerchant()

	    {	    	

	    	if (isset($this->data['id'])){

	    		if ( $res=Yii::app()->functions->getMerchant($this->data['id'])){	    			

	    			$tpl=$this->data['email_content'];

	    			$tpl=smarty('restaurant_name',$res['restaurant_name'],$tpl);

	    			$tpl=smarty('status',$res['status'],$tpl);

	    			$tpl=smarty('owner_name',$res['contact_name'],$tpl);

	    			$tpl=smarty('website_title',Yii::app()->functions->getOptionAdmin("website_title"),$tpl);	    			

	    			$merchant_email=$res['contact_email'];	    			

	    			if ( !empty($merchant_email)){

	    				$from=getAdminGlobalSender();

	    				if (sendEmail($merchant_email,$from,$this->data['subject'],$tpl)){

	    					$this->code=1;

	    				    $this->msg=t("Email has been sent");

	    				} else $this->msg=t("Failed sending email");

	    			} else $this->msg=t("Merchant has no email address provided");	    		

	    		} else $this->msg=t("Merchant information not found");

	    	} else $this->msg=t("Missing merchant id");

	    }

	    

	    public function subscribeNewsletter()

	    {

	    	$validator=new Validator;

			$req=array(

			   'subscriber_email'=>t("Email is required")

			);

			$req_email=array(

			  'subscriber_email'=>t("Email address seems invalid")

			);

			$validator->required($req,$this->data);

			$validator->email($req_email,$this->data);

			

			if ( Yii::app()->functions->getSubsriberEmail($this->data['subscriber_email']) ){

				$validator->msg[]=t("Sorry your Email address is already exist in our records.");

			}

					

			if ( $validator->validate()){				

				$params=array(

				  'email_address'=>$this->data['subscriber_email'],

				  'date_created'=>date('c'),

				  'ip_address'=>$_SERVER['REMOTE_ADDR']

				);				

				if ( $this->insertData("{{newsletter}}",$params)){

					$this->code=1;

				    $this->msg=t("Thank you for subscribing to our mailing list!");

				} else $this->msg=t("Sorry there is error while we saving your information.");			

			} else $this->msg=$validator->getErrorAsHTML();

	    }

	    

		public function subscriberList()

		{

		    $slug=Yii::app()->request->baseUrl."/".ADMIN_CONTROLLER."/".$_GET['slug'];

			$stmt="SELECT * FROM

			{{newsletter}}		

			ORDER BY id DESC						

			";						

			$_SESSION['kr_export_stmt']=$stmt;

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				

			   	////<a href=\"$slug/Do/Add/?id=$val[client_id]\" >".Yii::t("default","Edit")."</a>   	    			   	    

					$action="<div class=\"options\">    	    		

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";		   	  

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date);	 */

				   $date=FormatDateTime($val['date_created']);

				   				   

			   	   $feed_data['aaData'][]=array(

			   	      $val['id'],			   	      

			   	      $val['email_address'].$action,

			   	      $date,

			   	      $val['ip_address']			   	      

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();		

		}	    

		

		public function addCustomPageLink()

		{		

			$params=array(

	    	  'page_name'=>$this->data['page_name'],

	    	  'content'=>$this->data['content'],	    	  

	    	  'date_created'=>date('c'),

	    	  'ip_address'=>$_SERVER['REMOTE_ADDR'],

	    	  'status'=>$this->data['status'],

	    	  'is_custom_link'=>2,

	    	  'open_new_tab'=>isset($this->data['open_new_tab'])?$this->data['open_new_tab']:1

	    	);

	    		    	

	    	if (empty($this->data['id'])){		    		

		    	if ( $this->insertData("{{custom_page}}",$params)){

		    		$this->details=Yii::app()->db->getLastInsertID();

			    		$this->code=1;

			    		$this->msg=Yii::t("default","Successful");			    		

			    	}

			    } else {					    	

			    	unset($params['date_created']);

					$params['date_modified']=date('c');				

					$res = $this->updateData('{{custom_page}}' , $params ,'id',$this->data['id']);

					if ($res){

						$this->code=1;

		                $this->msg=Yii::t("default",'Page updated.');  

				} else $this->msg=Yii::t("default","ERROR: cannot update");

		    }		    		

		}	

		

		public function smsLogs()

		{

			$slug=Yii::app()->request->baseUrl."/admin/".$_GET['slug'];

			$stmt="SELECT a.*,

			(

			select restaurant_name

			from

			{{merchant}}

			where merchant_id=a.merchant_id

			) as restaurant_name

			 FROM

			{{sms_broadcast_details}} a

			ORDER BY id DESC

			LIMIT 0,1000

			";						

			if ($res=$this->rst($stmt)){

			   foreach ($res as $val) {				   	    			   	    

					/*$action="<div class=\"options\">

    	    		<a href=\"$slug/Do/Add/?id=$val[id]\" >".Yii::t("default","Edit")."</a>

    	    		<a href=\"javascript:;\" class=\"row_del\" rev=\"$val[id]\" >".Yii::t("default","Delete")."</a>

    	    		</div>";*/		   	  

					

				   /*$date=Yii::app()->functions->prettyDate($val['date_created']);	

				   $date=Yii::app()->functions->translateDate($date);	 */

				   $date=FormatDateTime($val['date_created']);

				   

			   	   $feed_data['aaData'][]=array(

			   	      $val['id'],

			   	      "<span class=\"uk-text-bold\">".$val['gateway']."</span>",

			   	      $val['restaurant_name'],

			   	      $val['contact_phone'],

			   	      "<span class=\"uk-text-success uk-text-small\">".$val['sms_message']."</span>",

			   	      "<span class=\"uk-text-warning uk-text-small\">".$val['gateway_response']."</span>",

			   	      "<span class=\"uk-text-danger uk-text-small\">".$val['status']."</span>",

			   	      $date

			   	   );			       

			   }

			   $this->otableOutput($feed_data);

			}

			$this->otableNodata();			 

		}

		

		public function saveAdminBarclaySettings()

		{

			

			Yii::app()->functions->updateOptionAdmin("admin_enabled_barclay",

	    	isset($this->data['admin_enabled_barclay'])?$this->data['admin_enabled_barclay']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mode_barclay",

	    	isset($this->data['admin_mode_barclay'])?$this->data['admin_mode_barclay']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sandbox_barclay_pspid",

	    	isset($this->data['admin_sandbox_barclay_pspid'])?$this->data['admin_sandbox_barclay_pspid']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_sandbox_barclay_password",

	    	isset($this->data['admin_sandbox_barclay_password'])?$this->data['admin_sandbox_barclay_password']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_live_barclay_pspid",

	    	isset($this->data['admin_live_barclay_pspid'])?$this->data['admin_live_barclay_pspid']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_live_barclay_password",

	    	isset($this->data['admin_live_barclay_password'])?$this->data['admin_live_barclay_password']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_currency",

	    	isset($this->data['admin_bcy_currency'])?$this->data['admin_bcy_currency']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_language",

	    	isset($this->data['admin_bcy_language'])?$this->data['admin_bcy_language']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_font",

	    	isset($this->data['admin_bcy_font'])?$this->data['admin_bcy_font']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_language",

	    	isset($this->data['admin_bcy_language'])?$this->data['admin_bcy_language']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_bgcolor",

	    	isset($this->data['admin_bcy_bgcolor'])?$this->data['admin_bcy_bgcolor']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_buttontextcolor",

	    	isset($this->data['admin_bcy_buttontextcolor'])?$this->data['admin_bcy_buttontextcolor']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_table_bgcolor",

	    	isset($this->data['admin_bcy_table_bgcolor'])?$this->data['admin_bcy_table_bgcolor']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_table_textcolor",

	    	isset($this->data['admin_bcy_table_textcolor'])?$this->data['admin_bcy_table_textcolor']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_textcolor",

	    	isset($this->data['admin_bcy_textcolor'])?$this->data['admin_bcy_textcolor']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_title",

	    	isset($this->data['admin_bcy_title'])?$this->data['admin_bcy_title']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_buttoncolor",

	    	isset($this->data['admin_bcy_buttoncolor'])?$this->data['admin_bcy_buttoncolor']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_listype",

	    	isset($this->data['admin_bcy_listype'])?$this->data['admin_bcy_listype']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_bcy_logo",

	    	isset($this->data['admin_bcy_logo'])?$this->data['admin_bcy_logo']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

	    	

		}

		

	    public function saveMerchantBarclaySettings()

		{

			

			$merchant_id=Yii::app()->functions->getMerchantID();

						

			Yii::app()->functions->updateOption("merchant_enabled_barclay",

	    	isset($this->data['merchant_enabled_barclay'])?$this->data['merchant_enabled_barclay']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mode_barclay",

	    	isset($this->data['merchant_mode_barclay'])?$this->data['merchant_mode_barclay']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_barclay_pspid",

	    	isset($this->data['merchant_sandbox_barclay_pspid'])?$this->data['merchant_sandbox_barclay_pspid']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_barclay_password",

	    	isset($this->data['merchant_sandbox_barclay_password'])?$this->data['merchant_sandbox_barclay_password']:'',

	    	$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_live_barclay_pspid",

	    	isset($this->data['merchant_live_barclay_pspid'])?$this->data['merchant_live_barclay_pspid']:'',

	    	$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_live_barclay_password",

	    	isset($this->data['merchant_live_barclay_password'])?$this->data['merchant_live_barclay_password']:'',

	    	$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_currency",

	    	isset($this->data['merchant_bcy_currency'])?$this->data['merchant_bcy_currency']:'',

	    	$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_language",

	    	isset($this->data['merchant_bcy_language'])?$this->data['merchant_bcy_language']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_font",

	    	isset($this->data['merchant_bcy_font'])?$this->data['merchant_bcy_font']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_language",

	    	isset($this->data['merchant_bcy_language'])?$this->data['merchant_bcy_language']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_bgcolor",

	    	isset($this->data['merchant_bcy_bgcolor'])?$this->data['merchant_bcy_bgcolor']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_buttontextcolor",

	    	isset($this->data['merchant_bcy_buttontextcolor'])?$this->data['merchant_bcy_buttontextcolor']:'',

	    	$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_table_bgcolor",

	    	isset($this->data['merchant_bcy_table_bgcolor'])?$this->data['merchant_bcy_table_bgcolor']:'',

	    	$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_table_textcolor",

	    	isset($this->data['merchant_bcy_table_textcolor'])?$this->data['merchant_bcy_table_textcolor']:'',

	    	$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_textcolor",

	    	isset($this->data['merchant_bcy_textcolor'])?$this->data['merchant_bcy_textcolor']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_title",

	    	isset($this->data['merchant_bcy_title'])?$this->data['merchant_bcy_title']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_buttoncolor",

	    	isset($this->data['merchant_bcy_buttoncolor'])?$this->data['merchant_bcy_buttoncolor']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_listype",

	    	isset($this->data['merchant_bcy_listype'])?$this->data['merchant_bcy_listype']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_bcy_logo",

	    	isset($this->data['merchant_bcy_logo'])?$this->data['merchant_bcy_logo']:'',$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

	    	

		}		

		

		public function saveAdminEpaybgSettings()

		{

			

			Yii::app()->functions->updateOption("admin_enabled_epaybg",

	    	isset($this->data['admin_enabled_epaybg'])?$this->data['admin_enabled_epaybg']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_mode_epaybg",

	    	isset($this->data['admin_mode_epaybg'])?$this->data['admin_mode_epaybg']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_sandbox_epaybg_min",

	    	isset($this->data['admin_sandbox_epaybg_min'])?$this->data['admin_sandbox_epaybg_min']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_sandbox_epaybg_secret",

	    	isset($this->data['admin_sandbox_epaybg_secret'])?$this->data['admin_sandbox_epaybg_secret']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_live_epaybg_min",

	    	isset($this->data['admin_live_epaybg_min'])?$this->data['admin_live_epaybg_min']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_live_epaybg_secret",

	    	isset($this->data['admin_live_epaybg_secret'])?$this->data['admin_live_epaybg_secret']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_sandbox_epaybg_request",

	    	isset($this->data['admin_sandbox_epaybg_request'])?$this->data['admin_sandbox_epaybg_request']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_sandbox_epaybg_lang",

	    	isset($this->data['admin_sandbox_epaybg_lang'])?$this->data['admin_sandbox_epaybg_lang']:'');

	    	

	    	Yii::app()->functions->updateOption("admin_live_epaybg_request",

	    	isset($this->data['admin_live_epaybg_request'])?$this->data['admin_live_epaybg_request']:'');

	    	Yii::app()->functions->updateOption("admin_live_epaybg_lang",

	    	isset($this->data['admin_live_epaybg_lang'])?$this->data['admin_live_epaybg_lang']:'');

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}

		

		public function saveMerchantEpaybgSettings()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();

			

			Yii::app()->functions->updateOption("merchant_enabled_epaybg",

	    	isset($this->data['merchant_enabled_epaybg'])?$this->data['merchant_enabled_epaybg']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mode_epaybg",

	    	isset($this->data['merchant_mode_epaybg'])?$this->data['merchant_mode_epaybg']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_epaybg_min",

	    	isset($this->data['merchant_sandbox_epaybg_min'])?$this->data['merchant_sandbox_epaybg_min']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_epaybg_secret",

	    	isset($this->data['merchant_sandbox_epaybg_secret'])?$this->data['merchant_sandbox_epaybg_secret']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_live_epaybg_min",

	    	isset($this->data['merchant_live_epaybg_min'])?$this->data['merchant_live_epaybg_min']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_live_epaybg_secret",

	    	isset($this->data['merchant_live_epaybg_secret'])?$this->data['merchant_live_epaybg_secret']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_epaybg_request",

	    	isset($this->data['merchant_sandbox_epaybg_request'])?$this->data['merchant_sandbox_epaybg_request']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_live_epaybg_request",

	    	isset($this->data['merchant_live_epaybg_request'])?$this->data['merchant_live_epaybg_request']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_sandbox_epaybg_lang",

	    	isset($this->data['merchant_sandbox_epaybg_lang'])?$this->data['merchant_sandbox_epaybg_lang']:'',$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_live_epaybg_lang",

	    	isset($this->data['merchant_live_epaybg_lang'])?$this->data['merchant_live_epaybg_lang']:'',$merchant_id);

	    	

	    	$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}

		

		public function themeSettings()

		{

			

			Yii::app()->functions->updateOptionAdmin("theme_hide_logo",

	    	isset($this->data['theme_hide_logo'])?$this->data['theme_hide_logo']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_hide_how_works",

	    	isset($this->data['theme_hide_how_works'])?$this->data['theme_hide_how_works']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_hide_cuisine",

	    	isset($this->data['theme_hide_cuisine'])?$this->data['theme_hide_cuisine']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_featured_merchant",

	    	isset($this->data['disabled_featured_merchant'])?$this->data['disabled_featured_merchant']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("disabled_subscription",

	    	isset($this->data['disabled_subscription'])?$this->data['disabled_subscription']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("social_flag",

	    	isset($this->data['social_flag'])?$this->data['social_flag']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_custom_footer",

	    	isset($this->data['theme_custom_footer'])?$this->data['theme_custom_footer']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_show_app",

	    	isset($this->data['theme_show_app'])?$this->data['theme_show_app']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_app_android",

	    	isset($this->data['theme_app_android'])?$this->data['theme_app_android']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_app_ios",

	    	isset($this->data['theme_app_ios'])?$this->data['theme_app_ios']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_app_windows",

	    	isset($this->data['theme_app_windows'])?$this->data['theme_app_windows']:'');

			

	    	Yii::app()->functions->updateOptionAdmin("theme_filter_colapse",

	    	isset($this->data['theme_filter_colapse'])?$this->data['theme_filter_colapse']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_list_style",

	    	isset($this->data['theme_list_style'])?$this->data['theme_list_style']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("enabled_search_map",

	    	isset($this->data['enabled_search_map'])?$this->data['enabled_search_map']:'');



	    	Yii::app()->functions->updateOptionAdmin("theme_menu_colapse",

	    	isset($this->data['theme_menu_colapse'])?$this->data['theme_menu_colapse']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_top_menu",

	    	isset($this->data['theme_top_menu'])?json_encode($this->data['theme_top_menu']):'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("show_language",

	    	isset($this->data['show_language'])?$this->data['show_language']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_promo_tab",

	    	isset($this->data['theme_promo_tab'])?$this->data['theme_promo_tab']:'');



	    	Yii::app()->functions->updateOptionAdmin("merchant_tbl_book_disabled",

	    	isset($this->data['merchant_tbl_book_disabled'])?$this->data['merchant_tbl_book_disabled']:'');



	    	Yii::app()->functions->updateOptionAdmin("theme_hours_tab",

	    	isset($this->data['theme_hours_tab'])?$this->data['theme_hours_tab']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_reviews_tab",

	    	isset($this->data['theme_reviews_tab'])?$this->data['theme_reviews_tab']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_map_tab",

	    	isset($this->data['theme_map_tab'])?$this->data['theme_map_tab']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_info_tab",

	    	isset($this->data['theme_info_tab'])?$this->data['theme_info_tab']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_photos_tab",

	    	isset($this->data['theme_photos_tab'])?$this->data['theme_photos_tab']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("cookie_law_enabled",

	    	isset($this->data['cookie_law_enabled'])?$this->data['cookie_law_enabled']:'');

	    	Yii::app()->functions->updateOptionAdmin("cookie_accept_text",

	    	isset($this->data['cookie_accept_text'])?$this->data['cookie_accept_text']:'');

	    	Yii::app()->functions->updateOptionAdmin("cookie_info_text",

	    	isset($this->data['cookie_info_text'])?$this->data['cookie_info_text']:'');

	    	Yii::app()->functions->updateOptionAdmin("cookie_msg_text",

	    	isset($this->data['cookie_msg_text'])?$this->data['cookie_msg_text']:'');

	    	Yii::app()->functions->updateOptionAdmin("cookie_info_link",

	    	isset($this->data['cookie_info_link'])?$this->data['cookie_info_link']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_search_merchant_name",

	    	isset($this->data['theme_search_merchant_name'])?$this->data['theme_search_merchant_name']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_search_street_name",

	    	isset($this->data['theme_search_street_name'])?$this->data['theme_search_street_name']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_search_cuisine",

	    	isset($this->data['theme_search_cuisine'])?$this->data['theme_search_cuisine']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_search_foodname",

	    	isset($this->data['theme_search_foodname'])?$this->data['theme_search_foodname']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_compression",

	    	isset($this->data['theme_compression'])?$this->data['theme_compression']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_search_merchant_address",

	    	isset($this->data['theme_search_merchant_address'])?$this->data['theme_search_merchant_address']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_lang_pos",

	    	isset($this->data['theme_lang_pos'])?$this->data['theme_lang_pos']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_hide_footer_section1",

	    	isset($this->data['theme_hide_footer_section1'])?$this->data['theme_hide_footer_section1']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_hide_footer_section2",

	    	isset($this->data['theme_hide_footer_section2'])?$this->data['theme_hide_footer_section2']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("theme_time_pick",

	    	isset($this->data['theme_time_pick'])?$this->data['theme_time_pick']:'');

	    	

			$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}

		

		public function adminMollieSettings()

		{

			Yii::app()->functions->updateOptionAdmin("admin_mol_enabled",

	    	isset($this->data['admin_mol_enabled'])?$this->data['admin_mol_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mol_mode",

	    	isset($this->data['admin_mol_mode'])?$this->data['admin_mol_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mollie_apikey_sanbox",

	    	isset($this->data['admin_mollie_apikey_sanbox'])?$this->data['admin_mollie_apikey_sanbox']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mollie_apikey_live",

	    	isset($this->data['admin_mollie_apikey_live'])?$this->data['admin_mollie_apikey_live']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_mol_locale",

	    	isset($this->data['admin_mol_locale'])?$this->data['admin_mol_locale']:'');

	    	

			$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}	

			

	    public function merchantMollieSettings()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();



            Yii::app()->functions->updateOption("merchant_mol_mode",

	    	isset($this->data['merchant_mol_mode'])?$this->data['merchant_mol_mode']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mol_enabled",

	    	isset($this->data['merchant_mol_enabled'])?$this->data['merchant_mol_enabled']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mol_locale",

	    	isset($this->data['merchant_mol_locale'])?$this->data['merchant_mol_locale']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mollie_apikey_sanbox",

	    	isset($this->data['merchant_mollie_apikey_sanbox'])?$this->data['merchant_mollie_apikey_sanbox']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_mollie_apikey_live",

	    	isset($this->data['merchant_mollie_apikey_live'])?$this->data['merchant_mollie_apikey_live']:''

	    	,$merchant_id);

	    	

			$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}			

		

		public function adminIpay88Settings()

		{

			Yii::app()->functions->updateOptionAdmin("admin_ip8_enabled",

	    	isset($this->data['admin_ip8_enabled'])?$this->data['admin_ip8_enabled']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_ip8_mode",

	    	isset($this->data['admin_ip8_mode'])?$this->data['admin_ip8_mode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_ip8_merchantcode",

	    	isset($this->data['admin_ip8_merchantcode'])?$this->data['admin_ip8_merchantcode']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_ip8_merchantkey",

	    	isset($this->data['admin_ip8_merchantkey'])?$this->data['admin_ip8_merchantkey']:'');

	    	

	    	Yii::app()->functions->updateOptionAdmin("admin_ip8_language",

	    	isset($this->data['admin_ip8_language'])?$this->data['admin_ip8_language']:'');

	    	

			$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}	

       

		public function merchantIpay88Settings()

		{

			$merchant_id=Yii::app()->functions->getMerchantID();

			

            Yii::app()->functions->updateOption("merchant_ip8_mode",

	    	isset($this->data['merchant_ip8_mode'])?$this->data['merchant_ip8_mode']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_ip8_enabled",

	    	isset($this->data['merchant_ip8_enabled'])?$this->data['merchant_ip8_enabled']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_ip8_merchantcode",

	    	isset($this->data['merchant_ip8_merchantcode'])?$this->data['merchant_ip8_merchantcode']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOption("merchant_ip8_merchantkey",

	    	isset($this->data['merchant_ip8_merchantkey'])?$this->data['merchant_ip8_merchantkey']:''

	    	,$merchant_id);

	    	

	    	Yii::app()->functions->updateOptionAdmin("merchant_ip8_language",

	    	isset($this->data['merchant_ip8_language'])?$this->data['merchant_ip8_language']:'');

	    	

			$this->code=1;

	    	$this->msg=Yii::t("default","Setting saved");

		}	

			

	} /*END AjaxAdmin*/			

}/* END CLASS*/