swift-cron-notifier
===================
This script is designed to work with a servers CRON scheduler to send notification emails in batches rather than on-demand.

Here is a create statement for the table that holds notifications:

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recipient` varchar(50) NOT NULL,
  `recipient_name` varchar(50) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `message` blob NOT NULL,
  `sender` varchar(50) DEFAULT NULL,
  `sender_name` varchar(50) DEFAULT NULL,
  `cron` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

Quick Description:

id: pretty self explanitory

time: creation date of the row in database

recipient: the email address you want to send a notification to (example: john@doe.com)

recipient_name: the friendly name of the person getting the email (example: John Doe)

subject: the subject of the email

message: the message

sender: who the email should be from (example: admin@example.com)

sender_name: the friendly name of the sender (example: Example.com Support Team)

cron: boolean flag to signal completed jobs


