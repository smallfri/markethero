<?php

/**
 * Frontend application bootstrap file
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

// define the type of application we are creating.
define('MW_APP_NAME', 'frontend');

// and start an instance of it.
require_once(dirname(__FILE__) . '/apps/init.php');


/*
$config['version'] = '1.0.0';
/*
==========================================================================
v 1.2.6.2                                                       11/07/2016
- ADDED tester for emails to dashboard contoller
- ADDED fix for email addresses to bounch handler
==========================================================================
v 1.2.6.1                                                       11/02/2016
- UPDATED klipfolio controller to only pull back 25 results
- Fixed issue with group emails not saving
==========================================================================
v 1.2.6                                                         11/02/2016
- Updated to require customer_id
==========================================================================
v 1.2.5                                                         10/11/2016
- Updated not to use send_at because its being handled in the main app
==========================================================================
v 1.2.5                                                         10/11/2016
- Updated queue names
- ADDED db queue
- FIXED issue with sms queue
- UPDATED unique id to 23 characters
==========================================================================
v 1.2.4                                                         10/03/2016
- Updated GroupEmailsController to use compliance check
==========================================================================
v 1.2.3                                                         09/29/2016
- Update number of queue workers, sent 32k in 1:14 w 40 queue workers
==========================================================================
v 1.2.2                                                         09/28/2016
- ADDED toggle for queues
- ADDED leads count to group email controller
- ADDED blacklist handling to GroupEmailsController
==========================================================================
v 1.2.1                                                         09/27/2016
- UPDATED queues to write to db AFTER send
==========================================================================
v 1.1.1                                                         09/26/2016
- UPDATED the SendEmail Job to handle the send_at time
==========================================================================
v 1.1.0                                                         09/25/2016
- ADDED queues
==========================================================================
v 1.0.1                                                         09/23/2016
- ADDED impressionwise integration so that it can be toggled on and off.
- ADDED timezone check when sending messages, converting to EST
 =========================================================================
v 1.0.0                                                        09/20/2016
- Initial Release to Production

