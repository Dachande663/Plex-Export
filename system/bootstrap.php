<?php

$_timer_start = microtime(true);
ini_set('memory_limit', '1024M');

require_once 'functions.php';
require_once 'classes/gettext/mo.php';
$_PLEX_MO = new Mo();

require_once 'classes/plexapi.php';
require_once 'classes/plexexport.php';
require_once 'classes/plextemplategenerator.php';
require_once 'classes/plextemplatehelper.php';
require_once 'classes/plextransport.php';

require_once 'models/plexitem.php';
require_once 'models/plexitem_episode.php';
require_once 'models/plexitem_movie.php';
require_once 'models/plexitem_season.php';
require_once 'models/plexitem_show.php';
require_once 'models/plexlibrary.php';
require_once 'models/plexmedia.php';
require_once 'models/plexsection.php';
require_once 'models/plexsection_movie.php';
require_once 'models/plexsection_show.php';

