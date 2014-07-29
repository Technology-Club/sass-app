<?php

/**
 * Created by PhpStorm.
 * User: Rizart Dokollari
 * Date: 5/29/14
 * Time: 1:31 PM
 */
class Users {

	private $dbConnection;

	/**
	 * Constructor
	 * @param $database
	 */
	public function __construct($dbConnection) {
		$this->setDbConnection($dbConnection);
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
	 * @return bool|string
	 */
	public function login($email, $password) {

		if (empty($email) === true || empty($password) === true) {
			throw new Exception('Sorry, but we need both your email and password.');
		} else if ($this->email_exists($email) === false) {
			throw new Exception('Sorry that email doesn\'t exists.');
		}
		$query = "SELECT password, email FROM `" . DB_NAME . "`.user WHERE email = :email";
		$query = $this->dbConnection->prepare($query);
		$query->bindParam(':email', $email);

		try {

			$query->execute();
			$data = $query->fetch();
			$hash_password = $data['password'];

			// using the verify method to compare the password with the stored hashed password.
			if (!password_verify($password, $hash_password)) {
				throw new Exception('Sorry, that email/password is invalid.');
			}

		} catch (PDOException $e) {
			// "Sorry could not connect to the database."
			throw new Exception("Sorry could not connect to the database: ");
		}
	} // end __construct

	/**
	 * Verifies a user with given email exists.
	 * returns true if found; else false
	 *
	 * @param $email $email given email
	 * @return bool true if found; else false
	 */
	public function email_exists($email) {
		$email = trim($email);
		$query = "SELECT COUNT(id) FROM `" . DB_NAME . "`.user WHERE email = :email";

		$query = $this->getDbConnection()->prepare($query);
		$query->bindParam(':email', $email, PDO::PARAM_STR);

		try {
			$query->execute();
			$rows = $query->fetchColumn();

			if ($rows == 1) {
				return true;
			} else {
				return false;
			} // end else if

		} catch (PDOException $e) {
			throw new Exception("Something terrible happened. Could not access database.");
		} // end catch
	}

	/**
	 * @return mixed
	 */
	public function getDbConnection() {
		return $this->dbConnection;
	}

	/**
	 * @param mixed $dbConnection
	 */
	public function setDbConnection($dbConnection) {
		$this->dbConnection = $dbConnection;
	}

	public function getAll() {
		// TODO: FIX QUERY TO NOT USER *, but rather specific columns instead. Safer & better performance
		$query = "SELECT * FROM `" . DB_NAME . "`.user
						LEFT OUTER JOIN user_types ON user.`user_types_id` = `user_types`.id";
		$query = $this->getDbConnection()->prepare($query);
		try {
			$query->execute();
			$rows = $query->fetchAll();

			return $rows;
		} catch (PDOException $e) {
			throw new Exception("Something terrible happened. Could not retrieve users data from database.: " . $e->getMessage());
		} // end catch
	}

	/**
	 * Returns all information of a user given his email.
	 * @param $email $email of user
	 * @return mixed If
	 */
	function getData($email) {
		$query = "SELECT user.email, user.id, user.`f_name`, user.`l_name`, user.`img_loc`,
						user.date, user.`profile_description`, user.mobile, user_types.type
					FROM `" . DB_NAME . "`.user
						LEFT OUTER JOIN user_types ON user.`user_types_id` = `user_types`.id
					WHERE email = :email";

		$query = $this->getDbConnection()->prepare($query);
		$query->bindValue(':email', $email, PDO::PARAM_INT);

		try {
			$query->execute();
			return $query->fetch();
		} catch (PDOException $e) {
			throw new Exception("Something terrible happened. Could not retrieve database." . $e->getMessage());
		} // end try
	}

	function update_profile_data($first_name, $last_name, $previous_mobile_num, $new_mobile_num, $description, $email) {
		$first_name = trim($first_name);
		$last_name = trim($last_name);
		$new_mobile_num = trim($new_mobile_num);
		$description = trim($description);
		$email = trim($email);

		$is_profile_data_correct = $this->is_profile_data_correct($first_name, $last_name,
			$new_mobile_num);

		if ($is_profile_data_correct !== true) {
			throw new Exception(implode("<br>", $is_profile_data_correct)); // the array of errors messages
		}


		if ($previous_mobile_num !== $new_mobile_num && $this->fetch_info('COUNT(mobile)', 'user', 'mobile', $new_mobile_num) != 0) {
			throw new Exception("Mobile entered number already exists in database."); // the array of errors messages
		}

		$query = "UPDATE `" . DB_NAME . "`.user
					SET `f_name`= :first_name, `l_name`= :last_name, `mobile`= :mobile,
						`profile_description`= :profile_description
						WHERE `email`= :email";
		try {
			$query = $this->dbConnection->prepare($query);

			$query->bindParam(':first_name', $first_name, PDO::PARAM_STR);
			$query->bindParam(':last_name', $last_name, PDO::PARAM_STR);
			$query->bindParam(':mobile', $new_mobile_num, PDO::PARAM_INT);
			$query->bindParam(':profile_description', $description, PDO::PARAM_STR);
			$query->bindParam(':email', $email, PDO::PARAM_STR);

			$query->execute();

			return true;
		} catch (PDOException $pe) {
			throw new Exception("Something terrible happened. Could not update profile." . $pe->getMessage());
			//throw new Exception($pe->getMessage());

		}
	}

	function is_profile_data_correct($first_name, $last_name, $mobile_num) {
		$errors = array();

		if (!ctype_alpha($first_name)) {
			$errors[] = 'First name may contain only letters.';
		}

		if (!ctype_alpha($last_name)) {
			$errors[] = 'Last name may contain only letters.';
		}

		if (!preg_match('/^[0-9]{10}$/', $mobile_num)) {
			$errors[] = 'Mobile number should contain only digits of total length 10';
		}

		if (empty($errors)) {
			return true;
		} else {
			return $errors;
		}
	}


	public function update_avatar_img($avatar_img_loc, $user_id) {
		try {

			$query = "UPDATE `" . DB_NAME . "`.user SET `img_loc`= :avatar_img WHERE `id`= :user_id";
			$query = $this->dbConnection->prepare($query);
			$query->bindParam(':avatar_img', $avatar_img_loc, PDO::PARAM_STR);
			$query->bindParam(':user_id', $user_id, PDO::PARAM_INT);

			$query->execute();
			return true;
		} catch (PDOException $e) {
			throw new Exception("Something terrible happened. Could not update database.");
		} // end try catch
	}

	public function update_password($user_id, $old_password, $new_password_1, $new_password_2) {

		$old_password_hashed = $this->getHashedPassword($user_id);
		if (!password_verify($old_password, $old_password_hashed)) {
			throw new Exception("Sorry, the old password is incorrect.");
		}

		if ($new_password_1 !== $new_password_2) {
			throw new Exception("There was a mismatch with the new passwords");
		}

		try {
			$new_password_hashed = password_hash($new_password_1, PASSWORD_DEFAULT);

			$query = "UPDATE `" . DB_NAME . "`.`user` SET `password`= :password WHERE `id`= :id";
			$query = $this->getDbConnection()->prepare($query);
			$query->bindParam(':id', $user_id);
			$query->bindParam(':password', $new_password_hashed);

			$query->execute();
		} catch (Exception $e) {
			throw new Exception("Could not connect to database.");
		}
	}

	public function getHashedPassword($id) {
		$query = "SELECT password FROM `" . DB_NAME . "`.user WHERE id = :id";
		$query = $this->getDbConnection()->prepare($query);
		$query->bindParam(':id', $id);

		try {

			$query->execute();
			$data = $query->fetch();
			$hash_password = $data['password'];
			return $hash_password;
		} catch (Exception $e) {
			throw new Exception("Could not connect to database.");
		}
	} // end getAllData

	public function confirm_recover($email) {

		$unique = uniqid('', true); // generate a unique string
		$random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10); // generate a more random string
		$generated_string = $unique . $random; // a random and unique string
		$query = $this->getDbConnection()->prepare("UPDATE `" . DB_NAME . "`.`user` SET `gen_string` = ? WHERE `email` = ?");
		$query->bindValue(1, $generated_string);
		$query->bindValue(2, $email);

		try {
			$query->execute();
			mail($email, 'Recover Password', "Hello.\r\nSome one, hopefully you requested a password reset.\r\nPlease click the link below:\r\n\r\nhttp://" . $_SERVER['SERVER_NAME'] . "/login/recover.php?email=" . $email . "&gen_string=" . $generated_string . "\r\n\r\n We will generate a new password for you and send it back to your email.\r\n\r\n-- sass team");
		} catch (PDOException $e) {
			throw new Exception("We could not send your email. Please retry.");
		}
	} // end confirm_recover

	public function recover($email, $generated_string) {
		//  update_password($user_id, $old_password, $new_password_1, $new_password_2) {
		if ($generated_string == 0) {
			return false;
		} else {
			$query = $this->getDbConnection()->prepare("SELECT COUNT(`id`) FROM `" . DB_NAME . "`.`user` WHERE `email` = ? AND `gen_string` = ?");
			$query->bindValue(1, $email);
			$query->bindValue(2, $generated_string);

			try {
				$query->execute();
				$rows = $query->fetchColumn();

				if ($rows != 1) { // if we find email/generated_string combo
					throw new Exception("Your email was not found. You can try to reset your password again.");
				}
				$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
				$generated_password = substr(str_shuffle($charset), 0, 10);
				$query = $this->getDbConnection()->prepare("UPDATE `" . DB_NAME . "`.`user` SET `gen_string` = 0 WHERE `email` = ?"); // set generated_string back to 0
				$query->bindValue(1, $email);
				$query->execute();

				$new_password = password_hash($generated_password, PASSWORD_DEFAULT);
				$query = "UPDATE `" . DB_NAME . "`.`user` SET `password`= :password WHERE `email`= :email";
				$query = $this->getDbConnection()->prepare($query);
				$query->bindParam(':email', $email);
				$query->bindParam(':password', $new_password);

				$query->execute();

				#mail the user the new password.
				mail($email, 'Your password', "Hello.\n\nYour your new password is: " . $generated_password . "\n\nPlease change your password once you have logged in using this password.\n\n-sass team");
			} catch (PDOException $e) {
				throw new Exception("Could not connect to database. Please retry.:" . $e->getMessage());
			}
		}

		return true;
	} // end recover

	/**
	 * Returns a single column from the next row of a result set or FALSE if there are no more rows.
	 *
	 * @param $what
	 * @param $field
	 * @param $value
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function fetch_info($what, $field, $where, $value) {
		// I have only added few, but you can add more. However do not add 'password' even though the parameters will only be given by you and not the user, in our system.
		$allowed = array('id', 'username', 'f_name', 'l_name', 'email', 'COUNT(mobile)', 'mobile', 'user');
		if (!in_array($what, $allowed, true) || !in_array($field, $allowed, true)) {
			throw new InvalidArgumentException;
		} else {
			$sql = "SELECT $what FROM `" . DB_NAME . "`.`" . $field . "` WHERE $where = ?";
			$query = $this->dbConnection->prepare($sql);
			$query->bindValue(1, $value, PDO::PARAM_INT);

			try {
				$query->execute();
				return $query->fetchColumn();
				//return $sql;
			} catch (PDOException $e) {
				throw new Exception($e->getMessage());
			}

		}
	} // end fetch_info

	public function register($first_name, $last_name, $email, $user_type, $user_major_ext, $teaching_courses) {
		$this->validate_name($first_name);
		$this->validate_name($last_name);
		$this->validate_email($email);
		$this->validate_user_type($user_type);
		//$this->validate_user_major($user_major_ext);
		//$this->validate_teaching_course($teaching_courses);

		try {
			$query = "INSERT INTO `" . DB_NAME . "`.user (`email`, `f_name`, `l_name`, `user_types_id`)
				VALUES(
					:email,
					:first_name,
					:last_name,
					(SELECT id as 'user_types_id' FROM user_types WHERE user_types.type=:user_type )
				)";

			$query = $this->getDbConnection()->prepare($query);
			$query->bindParam(':email', $email, PDO::PARAM_STR);
			$query->bindParam(':first_name', $first_name, PDO::PARAM_STR);
			$query->bindParam(':last_name', $last_name, PDO::PARAM_STR);
			$query->bindParam(':user_type', $user_type, PDO::PARAM_INT);
			$query->execute();
			return true;
		} catch (Exception $e) {
			throw new Exception("Could not insert user into database.");
		}

	}

	/**
	 * @param $name
	 * @throws Exception
	 */
	public function validate_name($name) {
		if (!preg_match('/^[A-Za-z]+$/', $name)) {
			throw new Exception("Please enter a first/last name containing only letters of minimum length 1.");
		}
	}

	public function validate_email($email) {
		// TODO: validate using phpmailer.
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw new Exception("Please enter a valid email address");
		} else if ($this->email_exists($email)) {
			throw new Exception('That email already exists. Please use another one.');
		} // end else if
	}

	public function validate_user_type($user_type) {
		switch ($user_type) {
			case "tutor":
			case "secretary":
			case "admin":
				return true;
			default:
				throw new Exception('Incorrect user type.');
		}
	}

	public function validate_user_major($user_major_ext) {
		if ($user_major_ext === "null") {
			return true;
		}
		$query = "SELECT COUNT(1)  FROM `" . DB_NAME . "`.major WHERE major.extension=':extension'";
		$query = $this->getDbConnection()->prepare($query);
		$query->bindParam(':extension', $user_major_ext);

		try {

			$query->execute();
			$data = $query->fetch();
		} catch (Exception $e) {
			throw new Exception("Could not connect to database.");
		}

		if ($data === 1) {
			return true;
		} else {
			// TODO: sent email to developer relavant to this error.
			throw new Exception("Either something went wrong with a database query, or you're trying to hack this app. In either case, the developers were just notified about this.");
		}
	}

	public function validate_teaching_course($teaching_courses) {

	}
} 