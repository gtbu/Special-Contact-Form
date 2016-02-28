<?php
defined('is_running') or die('Not an entry point...');

define('ckDefault', "toolbar : \n[\n['Bold', 'Italic', 'Underline', '-', 'Undo', 'Redo', '-', 'NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', '-',\n'Format', '-', 'Link', '-', 'About']\n],\ntoolbarLocation : 'bottom',\ntoolbarStartupExpanded:'true',\nuiColor : '#eeeeee',\nheight:'12em'\n");

class Edit
{
	public $filename;
	public $keypublic;
	public $keyprivate;
	public $data;
	public $items;
	public $lang; //user interface language
	
	function __construct()
	{
		global $config,$addonPathCode,$addonPathData;
		if (!file_exists($addonPathData))
			gpFiles::CheckDir($addonPathData);
		$this->template = $addonPathData.'/template.php';
		$this->formname = $addonPathData.'/contact_form.php';
		$this->keypublic = common::ConfigValue('recaptcha_public','');
		$this->keyprivate = common::ConfigValue('recaptcha_private','');
		
		//load language
		$this->load_language();
		if (file_exists($addonPathData.'/config.php'))
		{
			include($addonPathData.'/config.php');
			$this->items = $items;
			$this->data = $data;
			unset($items);
			unset($data);
		}
		else
		{
			$this->load_default_config();
		}
	}
	
	function load_language() //interface language
	{
		global $config,$addonPathCode,$addonPathData;
		$this->lang = 'en';
		if (file_exists($addonPathData.'/language.txt'))
			$this->lang = file_get_contents($addonPathData.'/language.txt');
		elseif (file_exists($addonPathCode.'/language/lang_'.$config['language'].'.php'))
			$this->lang = $config['language'];
		if (isset($_GET['iLanguage']) && $this->lang != $_GET['iLanguage'])
		{
			if (file_exists($addonPathCode.'/language/lang_'.$_GET['iLanguage'].'.php'))
			{
				$this->lang = $_GET['iLanguage'];
				file_put_contents($addonPathData.'/language.txt',$this->lang);
			}
		}
		include($addonPathCode.'/language/lang_'.$this->lang.'.php');
	}
	
	function load_default_config()
	{
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
	
	function getfile($filename,$part)
	{
		if (!file_exists($filename))
			return '';
		$string = file_get_contents($filename);
		$content = explode('?'.'>',$string,2);
		if ($part==0) return $content[0];
		if ($part==1) return $content[1];
		if ($part==2) return $content;//array
	}
	
	function menu()
	{
		global $config, $addonRelativeCode,$page,$langmessage,$languages, $addonPathCode,$addonPathData,$title;
		$page->head_js[] = $addonRelativeCode.'/jquery.tablednd.0.7.min.js';
		$page->head_js[] = $addonRelativeCode.'/javascript.js';
		$page->head .= '<style type="text/css"> #dataTable tbody tr:hover > td {background-color:#7fff7f;} </style>';
		
		echo '<div style="float:right">'.$langmessage['language'];
		$this->Select_Languages();
		echo '</div>';
		
		echo '<div style="font-size:16px; margin-bottom:1.5em;">'.$langmessage['Settings'].'</div>'.PHP_EOL;
		
/*begin 1*/	echo '<div onclick="$(this).next(\'div\').toggle()" style="cursor:pointer"><b> 1. </b>'.$this->SCF_LANG['form_fields'].'</div>';
		echo '<div style="display:none"><br/>';
		echo '<form action="'.common::GetUrl($title).'" method="post">'.PHP_EOL;
		
		echo '<table id="dataTable" class="dataTable" cellspacing="1" cellpadding="1" style="width:97%;border:1px solid black">';
		echo '<colgroup>
				<col style="width:10%"/>
				<col style="width:5%"/>
				<col style="width:10%"/>
				<col style="width:10%"/>
				<col style="width:34%"/>
				<col style="width:34%"/>
			</colgroup>';
		echo '<thead><tr><td><input type="button" value="'.$langmessage['add'].'" onclick="addRow(\'dataTable\',\''.$langmessage['delete'].'\',\''.$this->SCF_LANG['item'].'\',\''.$this->SCF_LANG['input'].'\',\''.$this->SCF_LANG['checkbox'].'\',\''.$this->SCF_LANG['radiobox'].'\',\''.$this->SCF_LANG['selectbox'].'\',\''.$this->SCF_LANG['textarea'].'\',\''.$this->SCF_LANG['file'].'\')" /></td>
			<td style="color:blue"> ID </td><td>'.$langmessage['label'].'</td><td>'.$this->SCF_LANG['type'].'</td>
			<td><a href="'.$addonRelativeCode.'/validations.html" name="ajax_box">'.$this->SCF_LANG['validations'].'</a></td><td>'.$this->SCF_LANG['options'].'</td>
			</tr></thead>';
		echo '<tbody>';
		foreach ($this->items as $i => $value)
		{
			echo '<tr id="row'.$i.'">';
			echo '<td> <input type="button" value="'.$langmessage['delete'].'" onclick="deleteRow(this)" />  </td>'; // add-remove dialog
			echo '<td>'.$i.'</td>';
			echo '<td> <input name="label'.$i.'" style="width:120px;" type="text" value="'.$value['label'].'" /> </td>';
			echo '<td><select name="type'.$i.'" onchange="checkSelectedType(this)" >
				<option value="input"    '.($value['type']=='input'?'selected="selected"':'').'>'.$this->SCF_LANG['input'].'</option>
				<option value="checkbox" '.($value['type']=='checkbox'?'selected="selected"':'').'>'.$this->SCF_LANG['checkbox'].'</option>
				<option value="radio"    '.($value['type']=='radio'?'selected="selected"':'').'>'.$this->SCF_LANG['radiobox'].'</option>
				<option value="select"   '.($value['type']=='select'?'selected="selected"':'').'>'.$this->SCF_LANG['selectbox'].'</option>
				<option value="textarea" '.($value['type']=='textarea'?'selected="selected"':'').'>'.$this->SCF_LANG['textarea'].'</option>
				<option value="file"     '.($value['type']=='file'?'selected="selected"':'').'>'.$this->SCF_LANG['file'].'</option>
				</select>';
			echo '</td>';
			echo '<td> <input name="valid'.$i.'" type="text" value="'.$value['valid'].'" /></td>';
			echo '<td> <input name="multi_values'.$i.'" type="text" value="'.$value['multi_values'].'" '.($value['type']=='radio' || $value['type']=='select' ? '':'style="display:none;"').' /> </td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<input id="maxval" name="maxval" type="hidden" value="'.count($this->items).'" />';
		echo '<br/>';
		echo '<table cellspacing="1" cellpadding="1" style="width:97%;border:1px solid black">';
		echo '<colgroup><col style="width:30%"/><col style="width:10%"/><col style="width:60%"/></colgroup>';
		echo '<tbody>';
		
		echo '<tr>';
		echo '<td>'.$this->SCF_LANG['name_id'].':</td>';
		echo '<td><input name="id_sendername" type="text" value="'.$this->data['id_sendername'].'" /></td>'.PHP_EOL;
		echo '<td>- '.$this->SCF_LANG['must_be_input'].'</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>'.$this->SCF_LANG['email_id'].':</td>';
		echo '<td><input name="id_senderemail" type="text" value="'.$this->data['id_senderemail'].'" /></td>'.PHP_EOL;
		echo '<td>- '.$this->SCF_LANG['must_be_input'].'</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>'.$this->SCF_LANG['subject_id'].':</td>';
		echo '<td><input name="id_sendersubject" type="text" value="'.$this->data['id_sendersubject'].'" /></td>'.PHP_EOL;
		echo '<td>- '.$this->SCF_LANG['must_be_input_select'].'</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>'.$this->SCF_LANG['message_id'].':</td>';
		echo '<td><input name="id_sendermessage" type="text" value="'.$this->data['id_sendermessage'].'" /></td>'.PHP_EOL;
		echo '<td>- '.$this->SCF_LANG['must_be_textarea'].'</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>'.$this->SCF_LANG['ta_params'].'</td>';
		echo '<td><input name="message_ta_params" type="text" value="'.str_replace('"','&quot;',$this->data['message_ta_params']).'" /></td>'.PHP_EOL;
		echo '</tr>';
		
		echo '<tr>';
		echo '<td> <input name="msg_sendcopytosender" type="text" value="'.str_replace('"','&quot;', $this->data['msg_sendcopytosender']).'" /></td>';
		echo '<td> <input name="sendcopytosender1" type="checkbox" '.($this->data['sendcopytosender']?'checked="checked"' : '').'/></td>'.PHP_EOL;
		echo '<td>-</td>'.PHP_EOL;
		echo '</tr>';
		
		echo '</tbody></table><br/>';
		echo '<input type="submit" name="save_fields" value="'.$langmessage['save'].'" />'.PHP_EOL;
/*end1*/	echo '</form></div><br/>';
		
/*begin 2*/	echo '<div onclick="$(this).next(\'div\').toggle()" style="cursor:pointer"><b> 2. </b>'.$this->SCF_LANG['antispam'].'</div>';
		echo '<div style="display:none">';
		echo '<form action="'.common::GetUrl($title).'" method="post"><br/>'.PHP_EOL;
		
		echo '<input id="aspam1" name="aspam" type="radio" value="math" '.($this->data['aspam']=='math'?'checked="checked"':'').'/> <label for="aspam1">'.$this->SCF_LANG['math_pr'].'</label><br/>'.PHP_EOL;
		echo '<input id="aspam2" name="aspam" type="radio" value="capt" '.($this->data['aspam']=='capt'?'checked="checked"':'').'/> <label for="aspam2">'.$this->SCF_LANG['captcha_pr'].'</label><br/>'.PHP_EOL;
		echo '<input id="aspam3" name="aspam" type="radio" value="rhcapt" '.($this->data['aspam']=='rhcapt'?'checked="checked"':'').'/> <label for="aspam3">'.$this->SCF_LANG['rhcaptcha_pr'].'</label><br/>'.PHP_EOL;
		echo '<input id="aspam4" name="aspam" type="radio" value="none" '.($this->data['aspam']=='none'?'checked="checked"':'').'/> <label for="aspam4">'.$langmessage['None'].'</label><br/>'.PHP_EOL;
		
		echo '<p>'.$this->SCF_LANG['math_pr'].'</p>';
		echo '<input id="Math_show1" name="Math_show1" type="checkbox" '.($this->data['Math']&1?'checked="checked"':'').'/> <label for="Math_show1"> A + B </label><br/>';
		echo '<input id="Math_show2" name="Math_show2" type="checkbox" '.($this->data['Math']&2?'checked="checked"':'').'/> <label for="Math_show2"> A - B </label><br/>';
		echo '<input id="Math_show4" name="Math_show4" type="checkbox" '.($this->data['Math']&4?'checked="checked"':'').'/> <label for="Math_show4"> A * B </label><br/>';
		echo '<input id="Math_show8" name="Math_show8" type="checkbox" '.($this->data['Math']&8?'checked="checked"':'').'/> <label for="Math_show8"> AbC </label>';
		echo '<input id="msg_enter_letter" name="msg_enter_letter" type="text" value="'.str_replace('"','&quot;', $this->data['msg_enter_letter']).'" /><br/>'.PHP_EOL;
		echo '<input id="Math_show16" name="Math_show16" type="checkbox" '.($this->data['Math']&16?'checked="checked"':'').'/> <label for="Math_show16"> AACAAAA </label>';
		echo '<input id="msg_enter_unique" name="msg_enter_unique" type="text" value="'.str_replace('"','&quot;', $this->data['msg_enter_unique']).'" /><br/>'.PHP_EOL;
		echo '<p>'.$this->SCF_LANG['captcha_pr'].'</p>';
		echo $this->SCF_LANG['rc_theme'].': <select name="captcha_rctheme">';
		echo '<option value="red"'. ($this->data['Captcha']['rctheme']=='red' ? 'selected="selected"':'') .'> red </option>';
		echo '<option value="white"'. ($this->data['Captcha']['rctheme']=='white' ? 'selected="selected"':'') .'> white </option>';
		echo '<option value="blackglass"'. ($this->data['Captcha']['rctheme']=='blackglass' ? 'selected="selected"':'') .'> blackglass </option>';
		echo '<option value="clean"'. ($this->data['Captcha']['rctheme']=='clean' ? 'selected="selected"':'') .'> clean </option>';
		echo '</select><br/>';
		echo '<span style="color:green">'.$langmessage['recaptcha_public'].':</span> '.$this->keypublic.'<br/>';
		echo '<span style="color:green">'.$langmessage['recaptcha_private'].':</span> '.$this->keyprivate.'<br/>';
		echo '<span style="color:green">'.$langmessage['recaptcha_language'].':</span> '.common::ConfigValue('recaptcha_language','').'<br/><br/>';
		echo '<span style="color:green">'.$this->SCF_LANG['green_settings'].' '.common::Link('Admin_Configuration','&#187;','',' name="admin_box" title="'.$langmessage['configuration'].'"').'</span>';
		echo '<br/><br/>';
		echo '<input type="submit" name="save_antispams" value="'.$langmessage['save'].'" />'.PHP_EOL;
/*end2*/	echo '</form></div><br/>';
		
/*begin 3*/	echo '<div onclick="$(this).next(\'div\').toggle()" style="cursor:pointer"><b> 3. </b>'.$this->SCF_LANG['email_settings'].'</div>';
		echo '<div style="display:none">';
		echo '<form action="'.common::GetUrl($title).'" method="post">'.PHP_EOL;
		echo '<span style="color:green">'.$this->SCF_LANG['will_deliver'].'</span> <input name="Receiver" type="text" value="'.( isset($config["toemail"]) ? str_replace('"','&quot;',$config["toemail"]) : '' ).'" size="25" /> = <input name="ReceiverName" type="text" value="'.( isset($config["toname"]) ? str_replace('"','&quot;',$config["toname"]) : '' ).'" size="25" /> '.common::Link('Admin_Configuration','&#187;','','name="admin_box" title="'.$langmessage['configuration'].'"').'<br/>'.PHP_EOL;
		echo $this->SCF_LANG['wordwrap'].' <input name="WordWrap" type="text" value="'.$this->data['WordWrap'].'" /><br/>'.PHP_EOL;
		echo $this->SCF_LANG['charset'].': <input name="CharSet" type="text" value="'.$this->data['CharSet'].'" /><br/>'.PHP_EOL;
		echo '<a href="http://php.net/manual/en/features.file-upload.errors.php" target="_blank">'.$this->SCF_LANG['max_filesize'].'</a>: '.ini_get('upload_max_filesize').'B<br/><br/>'.PHP_EOL;
		echo $langmessage['mail_method'];
		echo ' : <input id="method1" name="method" type="radio" value="smtp" '.($this->data['method']=='smtp'?'checked="checked"':'').'/> <label for="method1">SMTP server</label> '.PHP_EOL;
		echo '<input id="method2" name="method" type="radio" value="mail" '.($this->data['method']=='mail'?'checked="checked"':'').'/> <label for="method2">function mail()</label> '.PHP_EOL;
		echo '<input id="method3" name="method" type="radio" value="sendmail" '.($this->data['method']=='sendmail'?'checked="checked"':'').'/> <label for="method3">program sendmail</label> <br/>'.PHP_EOL;
		echo $this->SCF_LANG['smtp_use_auth'].' <input name="SMTPAuth" type="checkbox" '.($this->data['SMTPAuth']?'checked="checked"' : '').' /><br/>'.PHP_EOL;
		echo $this->SCF_LANG['smtp_host'].': <input name="Host" type="text" value="'.$this->data['Host'].'" /> '.PHP_EOL;
		echo '+ '.$this->SCF_LANG['smtp_port'].': <input name="Port" type="text" value="'.$this->data['Port'].'" /><br/>'.PHP_EOL;
		echo $this->SCF_LANG['smtp_sec'].': <input name="SMTPSecure" type="text" value="'.$this->data['SMTPSecure'].'" /><br/> '.PHP_EOL;
		echo '<span style="color:green">'.$this->SCF_LANG['smtp_user'].'</span> : <input name="Username" type="text" value="'.(isset($config['smtp_user'])&&$config['smtp_user']!=''?$config['smtp_user']:'-').'" /><br/>'.PHP_EOL;
		echo '<span style="color:green">'.$this->SCF_LANG['smtp_pass'].'</span> : <input name="Password" type="password" value="'.(isset($config['smtp_pass'])?$config['smtp_pass']:'').'" /><br/>'.PHP_EOL;
		echo '<span style="color:green">'.$langmessage['sendmail_path'].'</span> : '.(isset($config['sendmail_path'])?$config['sendmail_path']:$langmessage['default']).'<br/>'.PHP_EOL;
		echo '<div style="clear:both;height:1em;"></div>';
		echo '<input type="submit" name="save_emailsettings" value="'.$langmessage['save'].'" />'.PHP_EOL;
/*end3*/	echo '</form></div><br/>';
		
/*begin 4*/	echo '<div onclick="$(this).next(\'div\').toggle()" style="cursor:pointer"><b> 4. </b>'.$this->SCF_LANG['other'].'</div>';
		echo '<div style="display:none"><br/>';
		echo '<form action="'.common::GetUrl($title).'" method="post">'.PHP_EOL;
		
		echo '<label for="EnableCKE">'.$this->SCF_LANG['ckeditor_enable'].'</label>';
		echo '<input id="EnableCKE" name="EnableCKE" type="checkbox" '.($this->data['EnableCKE']?'checked="checked"' : '').'/> <br/>'.PHP_EOL;
		echo '<a onclick="$(\'#ckdiv\').toggle()" style="cursor:pointer">'.$this->SCF_LANG['ckeditor_settings'].'</a>';
		echo ' ( <a href="http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html" title="CKEditor 3 JavaScript API Documentation" target="_blank">ck info</a> / ';
		echo common::Link('Admin_scf',$this->SCF_LANG['ckeditor_def'],'cmd=set_defaults').' ) <br/>';
		echo '<div id="ckdiv" style="display:none"><textarea id="ckValues" name="ckValues" rows="11" cols="35" wrap="off" style="width:100%">'.$this->data['ckValues'].'</textarea></div><br/>';
		
		echo '<a onclick="$(\'#stylediv\').toggle()" style="cursor:pointer">'.$langmessage['style'].'</a> ( ';
		echo common::Link($title,$this->SCF_LANG['style_restore'],'cmd=style_restore').' ) <br/>'.PHP_EOL;
		echo '<div id="stylediv" style="display:none">';
		echo '<textarea id="cfstyle" name="cfstyle" wrap="off" rows="15" cols="50" style="width:100%">';
		if (file_exists($addonPathData.'/scf_style.css'))
			echo file_get_contents($addonPathData.'/scf_style.css');
		else
			echo file_get_contents($addonPathCode.'/scf_style.css');//default
		echo '</textarea>';
		echo '</div>';
		echo '<div style="clear:both;height:1em;"></div>';
		
		echo '<i>'.$this->SCF_LANG['messages'].'</i><br/><br/>';
		echo '<label for="msg_noscript"> '.$this->SCF_LANG['noscript'].': </label>';
		echo '<input id="msg_noscript" name="msg_noscript" type="text" value="'.str_replace('"','&quot;', $this->data['msg_noscript']).'" style="width:97%" /><br/><br/>'.PHP_EOL;
		echo '<label for="msg_listing"> '.$this->SCF_LANG['listing'].': </label>';
		echo '<input id="msg_listing" name="msg_listing" type="text" value="'.str_replace('"','&quot;', $this->data['msg_listing']).'" style="width:97%" /><br/><br/>'.PHP_EOL;
		echo '<label for="msg_rcerror"> '.$this->SCF_LANG['rcerror'].': </label>';
		echo '<input id="msg_rcerror" name="msg_rcerror" type="text" value="'.str_replace('"','&quot;', $this->data['msg_rcerror']).'" style="width:97%" /><br/><br/>'.PHP_EOL;
		echo '<label for="msg_success"> '.$this->SCF_LANG['success'].': </label>';
		echo '<input id="msg_success" name="msg_success" type="text" value="'.str_replace('"','&quot;', $this->data['msg_success']).'" style="width:97%" /><br/><br/>'.PHP_EOL;
		echo '<label for="msg_fail"> '.$this->SCF_LANG['fail'].': </label>';
		echo '<input id="msg_fail" name="msg_fail" type="text" value="'.str_replace('"','&quot;', $this->data['msg_fail']).'" style="width:97%" /><br/><br/>'.PHP_EOL;
		echo '<label for="msg_presubject"> '.$this->SCF_LANG['presubject'].': </label>';
		echo '<input id="msg_presubject" name="msg_presubject" type="text" value="'.str_replace('"','&quot;', $this->data['msg_presubject']).'" style="width:97%" /><br/><br/>'.PHP_EOL;
		echo $this->SCF_LANG['pmlang'].' : <select name="Language">';
		echo '<optgroup label="'.$this->SCF_LANG['pmlang'].'">';
		echo '<option value="ar"'. ($this->data['Language']=='ar' ? 'selected="selected"':'') .'> ar - Arabic </option>';
		echo '<option value="br"'. ($this->data['Language']=='br' ? 'selected="selected"':'') .'> br - Portuguese </option>';
		echo '<option value="ca"'. ($this->data['Language']=='ca' ? 'selected="selected"':'') .'> ca - Catalan </option>';
		echo '<option value="cz"'. ($this->data['Language']=='cz' ? 'selected="selected"':'') .'> cz - Czech </option>';
		echo '<option value="de"'. ($this->data['Language']=='de' ? 'selected="selected"':'') .'> de - German </option>';
		echo '<option value="dk"'. ($this->data['Language']=='dk' ? 'selected="selected"':'') .'> dk - Danish </option>';
		echo '<option value="en"'. ($this->data['Language']=='en' ? 'selected="selected"':'') .'> en - English </option>';
		echo '<option value="es"'. ($this->data['Language']=='es' ? 'selected="selected"':'') .'> es - Spanish </option>';
		echo '<option value="et"'. ($this->data['Language']=='et' ? 'selected="selected"':'') .'> et - Estonian </option>';
		echo '<option value="fi"'. ($this->data['Language']=='fi' ? 'selected="selected"':'') .'> fi - Finnish </option>';
		echo '<option value="fo"'. ($this->data['Language']=='fo' ? 'selected="selected"':'') .'> fo - Faroese </option>';
		echo '<option value="fr"'. ($this->data['Language']=='fr' ? 'selected="selected"':'') .'> fr - French </option>';
		echo '<option value="hu"'. ($this->data['Language']=='hu' ? 'selected="selected"':'') .'> hu - Hungarian </option>';
		echo '<option value="ch"'. ($this->data['Language']=='ch' ? 'selected="selected"':'') .'> ch - Chinese </option>';
		echo '<option value="it"'. ($this->data['Language']=='it' ? 'selected="selected"':'') .'> it - Italian </option>';
		echo '<option value="ja"'. ($this->data['Language']=='ja' ? 'selected="selected"':'') .'> ja - Japanese </option>';
		echo '<option value="nl"'. ($this->data['Language']=='nl' ? 'selected="selected"':'') .'> nl - Dutch </option>';
		echo '<option value="no"'. ($this->data['Language']=='no' ? 'selected="selected"':'') .'> no - Norwegian </option>';
		echo '<option value="pl"'. ($this->data['Language']=='pl' ? 'selected="selected"':'') .'> pl - Polish </option>';
		echo '<option value="ro"'. ($this->data['Language']=='ro' ? 'selected="selected"':'') .'> ro - Romanian </option>';
		echo '<option value="ru"'. ($this->data['Language']=='ru' ? 'selected="selected"':'') .'> ru - Russian </option>';
		echo '<option value="se"'. ($this->data['Language']=='se' ? 'selected="selected"':'') .'> se - Swedish </option>';
		echo '<option value="sk"'. ($this->data['Language']=='sk' ? 'selected="selected"':'') .'> sk - Slovak </option>';
		echo '<option value="tr"'. ($this->data['Language']=='tr' ? 'selected="selected"':'') .'> tr - Turkish </option>';
		echo '<option value="zh"'. ($this->data['Language']=='zh' ? 'selected="selected"':'') .'> zh - Traditional Chinese </option>';
		echo '<option value="zh_cn"'. ($this->data['Language']=='zh_cn' ? 'selected="selected"':'') .'> zh_cn - Simplified Chinese </option>';
		echo '</optgroup>';
		echo '</select><br/><br/>';
		
		echo $this->SCF_LANG['validator_errors'].' : <select name="validator_errors">';
		echo '<option value="1"'. ($this->data['validator_errors']==1 ? 'selected="selected"':'') .'>'.$this->SCF_LANG['validator_errors1'].'</option>';
		echo '<option value="2"'. ($this->data['validator_errors']==2 ? 'selected="selected"':'') .'>'.$this->SCF_LANG['validator_errors2'].'</option>';
		echo '</select><br/><br/>';
		
		echo '<input type="submit" name="save_othersettings" value="'.$langmessage['save'].'" /> &nbsp;&nbsp;&nbsp;&nbsp;'.PHP_EOL;
/*end4*/	echo '</form></div><br/><br/><br/>';
		
		echo '<div style="font-size:16px; margin:1.5em 0;">'.$this->SCF_LANG['template'].'</div>'.PHP_EOL;
		echo '<p> '.common::Link($title,$this->SCF_LANG['create'],'cmd=create_template').'&nbsp; | &nbsp;';
		echo common::Link($title,$this->SCF_LANG['view'],'cmd=view_template').'&nbsp; | &nbsp;';
		echo common::Link($title,$this->SCF_LANG['edita'],'cmd=edit_templatea').'&nbsp; | &nbsp;';
		echo common::Link($title,$this->SCF_LANG['edite'],'cmd=edit_template').' </p>';
		
		echo '<div style="font-size:16px; margin:1.5em 0;">'.$this->SCF_LANG['form'].'</div>'.PHP_EOL;
		echo '<p> '.common::Link($title,$this->SCF_LANG['create'],'cmd=create_form').'&nbsp; | &nbsp;';
		echo common::Link('Special_scf',$this->SCF_LANG['test'],'','target="_blank"').'&nbsp; | &nbsp;';
		echo common::Link($title,$this->SCF_LANG['edita'],'cmd=edit_forma').' </p>';
		
		echo '<br/><br/><br/>';
	}
	
	function Select_Languages()
	{
		global $addonPathCode, $languages, $langmessage;
		$avail=array();
		if ($handle = opendir($addonPathCode.'/language'))
		{
			while (false !== ($file = readdir($handle)))
				if (strpos($file, 'lang_')!==false)
					$avail[] = substr($file,5,-4);
			closedir($handle);
		}
		//uksort($avail,"strnatcasecmp");
		//print_r($avail);
		echo '<select name="iLanguage" onchange="switch_language(this)">';
		echo '<optgroup label="'.$langmessage['language'].'">';
		foreach ($avail as $lang)
		{
			if (!strlen($lang))
				continue;
			$lang1 = isset($languages[$lang])?$languages[$lang]:'*';
			echo '<option value="'.$lang.'"'. ($this->lang==$lang ? 'selected="selected"':'') .'> '.$lang.' - '.$lang1.' </option>';
		}
		echo '</optgroup>';
		echo '</select>';
	}
	
	function edit_template()
	{
		global $title, $langmessage, $addonFolderName, $addonPathData;
		if (!file_exists($this->template))
		{
			message($this->SCF_LANG['template_none']);
			return;
		}
		$a = $_GET['cmd']=='edit_templatea'; //in textarea or in ckeditor
		$text = $this->getfile($this->template,1);
		
		echo '<p style="font-size:16px; margin-bottom:1.5em;">'.$this->SCF_LANG['template'].' - ';
		echo $a? $this->SCF_LANG['edita']:$this->SCF_LANG['edite'];
		echo '</p>'.PHP_EOL;
		echo '<form action="'.common::GetUrl($title).'" method="post">';
		if ($a)
		{
			$text = htmlspecialchars($text);
			echo '<textarea id="textfield" name="textfield" wrap="off" rows="20" cols="50" spellcheck="false" style="width:100%">'.$text.'</textarea>'.PHP_EOL;
		}
		else
		{
			if (file_exists($addonPathData.'/scf_style.css'))
				$css = '/data/_addondata/'.$addonFolderName.'/scf_style.css';//custom
			else
				$css = '/data/_addoncode/'.$addonFolderName.'/scf_style.css';//default
			$options = array('contentsCss'=>common::GetDir($css));
			//var_export($options);
			includeFile('tool/editing.php');
			gp_edit::UseCK($text,'textfield',$options);
		}
		echo '<input type="submit" name="save_template" value="'.$langmessage['save'].'" /> <br/>'.PHP_EOL;
		echo '</form>';
	}
	
	function edit_form() //in textarea
	{
		global $page,$addonRelativeCode,$addonRelativeData,$addonPathData,$title, $langmessage;
		echo '<p style="font-size:16px; margin-bottom:1.5em;">'.$this->SCF_LANG['form'].' - '.$this->SCF_LANG['edita'].'</p>'.PHP_EOL;
		echo '<form action="'.common::GetUrl($title).'" method="post">';
		echo '<textarea id="textfield" name="textfield" wrap="off" rows="20" cols="50" spellcheck="false" style="width:100%">';
		echo htmlspecialchars($this->getfile($addonPathData.'/contact_form.php',1));
		echo '</textarea>'.PHP_EOL;
		echo '<input type="submit" name="save_form" value="'.$langmessage['save'].'" /> <br/>'.PHP_EOL;
		echo '</form>';
	}
	
	function create_form()
	{
		global $addonRelativeData,$addonPathData,$addonPathCode,$addonRelativeCode,$config,$dataDir,$gp_titles,$gp_index,$addonFolderName;
		if (!file_exists($this->template))
		{
			echo $this->SCF_LANG['template_none'];
			return;
		}
		
		if ( ($this->data['aspam']=='capt') && ($this->keypublic=='' || $this->keyprivate=='') )
		{
			$this->data['aspam']='none';
			echo $this->SCF_LANG['rc_notset'].'<br/>';
		}
		
		if ($this->data['EnableCKE'])
		{
			//check if ckeditor is correctly aligned
			if (file_exists($addonPathData.'/scf_style.css'))
				$style = file_get_contents($addonPathData.'/scf_style.css');
			else
				$style = file_get_contents($addonPathCode.'/scf_style.css');//default
			if (strpos($style,'#cke_item'.$this->data['id_sendermessage']) === false)
			{
				$style .= PHP_EOL.'#cke_item'.$this->data['id_sendermessage'].' { float:left; }'.PHP_EOL;
				file_put_contents($addonPathData.'/scf_style.css',$style);
			}
			unset($style);
		}
		
		// ******************************* template **********************
		$t = $this->getfile($this->template,1); //get second splitted part
		$x = explode('[NUMBERS]',$t,2);
		if (($this->data['aspam']=='math') && ($this->data['Math']!=0) && (count($x)>1)) //if enabled and [NUMBERS] string was found
		{
			$t = $x[0].'<'.'?'.'php ';
			$t .= 'if ($op==\'+\') echo $n1.\' + \'.$n2; ';
			$t .= 'if ($op==\'-\') echo $n1+$n2.\' - \'.$n1; ';
			$t .= 'if ($op==\'*\') echo $n1.\' * \'.$n2; ';
			$t .= 'if ($op==\'a\') { $tempstring=\''.str_replace('\'','\\\'',$this->data['msg_enter_letter']).'\'; echo \' \'.str_replace(array(\'%a\',\'%c\'),array(chr($n-1),chr($n+1)),$tempstring); } '.PHP_EOL;
			$t .= 'if ($op==\'b\') { echo \''.str_replace('\'','\\\'',$this->data['msg_enter_unique']).' \'; $rc=65+rand()%26; if($rc==$n) $rc=($n==65?90:65); $pos=1+rand()%5; for($rs=0;$rs<7;$rs++) echo ($rs==$pos)?chr($n):chr($rc); } '.PHP_EOL;
			$t .= '?'.'>'.$x[1];
			$disablemathchecking=false;
		}
		else
		{
			$t = $x[0].(isset($x[1])?$x[1]:'');
			$disablemathchecking=true;
		}
		
		$x = explode('[CAPTCHA]',$t,2);
		if (count($x)>1) //if [CAPTCHA] string was found
		{
			if ($this->data['aspam']=='capt') //if captcha is enabled
			{
				$t = $x[0].'<'.'?'.'php '.PHP_EOL;
				$t .= ' requi'.'re_once($dataDir.\'/include/thirdparty/recaptchalib.php\');
						$publickey = common::ConfigValue(\'recaptcha_public\',\'\');
						echo recaptcha_get_html($publickey);'.PHP_EOL;
				$t .= '?'.'>'.$x[1].PHP_EOL;
			}
			else
			{
				$t = $x[0].(isset($x[1])?$x[1]:''); // this removes the [CAPTCHA] string from template
			}
		}
		if ($this->data['EnableCKE'])
		{
			$t .= ' <script type="text/javascript">
				CKEDITOR.replace( \'item'.$this->data['id_sendermessage'].'\',
				{
					'.$this->data['ckValues'].'
				});
				</script> '.PHP_EOL;
		}
		
		// ******************************* begin *************************
		
		$begin = '<script src="'.$addonRelativeCode.'/form_validator.min.js" type="text/javascript"></script>'.PHP_EOL;
		$begin .= '<script type="text/javascript"> var RecaptchaOptions = { lang : \'<?p'.'hp echo common::ConfigValue(\'recaptcha_language\',\'\'); ?'.'>\', theme : \''.$this->data['Captcha']['rctheme'].'\' }; </script>'.PHP_EOL;
		$begin .= '<'.'?'.'php global $page,$addonPathData,$addonRelativeData,$addonRelativeCode,$dataDir;'.PHP_EOL;
		$begin .= ' $rhc_string=\'Antispam test passed!\'; // REVERSE HONEYPOT CAPTCHA OK STRING ~ Antispam test passed! Humanity confirmed! :-)'.PHP_EOL;
		$begin .= ' if (file_exists($addonPathData.\'/scf_style.css\'))'.PHP_EOL;
		$begin .= '  $page->css_user[] = $addonRelativeData.\'/scf_style.css\';'.PHP_EOL;
		$begin .= ' else'.PHP_EOL;
		$begin .= '  $page->css_user[] = $addonRelativeCode.\'/scf_style.css\'; ?'.'>'.PHP_EOL.PHP_EOL;
		
		$begin .= '<div class="simplecontactform">'.PHP_EOL;
		$begin .= '<noscript><p>'.($this->data['msg_noscript']).'</p></noscript>'.PHP_EOL.PHP_EOL;
		
		$begin .= '<'.'?'.'php global $config,$addonPathCode,$langmessage,$title;'.PHP_EOL;
		
		if ($this->data['aspam']=='capt')
		{
			$begin .= 'if (isset($_POST["submitForm"])) {'.PHP_EOL;
			$begin .= ' requi'.'re_once($dataDir.\'/include/thirdparty/recaptchalib.php\');'.PHP_EOL;
			$begin .= ' $privatekey = common::ConfigValue(\'recaptcha_private\',\'\');'.PHP_EOL;
			$begin .= ' $resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);'.PHP_EOL;
			$z = explode('[ERRORCODE]',$this->data['msg_rcerror'],2);
			$begin .= ' if (!$resp->is_valid) {'.PHP_EOL;
			$begin .= '  echo "'.$z[0].'".$resp->error."'.(isset($z[1])?$z[1]:'').'";'.PHP_EOL;
			$begin .= ' }'.PHP_EOL;
			$begin .= '}'.PHP_EOL.PHP_EOL;
			$begin .= 'if (isset($_POST["submitForm"]) && $_POST["url"]==\'\' && $resp->is_valid) {'.PHP_EOL;
		}
		elseif ($this->data['aspam']=='math')
		{
			$begin .= 'if (isset($_POST["submitForm"]) && $_POST["url"]==\'\') {'.PHP_EOL;
		}
		elseif ($this->data['aspam']=='rhcapt')
		{
			$begin .= 'if (isset($_POST["submitForm"]) && $_POST["url"]==$rhc_string) {'.PHP_EOL;
		}
		else // no antispam protection
		{
			$begin .= 'if (isset($_POST["submitForm"])) {'.PHP_EOL;
		}
		
		$eol = $this->data['EnableCKE']? '\'<br/>\'' : 'PHP_EOL.PHP_EOL';
		$b1 = $this->data['EnableCKE']? '\'<b>\'.' : '';
		$b2 = $this->data['EnableCKE']? '.\'</b>\'' : '';
		$begin .= ' requi'.'re_once($addonPathCode.\'/class.phpmailer.php\'); '.PHP_EOL;
		$begin .= ' $mail = new PHPMailer();'.PHP_EOL;
		$begin .= ' $mail->SetLanguage("'.$this->data['Language'].'");'.PHP_EOL;
		$begin .= ' $mail->IsHTML('.($this->data['EnableCKE']?'true':'false').');'.PHP_EOL;
		$begin .= ' $mail->WordWrap = '.$this->data['WordWrap'].';'.PHP_EOL;
		$begin .= ' $mail->CharSet = "'.$this->data['CharSet'].'";'.PHP_EOL;
		if ($this->data['method']=='smtp')
		{
			$begin .= ' $mail->IsSMTP();'.PHP_EOL; // we use the SMTP server to send e-mail
			$begin .= ' $mail->SMTPDebug = false;'.PHP_EOL;
			$begin .= ' $mail->SMTPAuth = '.($this->data['SMTPAuth']?'true':'false').';'.PHP_EOL;
			if ($this->data['SMTPAuth']=='true')
			{
				$begin .= ' $mail->SMTPSecure="'.$this->data['SMTPSecure'].'";'.PHP_EOL;
				$begin .= ' $mail->Host="'.($this->data['Host']).'";'.PHP_EOL;
				$begin .= ' $mail->Port="'.($this->data['Port']).'";'.PHP_EOL;
				$begin .= ' $mail->Username=$config[\'smtp_user\'];'.PHP_EOL;
				$begin .= ' $mail->Password=$config[\'smtp_pass\'];'.PHP_EOL;
			}
		}
		elseif ($this->data['method']=='mail')
		{
			$begin .= ' $mail->IsMail();'.PHP_EOL; // we use the mail() function to send e-mail
		}
		elseif ($this->data['method']=='sendmail')
		{
			$begin .= ' $mail->IsSendmail();'.PHP_EOL; // we use the Sendmail program to send e-mail
			$begin .= ' if ($config[\'sendmail_path\']!=\'\')'.PHP_EOL;
			$begin .= '  $mail->Sendmail = $config[\'sendmail_path\'];'.PHP_EOL;
		}
		if ($this->data['msg_listing']!='')
		{
			$begin .= ' echo \''.str_replace('\'','\\\'', $this->data['msg_listing']).'<br/>\';'.PHP_EOL;
		
		foreach ($this->items as $i => $value)
		{
			$begin .= ' echo \'<b>'.$value['label'].':</b> \';'.PHP_EOL;
			if ($value['type']=='input' || $value['type']=='radio' || $value['type']=='select' || $value['type']=='textarea')
			{
				$begin .= ' if (isset($_POST[\'item'.$i.'\']))'.PHP_EOL;
				$begin .= ' {'.PHP_EOL;
				$begin .= '  echo $_POST[\'item'.$i.'\'].\'<br/>\';'.PHP_EOL;
				$begin .= ' }'.PHP_EOL;
				$begin .= ' else'.PHP_EOL;
				$begin .= '  echo \'-<br/>\';'.PHP_EOL;
			}
			if ($value['type']=='checkbox')
			{
				$begin .= ' if (isset($_POST[\'item'.$i.'\']))'.PHP_EOL;
				$begin .= '  echo \''.$this->SCF_LANG['checked'].'<br/>\';'.PHP_EOL;
				$begin .= ' else'.PHP_EOL;
				$begin .= '  echo \''.$this->SCF_LANG['unchecked'].'<br/>\';'.PHP_EOL;
			}
			if ($value['type']=='file')
			{
				$begin .= ' if ($_FILES[\'item'.$i.'\'][\'name\']!=\'\')'.PHP_EOL;
				$begin .= '  echo \'<i>\'.$_FILES[\'item'.$i.'\'][\'name\'].\'</i>\';'.PHP_EOL;
				$begin .= ' else'.PHP_EOL;
				$begin .= '  echo \'-\';'.PHP_EOL;
				$begin .= ' if ($_FILES[\'item'.$i.'\'][\'error\']!=0 && $_FILES[\'item'.$i.'\'][\'error\']!=4)'.PHP_EOL;
				$begin .= '  echo \' <i>error</i> <a href="http://php.net/manual/en/features.file-upload.errors.php" target="_blank">\'.$_FILES[\'item'.$i.'\'][\'error\'].\'</a>\';'.PHP_EOL;
				$begin .= ' echo \' <br/>\';'.PHP_EOL;
			}
		}
		if ($this->data['sendcopytosender'])
		{
			$begin .= ' if (isset($_POST[\'sendcopytosender\']))'.PHP_EOL;
			$begin .= '  echo \'<b>'.$this->data['msg_sendcopytosender'].'</b>: '.$this->SCF_LANG['checked'].'<br/>\';'.PHP_EOL;
			$begin .= ' else'.PHP_EOL;
			$begin .= '  echo \'<b>'.$this->data['msg_sendcopytosender'].'</b>: '.$this->SCF_LANG['unchecked'].'<br/>\';'.PHP_EOL;
		}
		}//end of listing
		$begin .= PHP_EOL.PHP_EOL;
		
		
		// validate posted fields - on server
		$begin .= ' $send = true;'.PHP_EOL;
		if (strpos($this->items[$this->data['id_sendername']]['valid'],'req')!==false)
		{
			$begin .= ' if (!isset($_POST["item'.$this->data['id_sendername'].'"]) || $_POST["item'.$this->data['id_sendername'].'"]==\'\')'.PHP_EOL;
			$begin .= ' {'.PHP_EOL;
			$begin .= '  printf($langmessage[\'OOPS_REQUIRED\'],\''.$this->items[$this->data['id_sendername']]['label'].'\');'.PHP_EOL;
			$begin .= '  echo \'<br/>\';'.PHP_EOL;
			$begin .= '  $send = false;'.PHP_EOL;
			$begin .= ' }'.PHP_EOL;
		}
		if (strpos($this->items[$this->data['id_senderemail']]['valid'],'email')!==false)
		{
			$begin .= ' if (!isset($_POST["item'.$this->data['id_senderemail'].'"]) || $_POST["item'.$this->data['id_senderemail'].'"]==\'\' '.PHP_EOL;
			$begin .= '  || !preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $_POST["item'.$this->data['id_senderemail'].'"]))'.PHP_EOL;
			$begin .= ' {'.PHP_EOL;
			$begin .= '  echo $langmessage[\'invalid_email\'].\'<br/>\';'.PHP_EOL;
			$begin .= '  $send = false;'.PHP_EOL;
			$begin .= ' }'.PHP_EOL;
		}
		if (strpos($this->items[$this->data['id_sendersubject']]['valid'],'req')!==false)
		{
			$begin .= ' if (!isset($_POST["item'.$this->data['id_sendersubject'].'"]) || $_POST["item'.$this->data['id_sendersubject'].'"]==\'\')'.PHP_EOL;
			$begin .= ' {'.PHP_EOL;
			$begin .= '  printf($langmessage[\'OOPS_REQUIRED\'],\''.$this->items[$this->data['id_sendersubject']]['label'].'\');'.PHP_EOL;
			$begin .= '  echo \'<br/>\';'.PHP_EOL;
			$begin .= '  $send = false;'.PHP_EOL;
			$begin .= ' }'.PHP_EOL;
		}
		//message will be always checked for emptiness, should be written :
		//if (strpos($this->items[$this->data['id_sendermessage']]['valid'],'req')!==false)
		//{
			$begin .= ' if (!isset($_POST["item'.$this->data['id_sendermessage'].'"]) || $_POST["item'.$this->data['id_sendermessage'].'"]==\'\')'.PHP_EOL;
			$begin .= ' {'.PHP_EOL;
			$begin .= '  printf($langmessage[\'OOPS_REQUIRED\'],\''.$this->items[$this->data['id_sendermessage']]['label'].'\');'.PHP_EOL;
			$begin .= '  echo \'<br/>\';'.PHP_EOL;
			$begin .= '  $send = false;'.PHP_EOL;
			$begin .= ' }'.PHP_EOL;
		//}
		$begin .= ' if (!$send)'.PHP_EOL;
		$begin .= ' {'.PHP_EOL;
		$begin .= '  echo \'<br/><b>'.$this->SCF_LANG['message_not_sent'].'</b> <a href="\'.common::GetUrl($title).\'" target="_blank">\'.common::GetLabel($title).\'</a></div>\';'.PHP_EOL;
		$begin .= '  return;'.PHP_EOL;
		$begin .= ' }'.PHP_EOL;
		$begin .= ' $_name = $_POST["item'.$this->data['id_sendername'].'"];'.PHP_EOL;
		$begin .= ' $_email = $_POST["item'.$this->data['id_senderemail'].'"];'.PHP_EOL;
		$begin .= ' $_subject = $_POST["item'.$this->data['id_sendersubject'].'"];'.PHP_EOL;
		$begin .= ' $_message = $_POST["item'.$this->data['id_sendermessage'].'"];'.PHP_EOL;
		$begin .= ' $_body = \'\';'.PHP_EOL;
		$begin .= PHP_EOL.PHP_EOL;
		
		foreach ($this->items as $i => $value)
		{
			if ($value['type']!='file')
			{
				$begin .= '	$_body .= '.$b1.'\''.$value['label'].'\''.$b2.'.\': \';'.PHP_EOL;
			}
			if ($value['type']=='input' || $value['type']=='radio' || $value['type']=='select' || $value['type']=='textarea')
			{
				$begin .= '	if (isset($_POST[\'item'.$i.'\']))'.PHP_EOL;
				$begin .= '	{'.PHP_EOL;
			//	if ($value['type']=='textarea' && !$this->data['EnableCKE'])
			//		$begin .= '		$_POST[\'item'.$i.'\'] = str_replace("\n","<br/>\n",$_POST[\'item'.$i.'\']);'.PHP_EOL;
				$begin .= '		$_body .= $_POST[\'item'.$i.'\'].'.$eol.';'.PHP_EOL;
				$begin .= '	}'.PHP_EOL;
				$begin .= '	else'.PHP_EOL;
				$begin .= '		$_body .= \'-\'.'.$eol.';'.PHP_EOL;
			}
			if ($value['type']=='checkbox')
			{
				$begin .= '	if (isset($_POST[\'item'.$i.'\']))'.PHP_EOL;
				$begin .= '		$_body .= \''.$this->SCF_LANG['checked'].'\'.'.$eol.';'.PHP_EOL;
				$begin .= '	else'.PHP_EOL;
				$begin .= '		$_body .= \''.$this->SCF_LANG['unchecked'].'\'.'.$eol.';'.PHP_EOL;
			}
			if ($value['type']=='file') //attachment
			{
				$begin .= '	if ($_FILES[\'item'.$i.'\'][\'error\']==0)'.PHP_EOL;
				$begin .= '		$mail->AddAttachment($_FILES[\'item'.$i.'\'][\'tmp_name\'],$_FILES[\'item'.$i.'\'][\'name\']); // attachment'.PHP_EOL;
			}
		}
		if ($this->data['sendcopytosender'])
		{
			$begin .= '	if (isset($_POST[\'sendcopytosender\']))'.PHP_EOL;
			$begin .= '		$_body .= '.$eol.'.'.$b1.'\''.$this->data['msg_sendcopytosender'].'\''.$b2.'.\': '.$this->SCF_LANG['checked'].'\'.'.$eol.';'.PHP_EOL;
			$begin .= '	else'.PHP_EOL;
			$begin .= '		$_body .= '.$eol.'.'.$b1.'\''.$this->data['msg_sendcopytosender'].'\''.$b2.'.\': '.$this->SCF_LANG['unchecked'].'\'.'.$eol.';'.PHP_EOL;
		}
		$begin .= '			$mail->AddAddress($config[\'toemail\'],$config[\'toname\']); // e-mail address of receiver'.PHP_EOL;
		$begin .= '			//$mail->AddAddress("another.mail@another.address.com");  // e-mail address of another receiver..... '.PHP_EOL;
		if ($this->data['sendcopytosender'])
		{
			$begin .= '			if (isset($_POST[\'sendcopytosender\']))'.PHP_EOL;
			$begin .= '			{'.PHP_EOL;
			$begin .= '				$mail->AddBCC($_email,$_name); // blind carbon copy for sender'.PHP_EOL;
			$begin .= '			}'.PHP_EOL;
			$begin .= '			else'.PHP_EOL;
			$begin .= '			{'.PHP_EOL;
			$begin .= '				$mail->AddReplyTo($_email,$_name);'.PHP_EOL;
			$begin .= '			}'.PHP_EOL;
		}
		$begin .= '			$mail->FromName = $_name; // Sender\'s full name'.PHP_EOL;
		$begin .= '			$mail->From = $_email; // sender\'s e-mail address'.PHP_EOL;
		$begin .= '			$mail->Return = $_email; // if email will not be delivered, notice will return here'.PHP_EOL;
		$begin .= '			$mail->Subject = \''.str_replace('\'','\\\'', $this->data['msg_presubject']).'\'.$_subject;'.PHP_EOL;
		$begin .= '			$mail->Body .= $_body;'.PHP_EOL;
		$begin .= '			$sent = $mail->Send();//via smtp or phpmail'.PHP_EOL;
		$begin .= '			if($sent)'.PHP_EOL;
		$begin .= '				{echo \'<br/><br/>'.str_replace('\'','\\\'', $this->data['msg_success']).'\';}'.PHP_EOL;
		$begin .= '			else'.PHP_EOL;
		$begin .= '				{echo \'<br/><br/>'.str_replace('\'','\\\'', $this->data['msg_fail']).'\';}'.PHP_EOL;
		$begin .= '			echo \'<br/><br/>\';'.PHP_EOL;
		$begin .= '	  }'.PHP_EOL;
		if ($this->data['aspam']=='rhcapt')
		{
			// test reverse honeypot captcha
			$begin .= '	if (isset($_POST["submitForm"]) && $_POST[\'url\']!=$rhc_string)'.PHP_EOL; // if antispam field is not ok
			$begin .= '		{ echo \''.$this->SCF_LANG['spam_detected'].' (Reverse Honeypot Captcha)\';} '.PHP_EOL;
		}
		elseif ($this->data['aspam']=='math' || $this->data['aspam']=='capt')
		{
			// test ordinary honeypot captcha
			$begin .= '	if (isset($_POST["submitForm"]) && $_POST[\'url\']!=\'\')'.PHP_EOL;
			$begin .= '		{ echo \''.$this->SCF_LANG['spam_detected'].' (Honeypot Captcha)\';} '.PHP_EOL;
		}
		if (($this->data['aspam']=='math') && $this->data['Math']!=0)
		{
			$op = ($this->data['Math']&1?'+':'').($this->data['Math']&2?'-':'').($this->data['Math']&4?'*':'').($this->data['Math']&8?'a':'').($this->data['Math']&16?'b':'');
			$begin .= '	$op=\''.$op.'\';'.PHP_EOL;
			$begin .= '	$op=$op[(rand()%strlen($op))];'.PHP_EOL;
			$begin .= '	if ($op==\'a\' || $op==\'b\') { $n = 66+rand()%24; } else { $n1 = rand()%10; $n2 = rand()%10; }'.PHP_EOL;
			$begin .= '	'.PHP_EOL;
		}
		$begin .= '	?'.'>'.PHP_EOL;
		
		$begin .= '		<div id="scf_jsContentWrapper" style="display:none;">'.PHP_EOL;
		
		if ($this->data['EnableCKE'])
		{
			$begin .= '<script type="text/javascript" src="'. common::GetDir('/include/thirdparty/ckeditor_34/ckeditor.js') .'"></script>'.PHP_EOL;
		}
		
		// ******************************* end ***************************
		
		$end = '';
		$end .= '</div>'.PHP_EOL.PHP_EOL; //end of "scf_jsContentWrapper" division
		
		$end .= '<'.'?'.'php'.PHP_EOL.'if (!isset($_POST["submitForm"]))'.PHP_EOL; // don't display the form after the sending
		$end .= '{ echo \'<script type="text/javascript">$(\\\'#scf_jsContentWrapper\\\').removeAttr(\\\'style\\\'); </script>\'.PHP_EOL; }'.PHP_EOL;
		$end .= '?'.'>'.PHP_EOL;
		
		$end .= '	<script type="text/javascript">'.PHP_EOL;
		$end .= '		var frmvalidator  = new Validator("special_contact_form");'.PHP_EOL;
		if ($this->data['validator_errors']==1)
			$end .= '		frmvalidator.EnableOnPageErrorDisplaySingleBox();'.PHP_EOL;
		else
			$end .= '		frmvalidator.EnableOnPageErrorDisplay();'.PHP_EOL;
		foreach ($this->items as $i => $value)
		{
			if ($value['valid']=='')
				continue;
			$v = explode(',',$value['valid']);
			foreach ($v as $vv)
			{
				$validation = trim($vv);
				//if ($value['type']=='select' && $vv=='')
				//	$vv=' ';
				if (strpos($validation,'req')!==false)
					$validation_message = $this->SCF_LANG['valid_req'].' - '.$value['label'];
				if (strpos($validation,'minlen')!==false)
					$validation_message = $this->SCF_LANG['valid_short'].' - '.$value['label'];
				if (strpos($validation,'maxlen')!==false)
					$validation_message = $this->SCF_LANG['valid_long'].' - '.$value['label'];
				if (strpos($validation,'email')!==false)
					$validation_message = $this->SCF_LANG['valid_email'];
				$end .= '		frmvalidator.addValidation("item'.$i.'","'.$validation.'","'.$validation_message.'");'.PHP_EOL;
			}
		}
		if ($this->data['aspam']=='rhcapt')
		{
			$end .= '		$(\'form[name="special_contact_form"] input[type="submit"]\').hover(function(){ this.form.url.value=\'<?'.'ph'.'p echo $rhc_string; ?'.'>\'; },function(){}); // confirm humanity'.PHP_EOL;
			$end .= '		$(\'form[name="special_contact_form"]\').keypress( function(evt){ return !(evt.which==13 && evt.target.type!=\'textarea\'); }); // prevent submiting by enter'.PHP_EOL;
			$end .= '		$(\'form[name="special_contact_form"] input[type="submit"]\').attr(\'tabIndex\',-1); // prevent submiting by spacebar (keyCode==32)'.PHP_EOL;
			//$('#item2').click(function(){ this.form.submit(); }); //testing when mouseover event is not fired
		}
		else // == 'math' or 'capt' or 'none'
		{
			// url - must be empty - this is another anti spam check = direct honeypot captcha. (is this implemented correctly?)
			$end .= '		frmvalidator.addValidation("url","maxlen=0","'.$this->SCF_LANG['valid_empty'].'");'.PHP_EOL;
		}
		// check
		if ($disablemathchecking==false)
		{
			$end .= '			frmvalidator.addValidation("check","req","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '		<?'.'php if ($op==\'+\') echo \''.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","numeric","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","greaterthan=\'.($n1+$n2-1).\'","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","lessthan=\'.($n1+$n2+1).\'","'.$this->SCF_LANG['valid_antispam'].'");\';'.PHP_EOL;
			$end .= '		?>'.PHP_EOL;
			$end .= '		<?'.'php if ($op==\'-\') echo \''.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","numeric","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","greaterthan=\'.($n2-1).\'","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","lessthan=\'.($n2+1).\'","'.$this->SCF_LANG['valid_antispam'].'");\';'.PHP_EOL;
			$end .= '		?>'.PHP_EOL;
			$end .= '		<?'.'php if ($op==\'*\') echo \''.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","numeric","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","greaterthan=\'.($n1*$n2-1).\'","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","lessthan=\'.($n1*$n2+1).\'","'.$this->SCF_LANG['valid_antispam'].'");\';'.PHP_EOL;
			$end .= '		?>'.PHP_EOL;
			$end .= '		<?'.'php if ($op==\'a\' || $op==\'b\') echo \''.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","alpha","'.$this->SCF_LANG['valid_antispam'].'");'.PHP_EOL;
			$end .= '			frmvalidator.addValidation("check","regexp=[\'.chr($n).\']|[\'.chr($n+32).\']","'.$this->SCF_LANG['valid_antispam'].'");\';'.PHP_EOL;
			$end .= '		?>'.PHP_EOL;
		}
		$end .= '	</script>'.PHP_EOL;
		$end .= '</div>'.PHP_EOL;
		$str='<'.'?'.'php defined(\'is_running\') or die(\'Not an entry point...\');
 ?'.'>';
		
		if (file_put_contents($this->formname, $str. $begin.$t.$end)) //saves page's content
			message($this->SCF_LANG['cf_saved'].' '.$config['toemail'].'. >>  '.common::Link('Special_scf',common::GetLabel('Special_scf'),'','target="_blank"').'<br/><br/>');
	}
	
	function save_template($newcontent)
	{
		$begin = $this->getfile($this->template,0);
		if (file_put_contents($this->template, $begin.' ?'.'> '.$newcontent))
			message($this->SCF_LANG['template_saved']);
	}
	
	function save_form($newcontent)
	{
		global $addonPathData,$config;
		$form_file = $addonPathData.'/contact_form.php';
		$begin = $this->getfile($form_file,0);
		if (file_put_contents($form_file, $begin.' ?'.'> '.$newcontent))
			message($this->SCF_LANG['cf_saved'].' '.$config['toemail'].'. >>  '.common::Link('Special_scf',common::GetLabel('Special_scf'),'','target="_blank"').'<br/><br/>');
	}
	
	function create_template()
	{
		$t = '<div>'.$this->SCF_LANG['form'].'</div><br/>'.PHP_EOL;
		$t.= '<form enctype="multipart/form-data" action="" method="post" name="special_contact_form" class="scf">'.PHP_EOL;
		$t.= ' <fieldset>'.PHP_EOL;
		//var_export($this->items);
		foreach ($this->items as $i => $value)
		{
			if ($value['type']=='radio')
				$t.= '  <p><b>'.$value['label'].'</b></p>'.PHP_EOL;
			else
				$t.= '  <label for="item'.$i.'"><b>'.$value['label'].'</b>'.PHP_EOL;
			$rnr = '    *('.(strpos($value['valid'],'req')===false ? $this->SCF_LANG['recommended']:$this->SCF_LANG['required']).')'.PHP_EOL;
			if ($value['type']=='textarea' && $i!=$this->data['id_sendermessage'])
				$t.= $rnr;
			switch ($value['type'])
			{
				case 'input':
					$t.= '   <input id="item'.$i.'" name="item'.$i.'" type="text" value="" />'.PHP_EOL;
				break;
				case 'checkbox':
					$t.= '   <input id="item'.$i.'" name="item'.$i.'" type="checkbox" />'.PHP_EOL;
				break;
				case 'radio':
					if ($value['multi_values']=='')
						break; //skips wrong field
					$vs = explode(',', $value['multi_values']);
					$first = true;
					foreach ($vs as $j => $str)
					{
						$t.= '   <label for="item'.$i.'_'.$j.'"><b>'.$str.'</b> <input id="item'.$i.'_'.$j.'" name="item'.$i.'" type="radio" value="'.$str.'"'.($first?' checked="checked"':'').' /> </label><br/>'.PHP_EOL;
						if ($first) $first=false;
					}
				break;
				case 'select':
					$t.= '  <select id="item'.$i.'" name="item'.$i.'">'.PHP_EOL;
					if ($value['multi_values']!='')
					{
						$vs = explode(',', $value['multi_values']);
						foreach ($vs as $str)
						{
							$t.= '    <option value="'.$str.'">'.$str.'</option> '.PHP_EOL;
						}
					}
					$t.= '   </select>'.PHP_EOL;
				break;
				case 'textarea':
					$t.= '   <textarea id="item'.$i.'" name="item'.$i.'" '.($i==$this->data['id_sendermessage'] ? $this->data['message_ta_params']:'cols="30" rows="5"').'></textarea>'.PHP_EOL;
				break;
				case 'file':
					$t.= '    ('.$this->SCF_LANG['max_filesize'].': '.ini_get('upload_max_filesize').'B)'.PHP_EOL;
					$t.= '   <input id="item'.$i.'" name="item'.$i.'" type="file" value="" style="margin-right:90px"/>'.PHP_EOL;
				break;
			}
			if ($value['type']=='input')
				$t.= $rnr;
			if ($this->data['validator_errors']==2)
				$t .= '    <span class="error_strings" id="special_contact_form_item'.$i.'_errorloc"> </span>';
			if ($value['type']!='radio')
				$t.= '  </label>'.PHP_EOL;
			//$t .= '<br/>';
		}
		if ($this->data['sendcopytosender'])
		{
			$t.= '  <label for="sendcopytosender">'.$this->data['msg_sendcopytosender'].PHP_EOL;
			$t.= '   <input id="sendcopytosender" name="sendcopytosender" type="checkbox" /> '.PHP_EOL;
			$t.= '  </label>'.PHP_EOL;
		}
		if ($this->data['aspam']=='math')
		{
			$t.= '  <label for="check"><b>'.$this->SCF_LANG['antispam'].'</b>'.PHP_EOL;
			$t.= '    <span style="float:left">'.$this->SCF_LANG['enter_result'].' [NUMBERS] : </span>'.PHP_EOL;
			$t.= '    <input id="check" name="check" type="text" value="" class="scf_input" />'.PHP_EOL;
			$t.= '  </label>'.PHP_EOL;
		}
		
		if ($this->data['aspam']=='capt')
		{
			$t.= '  <label><b>'.$this->SCF_LANG['antispam'].'</b>'.PHP_EOL;
			$t.= '   [CAPTCHA]</label><br/>'.PHP_EOL;
		}
		$t.= '    <input class="scf_submit" name="submitForm" type="submit" value="'.$this->SCF_LANG['send'].'" />'.PHP_EOL;
		$t.= '    <input id="url" name="url" type="text" value="" style="display:none" />'.PHP_EOL;
		$t.= '    <input id="website" name="website" type="text" value="" style="display:none" />'.PHP_EOL;
		if ($this->data['validator_errors']==1)
		{
			$t.= '    <span class="error_strings" id="special_contact_form_errorloc"> </span>'.PHP_EOL;
		}
		if ($this->data['validator_errors']==2)
		{
			$t.= '    <span class="error_strings" id="special_contact_form_check_errorloc"> </span>'.PHP_EOL;
		}
		$t.= ' </fieldset>'.PHP_EOL;
		$t.= '</form>'.PHP_EOL;
		$str= '<'.'?'.'php defined(\'is_running\') or die(\'Not an entry point...\');
 ?'.'>'.PHP_EOL;
		file_put_contents($this->template, $str.$t); // save template
		message($this->SCF_LANG['template_created'].'<br/><br/>');
	}
	
	function view_template()
	{
		global $page,$addonPathData,$addonRelativeData,$addonRelativeCode;
		if (!file_exists($this->template))
		{
			message($this->SCF_LANG['template_none']);
			return;
		}
		if (file_exists($addonPathData.'/scf_style.css'))
			$page->css_user[] = $addonRelativeData.'/scf_style.css';//default
		else
			$page->css_user[] = $addonRelativeCode.'/scf_style.css';//default
		echo $this->SCF_LANG['template_preview'].'<br/><br/>';
		//include($this->template);
		$t = $this->getfile($this->template,1);
		$t = str_replace('type="submit"','type="submit" disabled="disabled"',$t);
		echo $t.'<br/>';
	}
	
	function save_config()
	{
		global $addonPathData;
		gpFiles::SaveArray($addonPathData.'/config.php', 'items', $this->items, 'data', $this->data);
	}
	
	function save_fields()
	{
		global $config,$addonPathData;
		$j = 1;
		$this->items = array();
		for ($i=0; $i<=$_POST['maxval']; $i++)
		{
			if (isset($_POST['type'.$i]))
			{
				$this->items[$j]['type'] = $_POST['type'.$i];
				$this->items[$j]['label'] = isset($_POST['label'.$i]) ?  $_POST['label'.$i] : 'label '.$j;
				$valid = isset($_POST['valid'.$i]) ? $_POST['valid'.$i] : '';
				if ($valid!='')
				{
					$x=array();
					$y=explode(',',$valid);
					foreach($y as $c)
					{
						$condition = trim($c);
						if ($condition=='required')
							$condition='req';
						$x[] = $condition;
					}
					$valid = implode(',',$x);//condensed
				}
				$this->items[$j]['valid'] = $valid;
				$this->items[$j]['multi_values'] = isset($_POST['multi_values'.$i]) ? $_POST['multi_values'.$i] : '';
				if ($_POST['id_sendername']==$i)
				{
					$this->data['id_sendername'] = $j;
					if ($this->items[$j]['type'] != 'input')
						echo $this->SCF_LANG['warn_name'].'<br/>';
				}
				if ($_POST['id_senderemail']==$i)
				{
					$this->data['id_senderemail'] = $j;
					if ($this->items[$j]['type'] != 'input')
						echo $this->SCF_LANG['warn_email'].'<br/>';
				}
				if ($_POST['id_sendersubject']==$i)
				{
					$this->data['id_sendersubject'] = $j;
					if ($this->items[$j]['type'] != 'input' && $this->items[$j]['type'] != 'select')
						echo $this->SCF_LANG['warn_subject'].'<br/>';
				}
				if ($_POST['id_sendermessage']==$i)
				{
					$this->data['id_sendermessage'] = $j;
					if ($this->items[$j]['type'] != 'textarea')
						echo $this->SCF_LANG['warn_message'].'<br/>';
				}
				$j++;
			}
		}
		$this->data['sendcopytosender']= isset($_POST['sendcopytosender1'])  ? true:false;
		$this->data['msg_sendcopytosender']= $_POST['msg_sendcopytosender'];
		$this->data['message_ta_params']= $_POST['message_ta_params'];
		$this->save_config();
		message($this->SCF_LANG['settings_saved'].'<br/>');
	}
	
	function save_antispams()
	{
		global $config,$addonPathData;
		$this->data['aspam'] = $_POST['aspam'];
		$this->data['Math'] = 0;
		$this->data['Math'] |= isset($_POST['Math_show1'])  ? 1:0;
		$this->data['Math'] |= isset($_POST['Math_show2'])  ? 2:0;
		$this->data['Math'] |= isset($_POST['Math_show4'])  ? 4:0;
		$this->data['Math'] |= isset($_POST['Math_show8'])  ? 8:0;
		$this->data['Math'] |= isset($_POST['Math_show16'])  ? 16:0;
		$this->data['msg_enter_letter']= $_POST['msg_enter_letter'];
		$this->data['msg_enter_unique']= $_POST['msg_enter_unique'];
		$this->data['Captcha']['rctheme']= $_POST['captcha_rctheme'];
		$this->save_config();
		message($this->SCF_LANG['settings_saved'].'<br/>');
	}
	
	function save_emailsettings()
	{
		global $config,$addonPathData;
		if ( ($config['toemail']!=$_POST['Receiver']) || ($config['toname']!=$_POST['ReceiverName'])
		   || ($config['smtp_user']!=$_POST['Username']) || ($config['smtp_pass']!=$_POST['Password']) )
		{
			$config['toemail'] = $_POST['Receiver'];
			$config['toname'] = $_POST['ReceiverName'];
			$config['smtp_user'] = $_POST['Username'];
			$config['smtp_pass'] = $_POST['Password'];
			admin_tools::SaveConfig();
		}
		$this->data['WordWrap']= 0+$_POST['WordWrap'];
		$this->data['CharSet']= $_POST['CharSet'];
		$this->data['method']= $_POST['method'];
		$this->data['SMTPAuth']= isset($_POST['SMTPAuth']) ? true:false; 
		$this->data['Host']= $_POST['Host'];
		$this->data['Port']= 0+$_POST['Port'];
		$_POST['SMTPSecure']= strtolower($_POST['SMTPSecure']);
		if ($_POST['SMTPSecure']=='' || $_POST['SMTPSecure']=='ssl' || $_POST['SMTPSecure']=='tls')
		{
			$this->data['SMTPSecure']= $_POST['SMTPSecure'];
		}
		else
		{
			$this->data['SMTPSecure']= '';
		}
		$this->save_config();
		message($this->SCF_LANG['settings_saved'].'<br/>');
	}
	
	function save_othersettings()
	{
		global $config,$addonPathData;
		$this->data['EnableCKE']= isset($_POST['EnableCKE']) ? true:false;
		$this->data['msg_noscript']= $_POST['msg_noscript'];
		$this->data['msg_listing']= $_POST['msg_listing'];
		$this->data['msg_success']= $_POST['msg_success'];
		$this->data['msg_fail']= $_POST['msg_fail'];
		$this->data['msg_presubject']= $_POST['msg_presubject'];
		$this->data['msg_rcerror']= $_POST['msg_rcerror'];
		$this->data['ckValues']= $_POST['ckValues'];
		$this->data['Language']= $_POST['Language']; //phpmailer
		$this->data['validator_errors']= 0+$_POST['validator_errors'];
		$this->save_config();
		file_put_contents($addonPathData.'/scf_style.css',$_POST['cfstyle']);
		message($this->SCF_LANG['settings_saved'].'<br/>');
	}
	
	function Start()
	{
		global $addonPathData;
		if (isset($_POST['save_fields'])) //settings 1
		{
			$this->save_fields();
		}
		elseif (isset($_POST['save_antispams'])) //settings 2
		{
			$this->save_antispams();
		}
		elseif (isset($_POST['save_emailsettings'])) //settings 3
		{
			$this->save_emailsettings();
		}
		elseif (isset($_POST['save_othersettings'])) //settings 4
		{
			$this->save_othersettings();
		}
		if (isset($_POST['save_template']))
		{
			$this->save_template($_POST['textfield']);
		}
		if (isset($_POST['save_form']))
		{
			$this->save_form($_POST['textfield']);
		}
		
		$cmd='';
		parse_str($_SERVER['QUERY_STRING']);
		if ($cmd=='set_defaults')
		{
			$this->data['ckValues'] = ckDefault;
			$this->save_config();
			message($this->SCF_LANG['ckeditor_default'].'<br/><br/>');
		}
		elseif ($cmd=='create_template')
		{
			$this->create_template();
		}
		elseif ($cmd=='create_form')
		{
			$this->create_form();
		}
		elseif ($cmd=='edit_templatea' || $cmd=='edit_template')
		{
			$this->edit_template();
		}
		elseif ($cmd=='edit_forma')
		{
			$this->edit_form();
		}
		elseif ($cmd=='view_template')
		{
			$this->view_template();
		}
		elseif ($cmd=='style_restore')
		{
			if (file_exists($addonPathData.'/scf_style.css'))
				unlink($addonPathData.'/scf_style.css');
			message($this->SCF_LANG['style_restored']);
		}
		$this->menu();
	}
}


$o = new Edit; //create object
$o->Start(); //execute it
unset($o); //release from memory

//global $config, $gp_menu, $gp_titles;
//echo '<pre>config ';print_r($config); echo '</pre><br/>';
//echo '<pre>';print_r($_POST);echo '</pre>';
