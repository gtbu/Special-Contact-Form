<?php

namespace Addon\SCF;

defined('is_running') or die('Not an entry point...');

class Form{

	protected $template;


	public $filename;
	public $keypublic;
	public $keyprivate;
	public $data;
	public $items;
	public $lang		= 'en'; //user interface language

	function __construct(){
		global $config, $addonPathCode, $addonPathData;

		\gpFiles::CheckDir($addonPathData);

		$this->template		= $addonPathData.'/template.php';
		$this->formname		= $addonPathData.'/contact_form.php';
		$this->keypublic	= \common::ConfigValue('recaptcha_public','');
		$this->keyprivate	= \common::ConfigValue('recaptcha_private','');

		//load language
		$this->load_language();
		if( file_exists($addonPathData.'/config.php')){
			include($addonPathData.'/config.php');
			$this->items = $items;
			$this->data = $data;
			unset($items);
			unset($data);
		}else{
			$this->load_default_config();
		}
	}

	/**
	 * Get language values
	 *
	 */
	function load_language(){
		global $config,$addonPathCode,$addonPathData;

		if( file_exists($addonPathData.'/language.txt')){
			$this->lang = file_get_contents($addonPathData.'/language.txt');
		}elseif( file_exists($addonPathCode.'/language/lang_'.$config['language'].'.php')){
			$this->lang = $config['language'];
		}

		if( isset($_GET['iLanguage']) && $this->lang != $_GET['iLanguage']){
			if( file_exists($addonPathCode.'/language/lang_'.$_GET['iLanguage'].'.php')){
				$this->lang = $_GET['iLanguage'];
				file_put_contents($addonPathData.'/language.txt',$this->lang);
			}
		}
		include($addonPathCode.'/language/lang_'.$this->lang.'.php');
	}


	/**
	 * Get the default configuration
	 *
	 */
	function load_default_config(){
		global $config,$addonPathCode,$addonPathData;
		//default values
		$this->items = array(
				 "1" => array( "label"=>$this->SCF_LANG['your_name'],  "type"=>"input", "multi_values"=>"", "valid"=>"req,minlength=2,maxlength=50" )
				,"2" => array( "label"=>$this->SCF_LANG['your_phone'], "type"=>"input", "multi_values"=>"", "valid"=>"maxlength=30" )
				,"3" => array( "label"=>$this->SCF_LANG['your_email'], "type"=>"input", "multi_values"=>"", "valid"=>"req,email" )
				,"4" => array( "label"=>$this->SCF_LANG['subject'],    "type"=>"select", "multi_values"=>"Appointment,Acknowledgment,Complaint", "valid"=>"req,minlength=2" )
				,"5" => array( "label"=>$this->SCF_LANG['message'],    "type"=>"textarea", "multi_values"=>"", "valid"=>"req" )
				,"6" => array( "label"=>$this->SCF_LANG['file'],       "type"=>"file",  "multi_values"=>"", "valid"=>"" ) ); // default items
		$this->data = array(
				 "id_sendername" => "1"
				,"id_senderemail" => "3"
				,"id_sendersubject" => "4"
				,"id_sendermessage" => "5"
				,"sendcopytosender" => false
				,"message_ta_params" => 'cols="50" rows="5" style="height:20em; width:98%; border:1px solid #ccc;"'
				,"Math" => 31
				,"msg_enter_letter" => $this->SCF_LANG['enter_letter']
				,"msg_enter_unique" => $this->SCF_LANG['enter_unique']
				,"Captcha" => array( "rctheme" => "red" )
				,"aspam" => "math"
				,"WordWrap" => 50
				,"EnableCKE" => false
				,"ckValues" => ckDefault
				,"method" => "smtp"
				,"SMTPAuth" => false
				,"SMTPSecure" => ""
				,"Host" => ""
				,"Port" => 25
				,"Language" => (file_exists($addonPathCode.'/language/phpmailer.lang-'.$this->lang.'.php') ? $config['language'] : 'en') // for phpmailer only
				,"CharSet" => "utf-8"
				,"validator_errors" => 2
				,"msg_noscript" => $this->SCF_LANG['msg_noscript']
				,"msg_listing" => $this->SCF_LANG['msg_listing']
				,"msg_success" => $this->SCF_LANG['msg_success']
				,"msg_fail" => $this->SCF_LANG['msg_fail']
				,"msg_rcerror" => $this->SCF_LANG['msg_rcerror']
				,"msg_presubject" => $this->SCF_LANG['msg_presubject']
				,"msg_sendcopytosender" => $this->SCF_LANG['sendcopytosender'] );
	}

	function Start(){
		global $addonPathData;

		if( file_exists($addonPathData.'/contact_form.php') ){
			include($addonPathData.'/contact_form.php');
			echo '<hr/>';
		}

		$this->create_form();
	}


	function create_form(){
		global $page;
		global $addonRelativeData,$addonPathData,$addonPathCode,$addonRelativeCode,$config,$dataDir,$gp_titles,$gp_index,$addonFolderName;

		if( !file_exists($this->template)){
			echo $this->SCF_LANG['template_none'];
			return;
		}

		if(  ($this->data['aspam']=='capt') && ($this->keypublic=='' || $this->keyprivate=='') ){
			$this->data['aspam']='none';
			echo $this->SCF_LANG['rc_notset'].'<br/>';
		}

		if( $this->data['EnableCKE'] ){
			//check if ckeditor is correctly aligned
			if( file_exists($addonPathData.'/scf_style.css') ){
				$style = file_get_contents($addonPathData.'/scf_style.css');
			}else{
				$style = file_get_contents($addonPathCode.'/scf_style.css');//default
			}

			if( strpos($style,'#cke_item'.$this->data['id_sendermessage']) === false){
				$style .= "\n".'#cke_item'.$this->data['id_sendermessage'].' { float:left; }';
				file_put_contents($addonPathData.'/scf_style.css',$style);
			}
			unset($style);
		}

		// ******************************* template **********************
		$t = $this->getfile($this->template,1); //get second splitted part
		$x = explode('[NUMBERS]',$t,2);
		if( ($this->data['aspam']=='math') && ($this->data['Math']!=0) && (count($x)>1)){ //if enabled and [NUMBERS] string was found

			$t = $x[0].'<'.'?'.'php ';
			$t .= 'if( $op==\'+\') echo $n1.\' + \'.$n2; ';
			$t .= 'if( $op==\'-\') echo $n1+$n2.\' - \'.$n1; ';
			$t .= 'if( $op==\'*\') echo $n1.\' * \'.$n2; ';
			$t .= 'if( $op==\'a\'){ $tempstring=\''.str_replace('\'','\\\'',$this->data['msg_enter_letter']).'\'; echo \' \'.str_replace(array(\'%a\',\'%c\'),array(chr($n-1),chr($n+1)),$tempstring); } ';
			$t .= 'if( $op==\'b\'){ echo \''.str_replace('\'','\\\'',$this->data['msg_enter_unique']).' \'; $rc=65+rand()%26; if($rc==$n) $rc=($n==65?90:65); $pos=1+rand()%5; for($rs=0;$rs<7;$rs++) echo ($rs==$pos)?chr($n):chr($rc); } ';
			$t .= '?'.'>'.$x[1];
			$disablemathchecking=false;
		}else{
			$t = $x[0].(isset($x[1])?$x[1]:'');
			$disablemathchecking=true;
		}

		$x = explode('[CAPTCHA]',$t,2);
		if( count($x)>1){ //if [CAPTCHA] string was found

			if( $this->data['aspam']=='capt'){ //if captcha is enabled

				$t = $x[0].'<'.'?'.'php ';
				$t .= ' requi'.'re_once($dataDir.\'/include/thirdparty/recaptchalib.php\');
						$publickey = \common::ConfigValue(\'recaptcha_public\',\'\');
						echo recaptcha_get_html($publickey);';
				$t .= '?'.'>'.$x[1];
			}else{
				$t = $x[0].(isset($x[1])?$x[1]:''); // this removes the [CAPTCHA] string from template
			}
		}
		if( $this->data['EnableCKE']){
			$t .= ' <script type="text/javascript">
				CKEDITOR.replace( \'item'.$this->data['id_sendermessage'].'\',
				{
					'.$this->data['ckValues'].'
				});
				</script> ';
		}

		// ******************************* begin *************************
		\gpPlugin::js('form_validator.min.js');

		$recaptcha_options				= array();
		$recaptcha_options['lang']		= \common::ConfigValue('recaptcha_language','');
		$recaptcha_options['theme']		= $this->data['Captcha']['rctheme'];
		$page->jQueryCode				.= 'var RecaptchaOptions = '.json_encode($recaptcha_options).';';
		$rhc_string						= 'Antispam test passed!'; // REVERSE HONEYPOT CAPTCHA OK STRING ~ Antispam test passed! Humanity confirmed! :-)'
		$begin							= '';


		if( file_exists($addonPathData.'/scf_style.css') ){
			$page->css_user[] = $addonRelativeData.'/scf_style.css';
		}else{
			$page->css_user[] = $addonRelativeCode.'/scf_style.css';
		}

		$begin .= '<div class="simplecontactform">';
		$begin .= '<noscript><p>'.($this->data['msg_noscript']).'</p></noscript>';


		if( $this->data['aspam']=='capt'){
			$begin .= 'if( isset($_POST["submitForm"])){';
			$begin .= ' requi'.'re_once($dataDir.\'/include/thirdparty/recaptchalib.php\');';
			$begin .= ' $privatekey = \common::ConfigValue(\'recaptcha_private\',\'\');';
			$begin .= ' $resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);';
			$z = explode('[ERRORCODE]',$this->data['msg_rcerror'],2);
			$begin .= ' if( !$resp->is_valid){';
			$begin .= '  echo "'.$z[0].'".$resp->error."'.(isset($z[1])?$z[1]:'').'";';
			$begin .= ' }';
			$begin .= '}';
			$begin .= 'if( isset($_POST["submitForm"]) && $_POST["url"]==\'\' && $resp->is_valid){';
		}elseif( $this->data['aspam']=='math'){
			$begin .= 'if( isset($_POST["submitForm"]) && $_POST["url"]==\'\'){';
		}elseif( $this->data['aspam']=='rhcapt'){
			$begin .= 'if( isset($_POST["submitForm"]) && $_POST["url"]==$rhc_string){';
		}else{ // no antispam protection
			$begin .= 'if( isset($_POST["submitForm"])){';
		}

		$eol = $this->data['EnableCKE']? '\'<br/>\'' : '"\n"';
		$b1 = $this->data['EnableCKE']? '\'<b>\'.' : '';
		$b2 = $this->data['EnableCKE']? '.\'</b>\'' : '';
		$begin .= ' requi'.'re_once($addonPathCode.\'/class.phpmailer.php\'); ';
		$begin .= ' $mail = new PHPMailer();';
		$begin .= ' $mail->SetLanguage("'.$this->data['Language'].'");';
		$begin .= ' $mail->IsHTML('.($this->data['EnableCKE']?'true':'false').');';
		$begin .= ' $mail->WordWrap = '.$this->data['WordWrap'].';';
		$begin .= ' $mail->CharSet = "'.$this->data['CharSet'].'";';
		if( $this->data['method']=='smtp'){
			$begin .= ' $mail->IsSMTP();'; // we use the SMTP server to send e-mail
			$begin .= ' $mail->SMTPDebug = false;';
			$begin .= ' $mail->SMTPAuth = '.($this->data['SMTPAuth']?'true':'false').';';
			if( $this->data['SMTPAuth']=='true'){
				$begin .= ' $mail->SMTPSecure="'.$this->data['SMTPSecure'].'";';
				$begin .= ' $mail->Host="'.($this->data['Host']).'";';
				$begin .= ' $mail->Port="'.($this->data['Port']).'";';
				$begin .= ' $mail->Username=$config[\'smtp_user\'];';
				$begin .= ' $mail->Password=$config[\'smtp_pass\'];';
			}
		}elseif( $this->data['method']=='mail'){
			$begin .= ' $mail->IsMail();'; // we use the mail() function to send e-mail
		}elseif( $this->data['method']=='sendmail'){
			$begin .= ' $mail->IsSendmail();'; // we use the Sendmail program to send e-mail
			$begin .= ' if( $config[\'sendmail_path\']!=\'\')';
			$begin .= '  $mail->Sendmail = $config[\'sendmail_path\'];';
		}
		if( $this->data['msg_listing']!=''){
			$begin .= ' echo \''.str_replace('\'','\\\'', $this->data['msg_listing']).'<br/>\';';

		foreach ($this->items as $i => $value){
			$begin .= ' echo \'<b>'.$value['label'].':</b> \';';
			if( $value['type']=='input' || $value['type']=='radio' || $value['type']=='select' || $value['type']=='textarea'){
				$begin .= ' if( isset($_POST[\'item'.$i.'\']))';
				$begin .= ' {';
				$begin .= '  echo $_POST[\'item'.$i.'\'].\'<br/>\';';
				$begin .= ' }';
				$begin .= ' else';
				$begin .= '  echo \'-<br/>\';';
			}
			if( $value['type']=='checkbox'){
				$begin .= ' if( isset($_POST[\'item'.$i.'\']))';
				$begin .= '  echo \''.$this->SCF_LANG['checked'].'<br/>\';';
				$begin .= ' else';
				$begin .= '  echo \''.$this->SCF_LANG['unchecked'].'<br/>\';';
			}
			if( $value['type']=='file'){
				$begin .= ' if( $_FILES[\'item'.$i.'\'][\'name\']!=\'\')';
				$begin .= '  echo \'<i>\'.$_FILES[\'item'.$i.'\'][\'name\'].\'</i>\';';
				$begin .= ' else';
				$begin .= '  echo \'-\';';
				$begin .= ' if( $_FILES[\'item'.$i.'\'][\'error\']!=0 && $_FILES[\'item'.$i.'\'][\'error\']!=4)';
				$begin .= '  echo \' <i>error</i> <a href="http://php.net/manual/en/features.file-upload.errors.php" target="_blank">\'.$_FILES[\'item'.$i.'\'][\'error\'].\'</a>\';';
				$begin .= ' echo \' <br/>\';';
			}
		}
		if( $this->data['sendcopytosender']){
			$begin .= ' if( isset($_POST[\'sendcopytosender\']))';
			$begin .= '  echo \'<b>'.$this->data['msg_sendcopytosender'].'</b>: '.$this->SCF_LANG['checked'].'<br/>\';';
			$begin .= ' else';
			$begin .= '  echo \'<b>'.$this->data['msg_sendcopytosender'].'</b>: '.$this->SCF_LANG['unchecked'].'<br/>\';';
		}
		}//end of listing
		$begin .= "\n";


		// validate posted fields - on server
		$begin .= ' $send = true;';
		if( strpos($this->items[$this->data['id_sendername']]['valid'],'req')!==false){
			$begin .= ' if( !isset($_POST["item'.$this->data['id_sendername'].'"]) || $_POST["item'.$this->data['id_sendername'].'"]==\'\')';
			$begin .= ' {';
			$begin .= '  printf($langmessage[\'OOPS_REQUIRED\'],\''.$this->items[$this->data['id_sendername']]['label'].'\');';
			$begin .= '  echo \'<br/>\';';
			$begin .= '  $send = false;';
			$begin .= ' }';
		}
		if( strpos($this->items[$this->data['id_senderemail']]['valid'],'email')!==false){
			$begin .= ' if( !isset($_POST["item'.$this->data['id_senderemail'].'"]) || $_POST["item'.$this->data['id_senderemail'].'"]==\'\' ';
			$begin .= '  || !preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $_POST["item'.$this->data['id_senderemail'].'"]))';
			$begin .= ' {';
			$begin .= '  echo $langmessage[\'invalid_email\'].\'<br/>\';';
			$begin .= '  $send = false;';
			$begin .= ' }';
		}
		if( strpos($this->items[$this->data['id_sendersubject']]['valid'],'req')!==false){
			$begin .= ' if( !isset($_POST["item'.$this->data['id_sendersubject'].'"]) || $_POST["item'.$this->data['id_sendersubject'].'"]==\'\')';
			$begin .= ' {';
			$begin .= '  printf($langmessage[\'OOPS_REQUIRED\'],\''.$this->items[$this->data['id_sendersubject']]['label'].'\');';
			$begin .= '  echo \'<br/>\';';
			$begin .= '  $send = false;';
			$begin .= ' }';
		}
		//message will be always checked for emptiness, should be written :
		//if( strpos($this->items[$this->data['id_sendermessage']]['valid'],'req')!==false)
		//{
			$begin .= ' if( !isset($_POST["item'.$this->data['id_sendermessage'].'"]) || $_POST["item'.$this->data['id_sendermessage'].'"]==\'\')';
			$begin .= ' {';
			$begin .= '  printf($langmessage[\'OOPS_REQUIRED\'],\''.$this->items[$this->data['id_sendermessage']]['label'].'\');';
			$begin .= '  echo \'<br/>\';';
			$begin .= '  $send = false;';
			$begin .= ' }';
		//}
		$begin .= ' if( !$send)';
		$begin .= ' {';
		$begin .= '  echo \'<br/><b>'.$this->SCF_LANG['message_not_sent'].'</b> <a href="\'.\common::GetUrl($title).\'" target="_blank">\'.\common::GetLabel($title).\'</a></div>\';';
		$begin .= '  return;';
		$begin .= ' }';
		$begin .= ' $_name = $_POST["item'.$this->data['id_sendername'].'"];';
		$begin .= ' $_email = $_POST["item'.$this->data['id_senderemail'].'"];';
		$begin .= ' $_subject = $_POST["item'.$this->data['id_sendersubject'].'"];';
		$begin .= ' $_message = $_POST["item'.$this->data['id_sendermessage'].'"];';
		$begin .= ' $_body = \'\';';
		$begin .= "\n";

		foreach ($this->items as $i => $value){
			if( $value['type']!='file'){
				$begin .= '	$_body .= '.$b1.'\''.$value['label'].'\''.$b2.'.\': \';';
			}
			if( $value['type']=='input' || $value['type']=='radio' || $value['type']=='select' || $value['type']=='textarea'){
				$begin .= '	if( isset($_POST[\'item'.$i.'\']))';
				$begin .= '	{';
			//	if( $value['type']=='textarea' && !$this->data['EnableCKE'])
			//		$begin .= '		$_POST[\'item'.$i.'\'] = str_replace("\n","<br/>\n",$_POST[\'item'.$i.'\']);';
				$begin .= '		$_body .= $_POST[\'item'.$i.'\'].'.$eol.';';
				$begin .= '	}';
				$begin .= '	else';
				$begin .= '		$_body .= \'-\'.'.$eol.';';
			}
			if( $value['type']=='checkbox'){
				$begin .= '	if( isset($_POST[\'item'.$i.'\']))';
				$begin .= '		$_body .= \''.$this->SCF_LANG['checked'].'\'.'.$eol.';';
				$begin .= '	else';
				$begin .= '		$_body .= \''.$this->SCF_LANG['unchecked'].'\'.'.$eol.';';
			}
			if( $value['type']=='file'){ //attachment

				$begin .= '	if( $_FILES[\'item'.$i.'\'][\'error\']==0)';
				$begin .= '		$mail->AddAttachment($_FILES[\'item'.$i.'\'][\'tmp_name\'],$_FILES[\'item'.$i.'\'][\'name\']); // attachment';
			}
		}
		if( $this->data['sendcopytosender']){
			$begin .= '	if( isset($_POST[\'sendcopytosender\']))';
			$begin .= '		$_body .= '.$eol.'.'.$b1.'\''.$this->data['msg_sendcopytosender'].'\''.$b2.'.\': '.$this->SCF_LANG['checked'].'\'.'.$eol.';';
			$begin .= '	else';
			$begin .= '		$_body .= '.$eol.'.'.$b1.'\''.$this->data['msg_sendcopytosender'].'\''.$b2.'.\': '.$this->SCF_LANG['unchecked'].'\'.'.$eol.';';
		}
		$begin .= '			$mail->AddAddress($config[\'toemail\'],$config[\'toname\']); // e-mail address of receiver';
		$begin .= '			//$mail->AddAddress("another.mail@another.address.com");  // e-mail address of another receiver..... ';
		if( $this->data['sendcopytosender']){
			$begin .= '			if( isset($_POST[\'sendcopytosender\']))';
			$begin .= '			{';
			$begin .= '				$mail->AddBCC($_email,$_name); // blind carbon copy for sender';
			$begin .= '			}';
			$begin .= '			else';
			$begin .= '			{';
			$begin .= '				$mail->AddReplyTo($_email,$_name);';
			$begin .= '			}';
		}
		$begin .= '			$mail->FromName = $_name; // Sender\'s full name';
		$begin .= '			$mail->From = $_email; // sender\'s e-mail address';
		$begin .= '			$mail->Return = $_email; // if email will not be delivered, notice will return here';
		$begin .= '			$mail->Subject = \''.str_replace('\'','\\\'', $this->data['msg_presubject']).'\'.$_subject;';
		$begin .= '			$mail->Body .= $_body;';
		$begin .= '			$sent = $mail->Send();//via smtp or phpmail';
		$begin .= '			if($sent)';
		$begin .= '				{echo \'<br/><br/>'.str_replace('\'','\\\'', $this->data['msg_success']).'\';}';
		$begin .= '			else';
		$begin .= '				{echo \'<br/><br/>'.str_replace('\'','\\\'', $this->data['msg_fail']).'\';}';
		$begin .= '			echo \'<br/><br/>\';';
		$begin .= '	  }';
		if( $this->data['aspam']=='rhcapt'){
			// test reverse honeypot captcha
			$begin .= '	if( isset($_POST["submitForm"]) && $_POST[\'url\']!=$rhc_string)'; // if antispam field is not ok
			$begin .= '		{ echo \''.$this->SCF_LANG['spam_detected'].' (Reverse Honeypot Captcha)\';} ';
		}elseif( $this->data['aspam']=='math' || $this->data['aspam']=='capt'){
			// test ordinary honeypot captcha
			$begin .= '	if( isset($_POST["submitForm"]) && $_POST[\'url\']!=\'\')';
			$begin .= '		{ echo \''.$this->SCF_LANG['spam_detected'].' (Honeypot Captcha)\';} ';
		}
		if( ($this->data['aspam']=='math') && $this->data['Math']!=0){
			$op = ($this->data['Math']&1?'+':'').($this->data['Math']&2?'-':'').($this->data['Math']&4?'*':'').($this->data['Math']&8?'a':'').($this->data['Math']&16?'b':'');
			$begin .= '	$op=\''.$op.'\';';
			$begin .= '	$op=$op[(rand()%strlen($op))];';
			$begin .= '	if( $op==\'a\' || $op==\'b\'){ $n = 66+rand()%24; } else { $n1 = rand()%10; $n2 = rand()%10; }';
			$begin .= '	';
		}
		$begin .= '	?'.'>';

		$begin .= '		<div id="scf_jsContentWrapper" style="display:none;">';

		if( $this->data['EnableCKE']){
			$begin .= '<script type="text/javascript" src="'. \common::GetDir('/include/thirdparty/ckeditor_34/ckeditor.js') .'"></script>';
		}

		// ******************************* end ***************************

		$end = '';
		$end .= '</div>'; //end of "scf_jsContentWrapper" division

		$end .= '<'.'?'.'php'.'if( !isset($_POST["submitForm"]))'; // don't display the form after the sending
		$end .= '{';
		$end .= 'global $page;';
		$end .= '$page->jQueryCode .= "$(\'#scf_jsContentWrapper\').removeAttr(\'style\');";';
		$end .= '}';
		$end .= '?'.'>';

		$end .= '	<script type="text/javascript">';
		$end .= '		var frmvalidator  = new Validator("special_contact_form");';
		if( $this->data['validator_errors']==1)
			$end .= '		frmvalidator.EnableOnPageErrorDisplaySingleBox();';
		else
			$end .= '		frmvalidator.EnableOnPageErrorDisplay();';
		foreach ($this->items as $i => $value){
			if( $value['valid']=='')
				continue;
			$v = explode(',',$value['valid']);
			foreach ($v as $vv){
				$validation = trim($vv);
				//if( $value['type']=='select' && $vv=='')
				//	$vv=' ';
				if( strpos($validation,'req')!==false)
					$validation_message = $this->SCF_LANG['valid_req'].' - '.$value['label'];
				if( strpos($validation,'minlen')!==false)
					$validation_message = $this->SCF_LANG['valid_short'].' - '.$value['label'];
				if( strpos($validation,'maxlen')!==false)
					$validation_message = $this->SCF_LANG['valid_long'].' - '.$value['label'];
				if( strpos($validation,'email')!==false)
					$validation_message = $this->SCF_LANG['valid_email'];
				$end .= '		frmvalidator.addValidation("item'.$i.'","'.$validation.'","'.$validation_message.'");';
			}
		}
		if( $this->data['aspam']=='rhcapt'){
			$end .= '		$(\'form[name="special_contact_form"] input[type="submit"]\').hover(function(){ this.form.url.value=\'<?'.'ph'.'p echo $rhc_string; ?'.'>\'; },function(){}); // confirm humanity';
			$end .= '		$(\'form[name="special_contact_form"]\').keypress( function(evt){ return !(evt.which==13 && evt.target.type!=\'textarea\'); }); // prevent submiting by enter';
			$end .= '		$(\'form[name="special_contact_form"] input[type="submit"]\').attr(\'tabIndex\',-1); // prevent submiting by spacebar (keyCode==32)';
			//$('#item2').click(function(){ this.form.submit(); }); //testing when mouseover event is not fired
		}else{ // == 'math' or 'capt' or 'none'

			// url - must be empty - this is another anti spam check = direct honeypot captcha. (is this implemented correctly?)
			$end .= '		frmvalidator.addValidation("url","maxlen=0","'.$this->SCF_LANG['valid_empty'].'");';
		}
		// check
		if( $disablemathchecking==false){
			$end .= '			frmvalidator.addValidation("check","req","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '		<?'.'php if( $op==\'+\') echo \'';
			$end .= '			frmvalidator.addValidation("check","numeric","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '			frmvalidator.addValidation("check","greaterthan=\'.($n1+$n2-1).\'","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '			frmvalidator.addValidation("check","lessthan=\'.($n1+$n2+1).\'","'.$this->SCF_LANG['valid_antispam'].'");\';';
			$end .= '		?>';
			$end .= '		<?'.'php if( $op==\'-\') echo \'';
			$end .= '			frmvalidator.addValidation("check","numeric","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '			frmvalidator.addValidation("check","greaterthan=\'.($n2-1).\'","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '			frmvalidator.addValidation("check","lessthan=\'.($n2+1).\'","'.$this->SCF_LANG['valid_antispam'].'");\';';
			$end .= '		?>';
			$end .= '		<?'.'php if( $op==\'*\') echo \'';
			$end .= '			frmvalidator.addValidation("check","numeric","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '			frmvalidator.addValidation("check","greaterthan=\'.($n1*$n2-1).\'","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '			frmvalidator.addValidation("check","lessthan=\'.($n1*$n2+1).\'","'.$this->SCF_LANG['valid_antispam'].'");\';';
			$end .= '		?>';
			$end .= '		<?'.'php if( $op==\'a\' || $op==\'b\') echo \'';
			$end .= '			frmvalidator.addValidation("check","alpha","'.$this->SCF_LANG['valid_antispam'].'");';
			$end .= '			frmvalidator.addValidation("check","regexp=[\'.chr($n).\']|[\'.chr($n+32).\']","'.$this->SCF_LANG['valid_antispam'].'");\';';
			$end .= '		?>';
		}
		$end .= '	</script>';
		$end .= '</div>';
		$str='<'.'?'.'php defined(\'is_running\') or die(\'Not an entry point...\');
 ?'.'>';


		echo '<textarea style="width:100%;height:400px">';
		echo htmlspecialchars( $str. $begin.$t.$end );
		echo '</textarea>';

	}



	function getfile($filename,$part){
		if( !file_exists($filename))
			return '';
		$string = file_get_contents($filename);
		$content = explode('?'.'>',$string,2);
		if( $part==0) return $content[0];
		if( $part==1) return $content[1];
		if( $part==2) return $content;//array
	}

}


