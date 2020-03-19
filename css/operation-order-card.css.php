<?php

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1); // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');


define('ISLOADEDBYSTEELSHEET', '1');





session_cache_limiter('public');

if(is_file(__DIR__.'/../../main.inc.php')) $dir = __DIR__.'/../../';
else  if(is_file(__DIR__.'/../../../../main.inc.php'))$dir = __DIR__.'/../../../../';
else $dir = __DIR__.'/../../../';

require_once $dir.'main.inc.php'; // __DIR__ allow this script to be included in custom themes
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
// and permission, so we can later calculate number of top menu ($nbtopmenuentries) according to user profile.
if (empty($user->id) && !empty($_SESSION['dol_login']))
{
    $user->fetch('', $_SESSION['dol_login'], '', 1);
    $user->getrights();
}


// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');


$colortextlink = '10, 20, 100';
$colortextlink       = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TEXTLINK) ? $colortextlink : $conf->global->THEME_ELDY_TEXTLINK) : (empty($user->conf->THEME_ELDY_TEXTLINK) ? $colortextlink : $user->conf->THEME_ELDY_TEXTLINK);
$colortextlink = 'rgb('.$colortextlink.')';
?>


.add-line-form-wrap{
	margin: 20px 0;
	border: 1px solid #c3c3c3;
	padding: 20px 10px;
}

.add-line-form-title{
	margin-bottom: 10px;
	padding-bottom: 10px;
	border-bottom: 1px solid #c3c3c3;

	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-size: 1.3em;
}

.add-line-form-body{

}

.table-full{
	width: 100%;
}

.operation-order-det-element-element-action-btn{
	padding-top: 5px;
}

.button-xs{
	display: inline-block;
	font-weight: 400;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
	border: 1px solid <?php print $colortextlink; ?>;
	padding: .3em .3em;

	font-size: .9em;
	line-height: 1;
	border-radius: .25rem;
	transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;

	color: <?php print $colortextlink; ?>;
	background-color: transparent;
	background-image: none;
}

.button-xs:hover {
	 color: #fff;
	text-decoration: none;
	 background-color: <?php print $colortextlink; ?>;
	 border-color: <?php print $colortextlink; ?>;
 }