<?php

    // this file references all the main classes that should be required / used when initialising an API call etc.

    // mysql class
    require_once(DOCUMENT_ROOT.'/php/classes/mysql.php');
    
    // file finder class
    require_once(DOCUMENT_ROOT.'/php/classes/file_finder.php');

    // api handler class
    require_once(DOCUMENT_ROOT.'/php/classes/api_handler.php');

    // XSS class
    require_once(DOCUMENT_ROOT.'/php/classes/xss.php');

?>