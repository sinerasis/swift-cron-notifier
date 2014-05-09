<?php
/*
Name: swift-cron-notifier
Author: Jeff Baker (sinerasis at gmail dot come)
Date: May 2014

This script is designed to work with the servers CRON scheduler, it will check the notifications table
and send batch notifications to admins and individual notifications to other recipients.
*/

/******************************************************************************************/
/* Mail server and assorted mail related options                                          */
/******************************************************************************************/

$server_host = '';								// Hostname of mail server (example: smpt.gmail.com).
$server_port = 465; 							// Port mail server is using (integer) (example: 465).
$server_ssl = 'ssl'; 							// Tells SwiftMailer to use SSL or not, set to 'ssl' to enable or blank string to disable.
$server_from = ''; 								// The address notifications will be from (that users will see in the FROM field of emails).
$server_user = ''; 								// Username for mail server (note: this is NOT the same as the server_from field above).
$server_pass = ''; 								// The password needed to authenticate with the mail server, pairs with server_user.
$server_name = ''; 								// Shows up as the name users see associated with the notification emails.
$admin_subject = '';							// This is the subject that emails to admins (only admins) will see.
$admin_subject_include_count = true;			// Boolean to set if the number of notifications will be included in the admin email subject.
$default_timezone = 'America/Los_Angeles';		// Set the timezone you want your emails to be coded with. (example: America/Los_Angeles)

// this array should include all administrators that will get ALL notifications
// the emails defined in this array get an email REGARDLESS of the email address defined in the database
// key is address, value is friendly name. Example: Array('john@doe.com'=>'John Doe', 'jane@doe.com'=>'Jane Doe', 'whatever@example.com'=>'Example.com Engineers')
$admins = Array(''=>'');

// swift mailer location
// this should be the relative path to the required swift mailer library (version 5.1.0 is current at time of writing)
// example: Swift-5.1.0/lib/swift_required.php
$swift_mailer = 'Swift-5.1.0/lib/swift_required.php';

/******************************************************************************************/
/* Database options (MySQL)                                                               */
/******************************************************************************************/

$mysql_host = '';								// Hostname of database server (example: localhost).
$mysql_port = 3306;								// Port (integer) (example: 3306).
$mysql_user = '';								// Username.
$mysql_pass = '';								// Password.
$notification_table = '';						// Database table that holds all notification information (example: `common`.`notification_table`).

/******************************************************************************************/
/* We shouldn't need to mess with anything below here unless something crazy has changed. */
/******************************************************************************************/

// create DSN string for use with PDO class
$mysql_dsn = 'mysql:host=' . $mysql_host . ';';
if(isset($mysql_port) && is_numeric($mysql_port)) {
	$mysql_dsn .= 'port=' . $mysql_port . ';';
}

// setup pdo handler for database
$pdo = new PDO($mysql_dsn, $mysql_user, $mysql_pass);

// set the default timezone
date_default_timezone_set($default_timezone);

// just the addresses since we'll be using them later
$admins_addresses = array_keys($admins);

// grab notifications from database
$n = Array();
foreach($pdo->query("SELECT `time`, `recipient`, `recipient_name`, `subject`, `message`, `sender`, `sender_name` FROM " . $notification_table . " WHERE `cron` = 0 ORDER BY `time` DESC;") as $row) {
	$n[] = $row;
}

// send out batch notification to administrators
if(count($n) > 0) {
	// we require the SwiftMailer class
	require_once($swift_mailer);
	
	// setup the transport
	$transport = Swift_SmtpTransport::newInstance($server_host, $server_port, $server_ssl)
		->setUsername($server_user)
		->setPassword($server_pass);
	
	// setup the mailer
	$mailer = Swift_Mailer::newInstance($transport);
	
	// set subject
	if($admin_subject_include_count) {
		$admin_subject .= ': ' . count($n) . ' new';
	}
	
	// create message html and plain text
	$message_html = '<html><head><title>' . $admin_subject . '</title></head><body style="font-family:verdana,sans-serif;font-size:100%;background-color:#ffffff;color:#000000;"><h1 style="font-size:1em;">New notification(s) have been sent:</h1><table style="border-collapse:collapse;">';
	$message_plain = 'New notification(s) have been sent:\n\n';
	foreach($n as $notify) {
		// append notification info to html/plain messages
		$message_html .= '<tr><th style="text-align:right;border:1px solid #ddd;">Time:</th><td style="border:1px solid #ddd;">' . $notify['time'] . '</td></tr>
			<tr><th style="text-align:right;border:1px solid #ddd;">Recipient:</th><td style="border:1px solid #ddd;">' . $notify['recipient'] . ' (' . $notify['recipient_name'] . ')</td></tr>
			<tr><th style="text-align:right;border:1px solid #ddd;">Subject:</th><td style="border:1px solid #ddd;">' . $notify['subject'] . '</td></tr>
			<tr><th style="text-align:right;border:1px solid #ddd;">Sender:</th><td style="border:1px solid #ddd;">' . $notify['sender'] . ' (' . $notify['sender_name'] . ')</td></tr>
			<tr><th style="text-align:right;border:1px solid #ddd;">Message:</th><td style="border:1px solid #ddd;">' . $notify['message'] . '</td></tr>
			<tr><td colspan="2" style="border:1px solid #ddd;">&nbsp;</td></tr>';
		$message_plain .= 'Time: ' . $notify['time'] . '\n
			Recipient: ' . $notify['recipient'] . ' (' . $notify['recipient_name'] . ')\n
			Subject: ' . $notify['subject'] . '\n
			Sender: ' . $notify['sender'] . ' (' . $notify['sender_name'] . ')\n
			Message: ' . $notify['message'] . '\n\n';
		
		// send an individual notification if the recipient isn't an admin
		if(!in_array($notify['recipient'], $admins_addresses)) {
			// setup message
			$message_individual = Swift_Message::newInstance($notify['subject'])
				->setFrom(Array($server_from=>$server_name))
				->setTo(Array($notify['recipient']))
				->setBody($notify['message']);
				
			// send individual message
			$mailer->send($message_individual);
		}
	}
	$message_html .= '</table><p>' . count($n) . ' total</p></body></html>';
	$message_plain .= count($n) . ' records total';
	
	// setup message
	$message = Swift_Message::newInstance($admin_subject)
		->setFrom(Array($server_from=>$server_name))
		->setTo($admins) // note that all admin names/address are included in a single message (and therefore visible to all that receive the email)
		->setBody($message_html, 'text/html')
		->addPart($message_plain, 'text/plain');
		
	// sends message
	$mailer->send($message);
	
	// updates the cron flag in the notification table so we know we've already processed these records
	$pdo->query("UPDATE " . $notification_table . " SET `cron` = 1 WHERE `cron` != 1;");
}
?>
