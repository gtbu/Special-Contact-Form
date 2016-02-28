<?php

defined('is_running') or die('Not an entry point...');

global $addonPathData;
if (file_exists($addonPathData.'/contact_form.php'))
	include($addonPathData.'/contact_form.php');

