swift-cron-notifier
===================
This script is designed to work with a servers CRON scheduler to send notification emails in batches rather than on-demand.

Dependencies:
-------------------
SwiftMailer is a great PHP class that allows using an external mail server very easily.

http://swiftmailer.org/

Database
-------------------
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

1. id: pretty self explanitory
2. time: creation date of the row in database
3. recipient: the email address you want to send a notification to (example: john@doe.com)
4. recipient_name: the friendly name of the person getting the email (example: John Doe)
5. subject: the subject of the email
6. message: the message
7. sender: who the email should be from (example: admin@example.com)
8. sender_name: the friendly name of the sender (example: Example.com Support Team)
9. cron: boolean flag to signal completed jobs
