<?php

/**
 * Class User will contain prototype for staff; tutors, secretary and admin.
 * Log In, Log Out.
 */
abstract class User extends Person {

	const ADMIN = 'admin';
	const TUTOR = 'tutor';
	const SECRETARY = 'secretary';
	const ACTIVE_STRING = 'activateAccount';
	const DEACTIVE_STRING = 'deactivateAccount';
	const ACTIVE_STATUS = self::PASSWORD_EXPIRATION_TIME_MINUTES;
	const DEACTIVE_STATUS = 0;
	const ACCOUNT_STATUS_ACTIVATED = '1';

	// representation of database info
	const DB_TABLE = "user";
	const DB_MOBILE_NUM = "mobile";
	const DB_COLUMN_FIRST_NAME = "f_name";
	const DB_COLUMN_LAST_NAME = "l_name";
	const DB_COLUMN_PROFILE_DESCRIPTION = "profile_description";
	const DB_COLUMN_ID = "id";
	const DB_COLUMN_EMAIL = "email";
	const DB_COLUMN_GEN_STRING = "gen_string";
	const DB_COLUMN_USER_TYPES_ID = "user_types_id";
	const PASSWORD_EXPIRATION_TIME_MINUTES = 60;
	private $avatarImgLoc;
	private $profileDescription;
	private $dateAccountCreated;
	private $userType;
	private $accountActiveStatus;

	/**
	 * Constructor
	 * @param $id
	 * @param $firstName
	 * @param $lastName
	 * @param $email
	 * @param $mobileNum
	 * @param $avatarImgLoc
	 * @param $profileDescription
	 * @param $dateAccountCreated
	 * @param $userType
	 * @param $accountActiveStatus
	 * @internal param $database
	 */
	public function __construct($id, $firstName, $lastName, $email, $mobileNum, $avatarImgLoc, $profileDescription, $dateAccountCreated, $userType, $accountActiveStatus)
	{
		parent::__construct($id, $firstName, $lastName, $email, $mobileNum);

		$this->setAvatarImgLoc($avatarImgLoc);
		$this->setProfileDescription($profileDescription);
		$this->setDateAccountCreated($dateAccountCreated);
		$this->setUserType($userType);
		$this->setActive($accountActiveStatus);
	}

	/**
	 * @param mixed $avatarImgLoc
	 */
	private function setAvatarImgLoc($avatarImgLoc)
	{
		$this->avatarImgLoc = $avatarImgLoc;
	}

	/**
	 * @param mixed $profileDescription
	 */
	private function setProfileDescription($profileDescription)
	{
		$this->profileDescription = $profileDescription;
	} // end __construct

	/**
	 * @param mixed $dateAccountCreated
	 */
	private function setDateAccountCreated($dateAccountCreated)
	{
		$this->dateAccountCreated = $dateAccountCreated;
	}

	/**
	 * @param mixed $userType
	 */
	private function setUserType($userType)
	{
		$this->userType = $userType;
	}

	/**
	 * @param mixed $active
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

	/**
	 * Returns all information of a user given his email.
	 * @param $id
	 * @return mixed
	 * @throws Exception
	 * @internal param $db
	 */
	public static function  getSingle($id)
	{
		self::validateId($id);

		return UserFetcher::retrieveSingle($id);
	}

	public static function validateId($id)
	{
		if ( ! preg_match('/^[0-9]+$/', $id) || ! UserFetcher::existsId($id))
		{
			throw new Exception('Data tempering detected. Aborting.');
		}
	}

	public static function updateActiveStatus($id, $oldStatus)
	{
		$newStatus = $oldStatus == 1 ? 0 : 1;

		try
		{
			$query = "UPDATE `" . App::getDbName() . "`.`user` SET `active`= :accountStatus WHERE `id`=:id";

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':accountStatus', $newStatus, PDO::PARAM_INT);
			$query->bindParam(':id', $id, PDO::PARAM_INT);

			$query->execute();

			return true;
		} catch (PDOException $e)
		{
			throw new Exception("Could not update database.");
		} // end catch
	}

	static function updateProfile($id, $firstName, $lastName, $prevMobileNum, $newMobileNum, $description)
	{
		$firstName = trim($firstName);
		$lastName = trim($lastName);
		$newMobileNum = trim($newMobileNum);
		$description = trim($description);

		$isProfileDataCorrect = self::isProfileDataCorrect($firstName, $lastName,
			$newMobileNum);

		if ($isProfileDataCorrect !== true)
		{
			throw new Exception(implode("<br>", $isProfileDataCorrect)); // the array of errors messages
		}


		$query = "UPDATE `" . App::getDbName() . "`.user
					SET `f_name`= :first_name, `l_name`= :last_name, `mobile`= :mobile,
						`profile_description`= :profile_description
						WHERE `id`= :id";
		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':first_name', $firstName, PDO::PARAM_STR);
			$query->bindParam(':last_name', $lastName, PDO::PARAM_STR);
			$query->bindParam(':mobile', $newMobileNum, PDO::PARAM_INT);
			$query->bindParam(':profile_description', $description, PDO::PARAM_STR);
			$query->bindParam(':id', $id, PDO::PARAM_INT);

			$query->execute();

			return true;
		} catch (Exception $e)
		{
			throw new Exception("Something terrible happened. Could not update profile.");
		}

	}

	public static function updateName($id, $column, $newFirstName)
	{
		$newFirstName = trim($newFirstName);

		if ( ! preg_match("/^[a-zA-Z]{1,35}$/", $newFirstName))
		{
			throw new Exception('Names may contain only letters of size 1-35 letters.');
		}

		$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `" . $column . "`= :newFirstName WHERE `id`= :id";
		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':newFirstName', $newFirstName, PDO::PARAM_STR);
			$query->bindParam(':id', $id, PDO::PARAM_INT);

			$query->execute();

			return true;
		} catch (Exception $e)
		{
			throw new Exception("Something terrible happened. Could not update profile.");
		}

	}

	public static function updateProfileDescription($id, $newProfileDescription)
	{

		if ( ! preg_match("/^[\\w\t\n\r .,\\-]{0,512}$/", $newProfileDescription))
		{
			throw new Exception("Description can contain only <a href='http://www.regular-expressions.info/shorthand.html'
			target='_blank'>word characters</a>, spaces, carriage returns, line feeds and special characters <strong>.,-2</strong> of max size 512 characters.");
		}

		$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `" . self::DB_COLUMN_PROFILE_DESCRIPTION . "`= :newProfileDescription WHERE `id`= :id";
		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':newProfileDescription', $newProfileDescription, PDO::PARAM_STR);
			$query->bindParam(':id', $id, PDO::PARAM_INT);

			$query->execute();

			return true;
		} catch (Exception $e)
		{
			throw new Exception("Something terrible happened. Could not update profile description.");
		}
	}

	public static function updateMobileNumber($id, $newMobileNum)
	{
		self::validateMobileNumber($newMobileNum);

		try
		{

			$query = "UPDATE `" . App::getDbName() . "`.`" . self::DB_TABLE . "`
					SET `mobile`= :mobile WHERE `id`= :id";

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':mobile', $newMobileNum, PDO::PARAM_INT);
			$query->bindParam(':id', $id, PDO::PARAM_INT);

			$query->execute();

			return true;
		} catch (Exception $e)
		{
			throw new Exception("Something terrible happened. Could not update profile.");
		}

	}

	/**
	 * @param $newMobileNum
	 * @return null
	 * @throws Exception
	 * @internal param $db
	 */
	public static function validateMobileNumber($newMobileNum)
	{
		if (empty($newMobileNum) === true)
		{
			return null; // no mobilenumber
		}
		if ( ! preg_match('/^[0-9]{10}$/', $newMobileNum))
		{
			throw new Exception('Mobile number should contain only digits of total length 10');
		}

		if (UserFetcher::existsMobileNum($newMobileNum))
		{
			throw new Exception("Mobile entered number already exists in database."); // the array of errors messages
		}

		return $newMobileNum;
	}

	public static function updatePassword($id, $oldPassword, $newPassword1, $newPassword2)
	{

		if ($newPassword1 !== $newPassword2)
		{
			throw new Exception("There was a mismatch with the new passwords");
		}

		self::validatePassword($newPassword1);

		$old_password_hashed = self::getHashedPassword($id);
		if ( ! password_verify($oldPassword, $old_password_hashed))
		{
			throw new Exception("Sorry, the old password is incorrect.");
		}

		try
		{
			$new_password_hashed = password_hash($newPassword1, PASSWORD_DEFAULT);

			$query = "UPDATE `" . App::getDbName() . "`.`user`
			SET `password`= :password
			WHERE `id`= :id";

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':id', $id);
			$query->bindParam(':password', $new_password_hashed);

			$query->execute();
		} catch (Exception $e)
		{
			throw new Exception("Could not connect to database.");
		}
	}

	public static function validatePassword($password)
	{
		$r1 = '/[A-Z]/'; //Uppercase
		$r2 = '/[a-z]/'; //lowercase
		$r3 = '/[0-9]/'; //numbers

		$correctPassword = true;

		$correctPassword = $correctPassword && preg_match($r1, $password);
		$correctPassword = $correctPassword && preg_match($r2, $password);
		$correctPassword = $correctPassword && preg_match($r3, $password);
		$correctPassword = $correctPassword && (strlen($password) > 5);

		if ( ! $correctPassword)
		{
			throw new Exception("Password should:
			<ul>
				<li>Contain at least one capitalized letter. [A-Z]</li>
				<li>Contain at least one lowercase letter. [a-z]</li>
				<li>Contain at least one number. [0-9]</li>
				<li>Be at least 6 characters long</li>
			</ul> ");
		}
	}

	public static function getHashedPassword($id)
	{
		$query = "SELECT password FROM `" . App::getDbName() . "`.user WHERE id = :id";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':id', $id);
			$query->execute();
			$data = $query->fetch();
			$hash_password = $data['password'];

			return $hash_password;
		} catch (Exception $e)
		{
			throw new Exception("Could not connect to database.");
		}
	}

	public static function retrieveAll()
	{
		$query = "SELECT user.id, user.f_name, user.l_name, user.img_loc, user.profile_description, user.date, user.mobile, user.email, user_types.type
		         FROM `" . App::getDbName() . "`.user
						LEFT OUTER JOIN user_types ON user.`user_types_id` = `user_types`.id
						WHERE active=1";


		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->execute();
			$rows = $query->fetchAll();

			return $rows;
		} catch (PDOException $e)
		{
			throw new Exception("Something terrible happened. Could not retrieve users data from database.: ");
			// end catch
		}
	}

	public static function retrieveAllInactive()
	{
		$query = "SELECT user.id, user.f_name, user.l_name, user.img_loc, user.profile_description, user.date, user.mobile, user.email, user_types.type
		         FROM `" . App::getDbName() . "`.user
						LEFT OUTER JOIN user_types ON user.`user_types_id` = `user_types`.id
						WHERE active=0";

		try
		{
			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->execute();
			$rows = $query->fetchAll();

			return $rows;
		} catch (PDOException $e)
		{
			throw new Exception("Something terrible happened. Could not retrieve users data from database.: ");
			// end catch
		}
	}

	/**
	 * Verifies given credentials are correct. If login successfuly, returns true
	 * else return the error message.
	 *
	 * Dependancies:
	 * require_once ROOT_PATH . "inc/models/bcrypt.php";
	 * $bcrypt = new Bcrypt(12);
	 *
	 * @param $email $email of user
	 * @param $password $password of user
	 *
	 * @throws Exception
	 * @return bool|string
	 */
	public static function login($email, $password)
	{

		if (empty($email) === true || empty($password) === true)
		{
			throw new Exception('Sorry, but we need both your email and password.');
		} else
		{
			if (self::emailExists($email, self::DB_TABLE) === false)
			{
				throw new Exception('Sorry that email doesn\'t exists.');
			}
		}
		$query = "SELECT id, active, password, email FROM `" . App::getDbName() . "`.user WHERE email = :email";
		$dbConnection = DatabaseManager::getConnection();
		$dbConnection = $dbConnection->prepare($query);
		$dbConnection->bindParam(':email', $email);

		try
		{

			$dbConnection->execute();
			$data = $dbConnection->fetch();
			$hash_password = $data['password'];

			// using the verify method to compare the password with the stored hashed password.
			if ( ! password_verify($password, $hash_password))
			{
				throw new Exception('That email/password is invalid.');
			}

			if ($data['active'] == 0)
			{
				throw new Exception('Your account has been deactivated.');
			}

			return $data['id'];
		} catch (PDOException $e)
		{
			// "Sorry could not connect to the database."
			throw new Exception("Could not connect to the database");
		}
	}

	public static function validateUserType($user_type)
	{
		switch ($user_type)
		{
			case self::TUTOR:
			case self::SECRETARY:
			case self::ADMIN:
				return true;
			default:
				throw new Exception('Incorrect user type.');
		}
	}

	/**
	 *  Returns a single column from the next row of a result set or FALSE if there are no more rows.
	 * @param $what
	 * @param $field
	 * @param $where
	 * @param $value
	 * @return string
	 * @throws Exception
	 * @internal param $db
	 */
	public static function fetchInfo($what, $field, $where, $value)
	{
		// I have only added few, but you can add more. However do not add 'password' even though the parameters will only be given by you and not the user, in our system.
		$allowed = ['id', 'username', 'f_name', 'l_name', 'email', 'COUNT(mobile)',
			'mobile', 'user', 'gen_string', 'COUNT(gen_string)', 'COUNT(id)', 'img_loc', 'user_types', 'type'];
		if ( ! in_array($what, $allowed, true) || ! in_array($field, $allowed, true))
		{
			throw new InvalidArgumentException;
		} else
		{
			try
			{
				$query = "SELECT `" . $what . "` FROM `" . App::getDbName() . "`.`" . $field . "` WHERE $where = :value";
				$dbConnection = DatabaseManager::getConnection();
				$query = $dbConnection->prepare($query);

				$query->bindParam(':value', $value, PDO::PARAM_STR);

				$query->execute();

				return $query->fetchColumn();
				//return $sql;
			} catch (Exception $e)
			{
				throw new Exception($e->getMessage());
			}

		}
	}

	public static function addNewPassword($id, $newPassword1, $newPassword2, $generatedString)
	{
		if (strcmp($newPassword1, $newPassword2) !== 0)
		{
			throw new Exception("There was a mismatch with the new passwords");
		}
		User::validatePassword($newPassword1);
		if ( ! UserFetcher::generatedStringExists($id, $generatedString))
		{
			throw new Exception("Could not verify generated string exists. Please make sure url sent was not modified.");
		}
		UserFetcher::updatePassword($id, $newPassword1);
	}

	public static function recoverPassword($id, $newPassword1, $newPassword2, $generatedString)
	{
		if (strcmp($newPassword1, $newPassword2) !== 0)
		{
			throw new Exception("There was a mismatch with the new passwords");
		}
		User::validatePassword($newPassword1);

		if ( ! UserFetcher::generatedStringExists($id, $generatedString))
		{
			throw new Exception("Could not verify generated string exists. Please make sure url sent was not modified.");
		}

		if (User::isGeneratedStringExpired($id, $generatedString))
		{
			throw new Exception("Sorry that link has expired. Please <a href='" . App::getDomainName()
				. "/login/confirm-password'
							target='_self'>request</a> a new one");
		}
		UserFetcher::updatePassword($id, $newPassword1);
	}

	public static function isGeneratedStringExpired($id, $generatedString)
	{
		date_default_timezone_set('Europe/Athens');
		$generatedStringDate = UserFetcher::retrieveGenStringDate($id);

		$dateNow = new DateTime();
		$dateGenStringMdfd = new DateTime($generatedStringDate);
		$dateDurationInterval = date_diff($dateNow, $dateGenStringMdfd);

		return $dateDurationInterval->i > self::PASSWORD_EXPIRATION_TIME_MINUTES;
	}


	public static function generateNewPasswordString($id)
	{
		$unique = uniqid('', true); // generate a unique string
		$random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10); // generate a more random string
		$generatedString = $unique . $random; // a random and unique string

		User::validateId($id);
		UserFetcher::updateGenString($id, $generatedString);
		UserFetcher::updateGenStringTimeUpdate($id);

		return $generatedString;
	}

	public static function isUserTypeTutor($userType)
	{
		if (strcmp($userType, TutorFetcher::DB_TABLE) === 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * @return mixed
	 */
	public function getAccountActiveStatus()
	{
		return $this->accountActiveStatus;
	} // end getAllData

	/**
	 * @param mixed $accountActiveStatus
	 */
	public function setAccountActiveStatus($accountActiveStatus)
	{
		$this->accountActiveStatus = $accountActiveStatus;
	} // end getAllData

	/**
	 * @return mixed
	 */
	public function isActive()
	{
		return $this->active;
	}

	public function updateAvatarImg($avatar_img_loc)
	{
		$id = $this->getId();

		try
		{

			$query = "UPDATE `" . App::getDbName() . "`.user SET `img_loc`= :avatar_img WHERE `id`= :user_id";

			$dbConnection = DatabaseManager::getConnection();
			$query = $dbConnection->prepare($query);
			$query->bindParam(':avatar_img', $avatar_img_loc, PDO::PARAM_STR);
			$query->bindParam(':user_id', $id, PDO::PARAM_INT);

			$query->execute();

			return true;
		} catch (PDOException $e)
		{
			throw new Exception("Something terrible happened. Could not update database.");
		} // end try catch
	}

	/**
	 * @return mixed
	 */
	public function getAvatarImgLoc()
	{
		return $this->avatarImgLoc;
	}

	/**
	 * @return mixed
	 */
	public function getDateAccountCreated()
	{
		return $this->dateAccountCreated;
	}

	/**
	 * @return mixed
	 */
	public function getProfileDescription()
	{
		return $this->profileDescription;
	}

	public function isAdmin()
	{
		return false;
	}

	public function isSecretary()
	{
		return false;
	}

	/**
	 * @return mixed
	 */
	public function getUserType()
	{
		return $this->userType;
	}

	/**
	 * @param mixed $users
	 */
	public function setUsers($users)
	{
		$this->users = $users;
	}

	/**
	 * Redirect if user elevation is not that of secretary or admin
	 */
	public function denyTutor()
	{
		if ($this->isTutor())
		{
			header('Location: ' . BASE_URL . "error-403");
			exit();
		}
	}

	public function isTutor()
	{
		return false;
	}

	public function allowDoctorKatsas()
	{
		if ( ! (($this->email === 'grkatsas@acg.edu')))
		{
			header('Location: ' . BASE_URL . "error-403");
			exit();
		}
	}

	/**
	 * Redirect if user elevation is not that of tutor
	 */
	public function allowTutor()
	{
		if ( ! $this->isTutor())
		{
			header('Location: ' . BASE_URL . "error-403");
			exit();
		}
	}
}