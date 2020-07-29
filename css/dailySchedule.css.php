<?php

if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1); // File must be accessed by logon page so without login
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

define('ISLOADEDBYSTEELSHEET', '1');

session_cache_limiter('public');

if(is_file(__DIR__.'/../../main.inc.php')) $dir = __DIR__.'/../../';
else  if(is_file(__DIR__.'/../../../../main.inc.php'))$dir = __DIR__.'/../../../../';
else $dir = __DIR__.'/../../../';

require_once $dir.'main.inc.php'; // __DIR__ allow this script to be included in custom themes
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
if (empty($conf->global->OR_ACTIVITYPLANNING_IMPROD_COLOR)) dolibarr_set_const($db, 'OR_ACTIVITYPLANNING_IMPROD_COLOR', '#4f93d6');
if (empty($conf->global->OR_ACTIVITYPLANNING_INTIME_COLOR)) dolibarr_set_const($db, 'OR_ACTIVITYPLANNING_INTIME_COLOR', 'green');
if (empty($conf->global->OR_ACTIVITYPLANNING_LATE_COLOR)) dolibarr_set_const($db, 'OR_ACTIVITYPLANNING_LATE_COLOR', 'red');

?>

.timeline::before{
	background-color: transparent;
}

.ui-resizable-handle {
	background-color: transparent;
}

.timeline {
	margin:0;
}

.jq-schedule .sc_bar.improd {
	background: <?php print $conf->global->OR_ACTIVITYPLANNING_IMPROD_COLOR; ?>;
}

.jq-schedule .sc_bar.late {
	background: <?php print $conf->global->OR_ACTIVITYPLANNING_LATE_COLOR; ?>;
}

.jq-schedule .sc_bar.in-time {
	background: <?php print $conf->global->OR_ACTIVITYPLANNING_INTIME_COLOR; ?>;
}

.jq-schedule div.ui-resizable-handle.ui-resizable-e {
	cursor: pointer;
	background-color: transparent;
	box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.4);
}
