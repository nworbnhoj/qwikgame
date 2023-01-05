<?php

define( "DOMAIN",      'qwikgame.org' );
define( "SUBDOMAIN",   'www' );
define( "HOST",        SUBDOMAIN.'.'.DOMAIN );
define( "QWIK_URL",    'https://'.HOST.'/');

define( "DOC_ROOT",  $_SERVER['DOCUMENT_ROOT']);
define( "PATH_PDF",  'pdf/'     );
define( "PATH_JSON", 'json/'    );


define( "ROOT",         DOC_ROOT.'../'  ); 
define( "PATH_CLASS",   UP.'class/'   );
define( "PATH_DELAYED", UP.'delayed/' );
define( "PATH_HTML",    UP.'html/'    );
define( "PATH_LANG",    UP.'lang/'    );
define( "PATH_MARK",    UP.'mark/'    );
define( "PATH_PLAYER",  UP.'player/'  );
define( "PATH_SERVICE", UP            );
define( "PATH_UPLOAD",  UP.'uploads/' );
define( "PATH_USER",    UP.'player/'  );
define( "PATH_VENUE",   UP.'venue/'   );
define( "PATH_VENUES",  UP.'venues/'  );
define( "PATH_VENDOR",  UP.'vendor/'  );
define( "PATH_LOG",     '/var/log/'.HOST.'.log');
 

?>
