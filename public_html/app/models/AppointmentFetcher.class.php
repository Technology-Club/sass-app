<?php

/**
 * Created by PhpStorm.
 * User: rdok
 * Date: 9/16/2014
 * Time: 8:02 PM
 */
class AppointmentFetcher {
	const DB_TABLE = "appointment";
	const DB_COLUMN_ID = "id";
	const DB_COLUMN_START_TIME = "start_time";
	const DB_COLUMN_END_TIME = "end_time";
	const DB_COLUMN_COURSE_ID = "course_id";
	const DB_COLUMN_TUTOR_USER_ID = "tutor_user_id";
	const DB_COLUMN_TERM_ID = "term_id";
	const DB_COLUMN_LABEL_MESSAGE = "label_message";
	const DB_COLUMN_LABEL_COLOR = "label_color";

    private static $labels = [
        Appointment::LABEL_MESSAGE_TUTOR_CANCELED,
        Appointment::LABEL_MESSAGE_STUDENT_CANCELED,
        Appointment::LABEL_MESSAGE_STUDENT_NO_SHOW,
        Appointment::LABEL_MESSAGE_TUTOR_NO_SHOW,
        Appointment::LABEL_MESSAGE_COMPLETE,
        Appointment::LABEL_MESSAGE_ADMIN_DISABLED,
    ];

	public static function delete($appointmentId)
	{
		try
		{
			$dbConnection = DatabaseManager::getConnection();

			try
			{
				$dbConnection->beginTransaction();
				$prevTransFromParent = false;
			} catch (PDOException $e)
			{
				$prevTransFromParent = true;
			}

			ReportFetcher::deleteWithAppointmentId($appointmentId);
			AppointmentHasStudentFetcher::delete($appointmentId);

			$query =
				"DELETE FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
				WHERE `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` = :appointment_id";

			$query = $dbConnection->prepare($query);
			$query->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
			$query->execute();


			if ( ! $prevTransFromParent)
			{
				$dbConnection->commit();
			}

			return $query->rowCount();

		} catch (Exception $e)
		{
			if (isset($dbConnection))
			{
				$dbConnection->rollback();
			}
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not delete appointment data." . $e->getMessage());
		}

		return false;
	}

	public static function updateTerm($appointmentId, $newTermId)
	{

		$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `" . self::DB_COLUMN_TERM_ID . "`= :term_id
					WHERE `" . self::DB_COLUMN_ID . "` = :appointment_id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);

			$query->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
			$query->bindParam(':term_id', $newTermId, PDO::PARAM_INT);

			$query->execute();

			return true;
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not update data.");
		}

		return false;
	}

	public static function updateDuration($appointmentId, $newStartTime, $newEndTime)
	{
		$newStartTime = $newStartTime->format(Dates::DATE_FORMAT_IN);
		$newEndTime = $newEndTime->format(Dates::DATE_FORMAT_IN);

		$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `" . self::DB_COLUMN_START_TIME . "`= :start_time,
					`" . self::DB_COLUMN_END_TIME . "`= :end_time
					WHERE `" . self::DB_COLUMN_ID . "` = :appointment_id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
			$query->bindParam(':start_time', $newStartTime, PDO::PARAM_STR);
			$query->bindParam(':end_time', $newEndTime, PDO::PARAM_STR);

			$query->execute();

			return true;
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not update data.");
		}

		return false;
	}

	public static function updateTutor($appointmentId, $newTutorId)
	{
		$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `" . self::DB_COLUMN_TUTOR_USER_ID . "`= :tutor_id
					WHERE `" . self::DB_COLUMN_ID . "` = :appointment_id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
			$query->bindParam(':tutor_id', $newTutorId, PDO::PARAM_INT);
			$query->execute();

			return true;
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not update data.");
		}

		return false;
	}

	public static function insert($dateStart, $dateEnd, $courseId, $studentsIds, $tutorId, $instructorsIds, $termId)
	{
		date_default_timezone_set('Europe/Athens');
		$dateStart = $dateStart->format(Dates::DATE_FORMAT_IN);
		$dateEnd = $dateEnd->format(Dates::DATE_FORMAT_IN);

		try
		{
			$queryInsertUser = "INSERT INTO `" . App::getDbName() . "`.`" . self::DB_TABLE . "` (`" . self::DB_COLUMN_START_TIME .
				"`,			`" . self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`, `" .
				self::DB_COLUMN_TUTOR_USER_ID . "`, `" . self::DB_COLUMN_TERM_ID . "`)
				VALUES(
					:start_time,
					:end_time,
					:course_id,
					:tutor_user_id,
					:term_id
				)";

			$dbConnection = DatabaseManager::getConnection();
			$dbConnection->beginTransaction();

			$queryInsertUser = $dbConnection->prepare($queryInsertUser);
			$queryInsertUser->bindParam(':start_time', $dateStart, PDO::PARAM_STR);
			$queryInsertUser->bindParam(':end_time', $dateEnd, PDO::PARAM_STR);
			$queryInsertUser->bindParam(':course_id', $courseId, PDO::PARAM_STR);
			$queryInsertUser->bindParam(':tutor_user_id', $tutorId, PDO::PARAM_STR);
			$queryInsertUser->bindParam(':term_id', $termId, PDO::PARAM_STR);

			$queryInsertUser->execute();

			// last inserted if of THIS connection
			$appointmentId = $dbConnection->lastInsertId();


			for ($i = 0; $i < sizeof($studentsIds); $i++)
			{
				AppointmentHasStudentFetcher::insert($appointmentId, $studentsIds[$i], $instructorsIds[$i]);
			}

			$dbConnection->commit();

			return $appointmentId;
		} catch (Exception $e)
		{
			if (isset($dbConnection))
			{
				$dbConnection->rollback();
			}
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not insert data into database.");
		}

	}

	public static function updateCourse($appointmentId, $newCourseId)
	{
		$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `" . self::DB_COLUMN_COURSE_ID . "`= :course_id
					WHERE `" . self::DB_COLUMN_ID . "` = :appointment_id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
			$query->bindParam(':course_id', $newCourseId, PDO::PARAM_INT);
			$query->execute();

			return true;
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not update data.");
		}

		return false;
	}

	public static function existsId($id)
	{
		try
		{
			$query = "SELECT COUNT(" . self::DB_COLUMN_ID . ") FROM `" . App::getDbName() . "`.`" .
				self::DB_TABLE . "` WHERE `" . self::DB_COLUMN_ID . "` = :id";

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':id', $id, PDO::PARAM_INT);
			$query->execute();

			if ($query->fetchColumn() === '0')
			{
				return false;
			}
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not check data already exists on database.");
		}

		return true;
	}

	public static function existsTutorsAppointmentsBetween($tutorId, $termId, $startDate, $endDate, $existingAppointmentId = false)
	{
		date_default_timezone_set('Europe/Athens');
		$startDate = $startDate->format(Dates::DATE_FORMAT_IN);
		$endDate = $endDate->format(Dates::DATE_FORMAT_IN);

		try
		{
			$existingAppointment = ($existingAppointmentId !== false) ? "AND `" . self::DB_COLUMN_ID . "` <> :appointment_id" : "";

			$query =
			$query =
				"SELECT COUNT(`" . self::DB_COLUMN_ID . "`)
				FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
				WHERE `" . self::DB_COLUMN_TUTOR_USER_ID . "` = :tutor_user_id
				" . $existingAppointment . "
				AND
				(
					(:start_date >= `" . self::DB_COLUMN_START_TIME . "` AND :start_date < `" . self::DB_COLUMN_END_TIME . "`)
					OR
					(:end_date > `" . self::DB_COLUMN_START_TIME . "` AND :end_date <= `" . self::DB_COLUMN_END_TIME . "`)
					OR
					(`" . self::DB_COLUMN_START_TIME . "` >= :start_date AND `" . self::DB_COLUMN_START_TIME . "` < :end_date)
					OR
					(`" . self::DB_COLUMN_END_TIME . "` > :start_date AND `" . self::DB_COLUMN_END_TIME . "` < :end_date)

				)
				AND `" . self::DB_COLUMN_TERM_ID . "`=:term_id
				AND
				(
				`" . self::DB_COLUMN_LABEL_MESSAGE . "` = :message_pending
				OR
				`" . self::DB_COLUMN_LABEL_MESSAGE . "` = :message_complete
				)";

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);

			$query->bindParam(':tutor_user_id', $tutorId, PDO::PARAM_INT);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);

			$messagePending = Appointment::LABEL_MESSAGE_PENDING;
			$messageComplete = Appointment::LABEL_MESSAGE_COMPLETE;

			$query->bindParam(':message_pending', $messagePending, PDO::PARAM_INT);
			$query->bindParam(':message_complete', $messageComplete, PDO::PARAM_INT);

			if ($existingAppointmentId !== false)
			{
				$query->bindParam(':appointment_id', $existingAppointmentId, PDO::PARAM_INT);
			}

//			throw new Exception("Could not check conflicts with other appointments." . $startDate);

			$query->bindParam(':start_date', $startDate, PDO::PARAM_STR);
			$query->bindParam(':end_date', $endDate, PDO::PARAM_STR);

			$query->execute();

			if ($query->fetchColumn() === '0')
			{
				return false;
			}
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not check conflicts with other appointments.");
		}

		return true;
	}


	public static function belongsToTutor($id, $tutorId)
	{
		try
		{
			$query = "SELECT COUNT(" . self::DB_COLUMN_ID . ")
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
				WHERE `" . self::DB_COLUMN_ID . "` = :id
				AND `" . self::DB_COLUMN_TUTOR_USER_ID . "` = :tutor_id";

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':id', $id, PDO::PARAM_INT);
			$query->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT);
			$query->execute();

			if ($query->fetchColumn() === '0')
			{
				return false;
			}
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not check data already exists on database.");
		}

		return true;
	}

	public static function retrieveSingle($id)
	{
		$query = "SELECT `" . self::DB_COLUMN_START_TIME . "`, `" . self::DB_COLUMN_END_TIME . "`, `" .
			self::DB_COLUMN_COURSE_ID . "`, `" . self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID .
			"`,  `" . self::DB_COLUMN_ID . "`,  `" . self::DB_COLUMN_TERM_ID . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			WHERE `" . self::DB_COLUMN_ID . "`=:id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':id', $id, PDO::PARAM_INT);

			$query->execute();

			return $query->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Something terrible happened . Could not retrieve data from database .: ");
		} // end catch
	}

	public static function retrieveReport($id)
	{
		$query = "SELECT `" . self::DB_COLUMN_START_TIME . "`, `" . self::DB_COLUMN_END_TIME . "`, `" .
			self::DB_COLUMN_COURSE_ID . "`, `" . self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID .
			"`,  `" . self::DB_COLUMN_ID . "`,  `" . self::DB_COLUMN_TERM_ID . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			WHERE `" . self::DB_COLUMN_ID . "`=:id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':id', $id, PDO::PARAM_INT);

			$query->execute();

			return $query->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Something terrible happened . Could not retrieve data from database .: ");
		} // end catch
	}

	public static function retrieveAll()
	{
		$query =
			"SELECT `" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` , `" . self::DB_COLUMN_END_TIME . "`,
			 `" . self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`,  `" .
			self::DB_COLUMN_TUTOR_USER_ID . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "` ORDER BY `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	/**
	 *
	 * select all appointments that have yet to be started from current time:
	 * inner join with courses: course_code & course_name
	 * inner join with all students that are scheduled for this appointment: student_id
	 * @return array
	 * @throws Exception
	 */
	public static function retrievePendingForAllStudents()
	{

		$query = "SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "`,
            `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_END_TIME . "`,
            `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "`,
            `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`,
			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_NAME . "`,
			`" . AppointmentHasStudentFetcher::DB_TABLE . "`.`" . AppointmentHasStudentFetcher::DB_COLUMN_STUDENT_ID . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
				ON `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`
			INNER JOIN `" . App::getDbName() . "`.`" . AppointmentHasStudentFetcher::DB_TABLE . "`
				ON `" . AppointmentHasStudentFetcher::DB_TABLE . "`.`" . AppointmentHasStudentFetcher::DB_COLUMN_APPOINTMENT_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "`
			WHERE `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "`	> :now";

		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':now', $now, PDO::PARAM_STR);
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			App::storeError($e->getMessage());
			throw new Exception("Could not retrieve data from database." . $e);
		}
	}

	public static function retrieveForTerm($termId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` ,
			`" . self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID
			. "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_COLUMN_LABEL_MESSAGE . "`,
			 `" . self::DB_COLUMN_LABEL_COLOR . "`,

			`" . UserFetcher::DB_COLUMN_FIRST_NAME . "`, `" . UserFetcher::DB_COLUMN_LAST_NAME . "`,
			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,

			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_NAME .
			"` AS " . CourseFetcher::DB_TABLE . "_" . CourseFetcher::DB_COLUMN_NAME . ",

			`" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_NAME .
			"` AS " . TermFetcher::DB_TABLE . "_" . TermFetcher::DB_COLUMN_NAME . "

			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
				ON `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "` =
				`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`
			INNER JOIN `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
				ON `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` =
				`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`
			INNER JOIN `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
				ON `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` =
				`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`
			WHERE `" . self::DB_COLUMN_TERM_ID . "` = :term_id
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` ASC";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function retrieveAllOfCurrTerms()
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` AS " . self::DB_TABLE . "_" . self::DB_COLUMN_ID . ",
			`" . self::DB_COLUMN_START_TIME . "` , `" . self::DB_COLUMN_END_TIME . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_LABEL_MESSAGE . "`, `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`,
			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_NAME . "`,
			`" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_FIRST_NAME . "`,
			`" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_LAST_NAME . "`,
			`" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_NAME . "` AS " . TermFetcher::DB_TABLE . "_" . TermFetcher::DB_COLUMN_NAME . "
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
				ON `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`
			INNER JOIN `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
				ON `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`
			INNER JOIN `" . TermFetcher::DB_TABLE . "`
				ON `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`
			WHERE :now BETWEEN `" . TermFetcher::DB_COLUMN_START_DATE . "` AND `" . TermFetcher::DB_COLUMN_END_DATE . "`
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` DESC"; // IS USED BY DASHBOARD FOR SHOWING LATEST APPOINTMENTS


		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':now', $now, PDO::PARAM_STR);
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function retrieveAllOfCurrTermsByTutor($tutorId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` AS " . self::DB_TABLE . "_" .
			self::DB_COLUMN_ID . ", `" . self::DB_COLUMN_START_TIME . "` , `" . self::DB_COLUMN_END_TIME . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_LABEL_COLOR . "`, `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			 `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_NAME . "`,	`" . UserFetcher::DB_TABLE . "`.`" .
			UserFetcher::DB_COLUMN_FIRST_NAME . "`, `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_LAST_NAME
			. "`, `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_NAME . "` AS " . TermFetcher::DB_TABLE . "_"
			. TermFetcher::DB_COLUMN_NAME . "
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
				ON `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`
			INNER JOIN `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
				ON `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`
			INNER JOIN `" . TermFetcher::DB_TABLE . "`
				ON `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` =
					`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`
			WHERE `" . self::DB_COLUMN_TUTOR_USER_ID . "` = :tutor_id AND
			:now BETWEEN `" . TermFetcher::DB_COLUMN_START_DATE . "` AND `" . TermFetcher::DB_COLUMN_END_DATE . "`";

		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':now', $now, PDO::PARAM_STR);

			$query->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT);
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function retrieveTutors($termId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` , `" .
			self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			`" . self::DB_COLUMN_TUTOR_USER_ID . "`, `" . UserFetcher::DB_COLUMN_FIRST_NAME . "` , `" .
			UserFetcher::DB_COLUMN_LAST_NAME . "`, `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`  = `" .
			UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`  = `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` = :term_id
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function getTutorsTeachingCourseOnTerm($courseId, $termId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` , `" .
			self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			`" . self::DB_COLUMN_TUTOR_USER_ID . "`, `" . UserFetcher::DB_COLUMN_FIRST_NAME . "` , `" .
			UserFetcher::DB_COLUMN_LAST_NAME . "`, `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`  = `" .
			UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`  = `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` = :term_id
			AND `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` = :course_id
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);
			$query->bindParam(':course_id', $courseId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function updateLabel($appointmentId, $labelMessage, $labelColor)
	{
		$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `" . self::DB_COLUMN_LABEL_MESSAGE . "`= :label_message, `" . self::DB_COLUMN_LABEL_COLOR . "` =
					:label_color
					WHERE `" . self::DB_COLUMN_ID . "` = :id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':id', $appointmentId, PDO::PARAM_INT);
			$query->bindParam(':label_message', $labelMessage, PDO::PARAM_STR);
			$query->bindParam(':label_color', $labelColor, PDO::PARAM_STR);

			$query->execute();

			return true;
		} catch (Exception $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not update data.");
		}

		return false;
	}


	public static function retrieveBetweenDates($startWeek, $endWeek)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_START_TIME . "` , `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_END_TIME . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			  `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			WHERE
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` BETWEEN :start_week AND :end_week
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` DESC"; // IS USED BY DASHBOARD FOR SHOWING LATEST APPOINTMENTS
		// DO NOT REMOVE. (DESC ID)

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':start_week', $startWeek, PDO::PARAM_STR);
			$query->bindParam(':end_week', $endWeek, PDO::PARAM_STR);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);

		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function retrieveTutorsBetweenDates($tutorId, $startWeek, $endWeek)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_START_TIME . "` , `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_END_TIME . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			  `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			WHERE
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` BETWEEN :start_week AND :end_week
			AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "` = :tutor_id
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` DESC"; // IS USED BY DASHBOARD FOR SHOWING LATEST APPOINTMENTS
		// DO NOT REMOVE. (DESC ID)


		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':start_week', $startWeek, PDO::PARAM_STR);
			$query->bindParam(':end_week', $endWeek, PDO::PARAM_STR);
			$query->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);

		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}


	public static function retrieveAllForSingleTutor($tutorId, $termId)
	{
		$query =
			"SELECT `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_EMAIL . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` , `" . self::DB_COLUMN_END_TIME . "`, `" .
			self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`, `" . self::DB_COLUMN_TUTOR_USER_ID .
			"`, `" . UserFetcher::DB_COLUMN_FIRST_NAME . "` , `" . UserFetcher::DB_COLUMN_LAST_NAME . "`, `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`, `" . CourseFetcher::DB_TABLE . "`.`" .
			CourseFetcher::DB_COLUMN_NAME . "`, `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`  = `" .
			UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`  = `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "` = :tutor_id
			AND `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` = :term_id
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	/**
	 * Appointments are considered completed if 30 minutes have from the passing of it's start time.
	 * @return array
	 * @throws Exception
	 * @internal param $db
	 */
	public static function  retrieveCmpltWithoutRptsOnCurTerms()
	{
		$query =
			"SELECT DISTINCT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_START_TIME . "` , `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_END_TIME . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			LEFT JOIN  `" . App::getDbName() . "`.`" . AppointmentHasStudentFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "`  = `" .
			AppointmentHasStudentFetcher::DB_TABLE . "`.`" . AppointmentHasStudentFetcher::DB_COLUMN_APPOINTMENT_ID . "`
			LEFT JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE TIME_TO_SEC(TIMEDIFF(:now,  `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "`))/60 >= 30
			AND `" . AppointmentFetcher::DB_TABLE . "`.`" .
			self::DB_COLUMN_LABEL_MESSAGE . "` = :pending
			AND :now BETWEEN `" . TermFetcher::DB_COLUMN_START_DATE . "` AND `" . TermFetcher::DB_COLUMN_END_DATE . "`
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";


		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':now', $now, PDO::PARAM_STR);

			$pendingLabel = Appointment::LABEL_MESSAGE_PENDING;
			$query->bindParam(':pending', $pendingLabel, PDO::PARAM_STR);


			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function retrievePending($termId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` ,
			`" . self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`,
			`" . self::DB_COLUMN_TUTOR_USER_ID . "`, `" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			`" . UserFetcher::DB_COLUMN_FIRST_NAME . "` , `" . UserFetcher::DB_COLUMN_LAST_NAME . "`,
			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`  = `" .
			UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`  = `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` = :term_id
			AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` > :now
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);
			$query->bindParam(':now', $now, PDO::PARAM_STR);
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			App::storeError($e->getMessage());
			throw new Exception("Could not retrieve data from database.");
		}
	}

	/**
	 * @param $courseId
	 * @param $termId
	 * @return array
	 * @throws Exception
	 */
	public static function getPendingAppointmentsWithCourse($courseId, $termId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` , `" .
			self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			`" . self::DB_COLUMN_TUTOR_USER_ID . "`, `" . UserFetcher::DB_COLUMN_FIRST_NAME . "` , `" .
			UserFetcher::DB_COLUMN_LAST_NAME . "`, `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`  = `" .
			UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`  = `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` = :term_id
			AND `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` = :course_id
			AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` > :now
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);
			$query->bindParam(':now', $now, PDO::PARAM_STR);
			$query->bindParam(':course_id', $courseId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			App::storeError($e->getMessage());
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function getPendingAppointmentsWithCourseAndTutor($tutorId, $courseId, $termId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` , `" .
			self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			`" . self::DB_COLUMN_TUTOR_USER_ID . "`, `" . UserFetcher::DB_COLUMN_FIRST_NAME . "` , `" .
			UserFetcher::DB_COLUMN_LAST_NAME . "`, `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`  = `" .
			UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`  = `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` = :term_id
			AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "` = :tutor_id
			AND `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` = :course_id
			AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` > :now
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);
			$query->bindParam(':now', $now, PDO::PARAM_STR);
			$query->bindParam(':course_id', $courseId, PDO::PARAM_INT);
			$query->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			App::storeError($e->getMessage());
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function getAppointmentsForTutorAndCourse($tutorId, $courseId, $termId)
	{
		$query =
			"SELECT `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_EMAIL . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` , `" . self::DB_COLUMN_END_TIME . "`, `" .
			self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`, `" . self::DB_COLUMN_TUTOR_USER_ID .
			"`, `" . UserFetcher::DB_COLUMN_FIRST_NAME . "` , `" . UserFetcher::DB_COLUMN_LAST_NAME . "`, `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`, `" . CourseFetcher::DB_TABLE . "`.`" .
			CourseFetcher::DB_COLUMN_NAME . "`, `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_COLOR . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`  = `" .
			UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`  = `" .
			CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "`
			INNER JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`  = `" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "` = :tutor_id
			AND `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` = :term_id
			AND `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` = :course_id
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT);
			$query->bindParam(':term_id', $termId, PDO::PARAM_INT);
			$query->bindParam(':course_id', $courseId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

	/**
	 * Retrieve pending appointments for current terms for given tutorId.
	 * Pending appointments start time will be at least 30 minutes away from it's starting time
	 * @param $tutorId
	 * @return array
	 * @throws Exception
	 */
	public static function retrievePendingForCurrentTerms($tutorId)
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_START_TIME . "` , `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_END_TIME . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`, `" . self::DB_TABLE . "`.`" .
			self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`,
			`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "`
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			LEFT JOIN  `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
			ON `" . App::getDbName() . "`.`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`=`" .
			TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "`
			WHERE `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "` = :tutor_id
			AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "` ='" . Appointment::LABEL_MESSAGE_PENDING . "'
			AND :now BETWEEN `" . TermFetcher::DB_COLUMN_START_DATE . "` AND `" . TermFetcher::DB_COLUMN_END_DATE . "`
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` DESC";

		try
		{
			$now = App::getCurrentTime();

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);

			$query->bindParam(':now', $now, PDO::PARAM_STR);
			$query->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT);

			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);

		} catch (PDOException $e)
		{
			App::storeError($e->getMessage());
			throw new Exception("Could not retrieve data from database.");
		}
	}

	public static function retrieveForCurrentTerms()
	{
		$query =
			"SELECT `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "` , `" . self::DB_COLUMN_START_TIME . "` ,
			`" . self::DB_COLUMN_END_TIME . "`, `" . self::DB_COLUMN_COURSE_ID . "`,  `" . self::DB_COLUMN_TUTOR_USER_ID
			. "`,  `" . self::DB_COLUMN_TUTOR_USER_ID . "`,  `" . self::DB_COLUMN_LABEL_MESSAGE . "`,
			 `" . self::DB_COLUMN_LABEL_COLOR . "`,

			`" . UserFetcher::DB_COLUMN_FIRST_NAME . "`, `" . UserFetcher::DB_COLUMN_LAST_NAME . "`,
			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_CODE . "`,

			`" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_NAME .
			"` AS " . CourseFetcher::DB_TABLE . "_" . CourseFetcher::DB_COLUMN_NAME . ",

			`" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_NAME .
			"` AS " . TermFetcher::DB_TABLE . "_" . TermFetcher::DB_COLUMN_NAME . "

			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			INNER JOIN `" . App::getDbName() . "`.`" . UserFetcher::DB_TABLE . "`
				ON `" . UserFetcher::DB_TABLE . "`.`" . UserFetcher::DB_COLUMN_ID . "` =
				`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TUTOR_USER_ID . "`
			INNER JOIN `" . App::getDbName() . "`.`" . CourseFetcher::DB_TABLE . "`
				ON `" . CourseFetcher::DB_TABLE . "`.`" . CourseFetcher::DB_COLUMN_ID . "` =
				`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_COURSE_ID . "`
			INNER JOIN `" . App::getDbName() . "`.`" . TermFetcher::DB_TABLE . "`
				ON `" . TermFetcher::DB_TABLE . "`.`" . TermFetcher::DB_COLUMN_ID . "` =
				`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "`
			WHERE :now BETWEEN `" . TermFetcher::DB_COLUMN_START_DATE . "` AND `" . TermFetcher::DB_COLUMN_END_DATE . "`
			ORDER BY `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` ASC";

		try
		{
			date_default_timezone_set('Europe/Athens');
			$now = new DateTime();
			$now = $now->format(Dates::DATE_FORMAT_IN);

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':now', $now, PDO::PARAM_STR);
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
	}

    public static function retrieveByGroupedDateForTermIds($termIds, $groupBy = ['year','month', 'hour'], $labels = []) {
        if(empty($labels)){
            $labels = self::$labels;
        }
        foreach($termIds as $key => $termId){
            $termBindParams[] = ":term_id_{$key}";
        }
        $termBindParams = implode(',', $termBindParams);
        $labelBindParams = "'" . implode("', '", $labels) . "'";

        $groupByQuery = '';
        foreach($groupBy as $group){
            $groupByQuery .= "GROUP BY {$group}(date), ";
        }
        $groupByQuery = rtrim($groupByQuery, ', ');

		$query =
            "SELECT COUNT(`" . self::DB_TABLE . "`.`" . self::DB_COLUMN_ID . "`) as total,
            `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_START_TIME . "` as date

			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
			WHERE `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "` in ({$termBindParams})
            AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "` in ({$labelBindParams})
            $groupByQuery
            ";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
            foreach($termIds as $key => $termId){
                $query->bindValue(":term_id_{$key}", $termId, PDO::PARAM_INT);
            }
			$query->execute();

			return $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
    }

    public static function countForTermIds($termIds, $labels = []) {
        if(empty($labels)){
            $labels = self::$labels;
        }

        foreach($termIds as $key => $termId){
            $termBindParams[] = ':term_id_' . $key;
        }
        $termBindParams = implode(', ', $termBindParams);

        $labelBindParams = "'" . implode("', '", $labels) . "'";

		$query =
            "SELECT COUNT(" . self::DB_COLUMN_ID . ") AS total
			FROM `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
            WHERE `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_TERM_ID . "` in ({$termBindParams})
            AND `" . self::DB_TABLE . "`.`" . self::DB_COLUMN_LABEL_MESSAGE . "` in ({$labelBindParams})";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
            foreach($termIds as $key => $termId){
               $query->bindValue(":term_id_{$key}", $termId, PDO::PARAM_INT);
            }
			$query->execute();

			return $query->fetch(PDO::FETCH_ASSOC)['total'];
		} catch (PDOException $e)
		{
			Mailer::sendDevelopers($e->getMessage(), __FILE__);
			throw new Exception("Could not retrieve data from database.");
		}
    }
}
