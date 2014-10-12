<?php
/**
 *
 */
ob_start();

#starting the staff session
session_start();

require "config/app.php";
require ROOT_PATH . "config/DatabaseManager.class.php";

// identical to require; but php will include it only if it has not already been included
require_once ROOT_PATH . "controllers/General.class.php";
require_once ROOT_PATH . "controllers/App.class.php";
require_once ROOT_PATH . "controllers/Person.class.php";
require_once ROOT_PATH . "controllers/User.class.php";
require_once ROOT_PATH . "controllers/Admin.class.php";
require_once ROOT_PATH . "controllers/Tutor.class.php";
require_once ROOT_PATH . "controllers/TutorHasCourseHasTerm.class.php";
require_once ROOT_PATH . "controllers/Term.class.php";
require_once ROOT_PATH . "controllers/Appointment.class.php";
require_once ROOT_PATH . "controllers/Schedule.class.php";
require_once ROOT_PATH . "controllers/Report.class.php";
require_once ROOT_PATH . "controllers/Secretary.class.php";
require_once ROOT_PATH . "controllers/Instructor.class.php";
require_once ROOT_PATH . "controllers/Major.class.php";
require_once ROOT_PATH . "controllers/Course.class.php";
require_once ROOT_PATH . "controllers/Student.class.php";
require_once ROOT_PATH . "controllers/Dates.class.php";
require_once ROOT_PATH . "controllers/Mailer.class.php";


require_once ROOT_PATH . "models/UserFetcher.class.php";
require_once ROOT_PATH . "models/TutorFetcher.class.php";
require_once ROOT_PATH . "models/TutorHasCourseHasTermFetcher.class.php";
require_once ROOT_PATH . "models/TermFetcher.class.php";
require_once ROOT_PATH . "models/AppointmentFetcher.class.php";
require_once ROOT_PATH . "models/AppointmentHasStudentFetcher.class.php";
require_once ROOT_PATH . "models/ScheduleFetcher.class.php";
require_once ROOT_PATH . "models/ReportFetcher.class.php";
require_once ROOT_PATH . "models/InstructorFetcher.class.php";
require_once ROOT_PATH . "models/UserTypesFetcher.class.php";
require_once ROOT_PATH . "models/MajorFetcher.class.php";
require_once ROOT_PATH . "models/CourseFetcher.class.php";
require_once ROOT_PATH . "models/StudentFetcher.class.php";
require_once ROOT_PATH . "models/StudentBroughtAlongFetcher.class.php";
require_once ROOT_PATH . "models/MailerFetcher.class.php";

$errors = array();

try {
//	$staff = new Users(->getDbConnection());
	$general = new General();


// retrieves data if a user is logged in
	if ($general->loggedIn() === true) {

		// instantiate user class & connect to db.
		$id = $_SESSION['id']; // getting user's id from the session.4

		$data = User::getSingle($id);

		if (strcmp($data['type'], 'tutor') === 0) {
			$tutor = Tutor::getSingle($id);
			$user = new Tutor($data['id'], $data['f_name'], $data['l_name'], $data['email'], $data['mobile'], $data['img_loc'], $data['profile_description'], $data['date'], $data['type'], $data['active'], $tutor[MajorFetcher::DB_COLUMN_NAME]);
		} else if (strcmp($data['type'], 'secretary') === 0) {
			$user = new Secretary($data['id'], $data['f_name'], $data['l_name'], $data['email'], $data['mobile'], $data['img_loc'], $data['profile_description'], $data['date'], $data['type'], $data['active']);
		} else if (strcmp($data['type'], 'admin') === 0) {
			$user = new Admin($data['id'], $data['f_name'], $data['l_name'], $data['email'], $data['mobile'], $data['img_loc'], $data['profile_description'], $data['date'], $data['type'], $data['active']);
		} else {
			throw new Exception("Something terrible has happened with the database. <br/>The software developers will tremble with fear.");
		}
	}

} catch (Exception $e) {
	// if no database connection available this app is not able to work.
	$_SESSION['errors'] = $e->getMessage();
	header('Location: ' . BASE_URL . 'error-500.php');
	exit();
}
