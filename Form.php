<?php

namespace Addon\SCF{

	defined('is_running') or die('Not an entry point...');

	class Form{

		function Start(){
			global $addonPathData;

			if( file_exists($addonPathData.'/contact_form.php') ){
				include($addonPathData.'/contact_form.php');
			}


		}

	}
}

