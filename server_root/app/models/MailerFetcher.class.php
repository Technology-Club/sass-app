<?php

/**
 * Created by PhpStorm.
 * User: rdok
 * Date: 10/10/2014
 * Time: 4:14 AM
 */
class MailerFetcher
{
	const DB_TABLE = "mail";
	const DB_COLUMN_LAST_SENT = "last_sent";
	const MAX_MAILS_PER_MINUTE = 19;

	public static function canSendMail() {
		$db = DatabaseManager::getConnection();
		date_default_timezone_set('Europe/Athens');

		try {
			$sql = "SELECT COUNT(`" . self::DB_COLUMN_LAST_SENT . "`)
			FROM `" . DB_NAME . "`.`" . self::DB_TABLE . "`
			WHERE `" . self::DB_COLUMN_LAST_SENT . "` >= now() - INTERVAL 1 MINUTE";

			$query = $db->getConnection()->prepare($sql);
			$query->execute();

			if ($query->fetchColumn() >= self::MAX_MAILS_PER_MINUTE) return false;
		} catch (Exception $e) {
			throw new Exception("Could not check how mails data. Aborting.");
		}

		return true;
	}

	public static function updateMailSent($db) {
		date_default_timezone_set('Europe/Athens');
		$dateNow = new DateTime();
		$dateNow = $dateNow->format(Dates::DATE_FORMAT_IN);


		try {
			$query = "INSERT INTO `" . DB_NAME . "`.`" . self::DB_TABLE . "`
				VALUES(
					:now
				)";

			$query = $db->getConnection()->prepare($query);
			$query->bindParam(':now', $dateNow, PDO::PARAM_STR);
			$query->execute();
			return true;
		} catch (Exception $e) {
			throw new Exception("Could not data into database.");
		}
	}
} 