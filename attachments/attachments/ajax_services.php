<?php

/**
 * [dati][comunicazioni] ajax_services.php: servizi ajax
 *
 * @author Giada & Virginio Laurini @ Pingitore Informatica
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.inc.php';


# Classi necessarie
require_once DIR_CLASS . 'gAjax.class.php';
require_once  DIR_LIB. 'attachments/attachment.lib.php';


$gAjax = new gAjax(array(
    'viewAttachment' => 'viewAttachment',
    'deleteAttachment' => 'deleteAttachment',
));

echo $gAjax->router($_POST['cmd']);
exit;