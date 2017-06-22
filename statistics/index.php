<?php

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$page = optional_param('page', 'rosters', PARAM_ALPHA);

// Set tabs.
$pages = array('rosters');

$tabtree = array();
foreach ($pages as $pagename) {
    $url = new moodle_url('/local/apsolu/index.php', array('page' => $pagename));
    $tabtree[] = new tabobject($pagename, $url, get_string('settings_statistics_'.$pagename, 'local_apsolu'));
}

// Set default tabs.
if (in_array($page, $pages, true) === false) {
    $page = $pages[0];
}

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_statistics_'.$page);

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabtree, $page);
require(__DIR__.'/rosters.php');
echo $OUTPUT->footer();
