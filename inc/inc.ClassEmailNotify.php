<?php
/**
 * Implementation of notifation system using email
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("inc.ClassNotify.php");
require_once("Mail.php");

/**
 * Class to send email notifications to individuals or groups
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_EmailNotify extends SeedDMS_Notify {
	/**
	 * Instanz of DMS
	 */
	protected $_dms;

	protected $smtp_server;

	protected $smtp_port;

	protected $smtp_user;

	protected $smtp_password;

	protected $from_address;

	function __construct($dms, $from_address='', $smtp_server='', $smtp_port='', $smtp_username='', $smtp_password='') { /* {{{ */
		$this->_dms = $dms;
		$this->smtp_server = $smtp_server;
		$this->smtp_port = $smtp_port;
		$this->smtp_user = $smtp_username;
		$this->smtp_password = $smtp_password;
		$this->from_address = $from_address;
	} /* }}} */

	/**
	 * Send mail to individual user
	 *
	 * @param mixed $sender individual sending the email. This can be a
	 *        user object or a string. If it is left empty, then
	 *        $this->from_address will be used.
	 * @param object $recipient individual receiving the mail
	 * @param string $subject key of string containing the subject of the mail
	 * @param string $message key of string containing the body of the mail
	 * @param array $params list of parameters which replaces placeholder in
	 *        the subject and body
	 * @return false or -1 in case of error, otherwise true
	 */
	function toIndividual($sender, $recipient, $subject, $message, $params=array()) { /* {{{ */
		global $settings;
		if ($recipient->isDisabled() || $recipient->getEmail()=="") return 0;

		if(!is_object($recipient) && strcasecmp(get_class($recipient), "SeedDMS_Core_User")) {
			return -1;
		}
		if (is_object($sender) && !strcasecmp(get_class($sender), "SeedDMS_Core_User")) {
			$from = $sender->getFullName() ." <". $sender->getEmail() .">";
		} elseif(is_string($sender) && trim($sender) != "") {
			$from = $sender;
		} else
			return -1;


		if(is_object($sender) && strcasecmp(get_class($sender), "SeedDMS_Core_User")) {
			$from = $sender->getFullName() ." <". $sender->getEmail() .">";
		} elseif(is_string($sender) && trim($sender) != "") {
			$from = $sender;
		} else {
			$from = $this->from_address;
		}

		$lang = $recipient->getLanguage();

		$message = getMLText("email_header", array(), "", $lang)."\r\n\r\n".getMLText($message, $params, "", $lang);
		$message .= "\r\n\r\n".getMLText("email_footer", array(), "", $lang);

		$headers = array ();
		$headers['From'] = $from;
		$headers['To'] = $recipient->getEmail();
		$headers['Subject'] = getMLText($subject, $params, "", $lang);
		$headers['MIME-Version'] = "1.0";
		$headers['Content-type'] = "text/plain; charset=utf-8";

		$mail_params = array();
		if($this->smtp_server) {
			$mail_params['host'] = $this->smtp_server;
			if($this->smtp_port) {
				$mail_params['port'] = $this->smtp_port;
			}
			if($this->smtp_user) {
				$mail_params['auth'] = true;
				$mail_params['username'] = $this->smtp_user;
				$mail_params['password'] = $this->smtp_password;
			}
			$mail = Mail::factory('smtp', $mail_params);
		} else {
			$mail = Mail::factory('mail', $mail_params);
		}
 
		$result = $mail->send($recipient->getEmail(), $headers, $message);
		if (PEAR::isError($result)) {
			return false;
		} else {
			return true;
		}

/*
		$headers   = array();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/plain; charset=utf-8";
		$headers[] = "From: ". $from;

		$lang = $recipient->getLanguage();
		$message = getMLText("email_header", array(), "", $lang)."\r\n\r\n".getMLText($message, $params, "", $lang);
		$message .= "\r\n\r\n".getMLText("email_footer", array(), "", $lang);

		$subject = "=?UTF-8?B?".base64_encode(getMLText($subject, $params, "", $lang))."?=";
		mail($recipient->getEmail(), $subject, $message, implode("\r\n", $headers));

		return true;
*/
	} /* }}} */

	function toGroup($sender, $groupRecipient, $subject, $message, $params=array()) { /* {{{ */
		if ((!is_object($sender) && strcasecmp(get_class($sender), "SeedDMS_Core_User")) ||
				(!is_object($groupRecipient) && strcasecmp(get_class($groupRecipient), "SeedDMS_Core_Group"))) {
			return -1;
		}

		foreach ($groupRecipient->getUsers() as $recipient) {
			$this->toIndividual($sender, $recipient, $subject, $message, $params);
		}

		return true;
	} /* }}} */

	function toList($sender, $recipients, $subject, $message, $params=array()) { /* {{{ */
		if ((!is_object($sender) && strcasecmp(get_class($sender), "SeedDMS_Core_User")) ||
				(!is_array($recipients) && count($recipients)==0)) {
			return -1;
		}

		foreach ($recipients as $recipient) {
			$this->toIndividual($sender, $recipient, $subject, $message, $params);
		}

		return true;
	} /* }}} */
}
?>