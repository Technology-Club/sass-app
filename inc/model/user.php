<?php

/**
 * Class User will contain prototype for users; tutors, secretary and admin.
 * Log In, Log Out.
 */
class User {

	// connection to db.
	private $dbConnection;

	/**
	 * Constructor
	 * @param $database
	 */
	public function __construct($dbConnection) {
		$this->dbConnection = $dbConnection;
	} // end __construct


	/**
	 * Verifies given credentials are correct. If login successfuly, returns true
	 * else return the error message.
	 *
	 * Dependancies:
	 * require_once ROOT_PATH . "inc/model/bcrypt.php";
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
				throw new Exception('Sorry, that email/password is invalid');
			}

		} catch (PDOException $e) {
			// "Sorry could not connect to the database."
			throw new Exception("Sorry could not connect to the database.");
		}
	}// end function login

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

		$query = $this->dbConnection->prepare($query);
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
			die($e->getMessage());
		} // end catch
	} // end function user_exists


	/**
	 * Returns all information of a user given his email.
	 * @param $email $email of user
	 * @return mixed If
	 */
	function get_data($email) {
		$query = "SELECT user.id, user.`f_name`, user.`l_name`, user.`img_loc`,
						user.date, user.`profile_description`, user.mobile, user_types.type, major.name
					FROM `" . DB_NAME . "`.user
						LEFT OUTER JOIN user_types ON user.`user_types_id` = `user_types`.id
						LEFT OUTER JOIN major ON user.major_id = major.id
					WHERE email = :email";

		$query = $this->dbConnection->prepare($query);
		$query->bindValue(':email', $email, PDO::PARAM_INT);

		try {
			$query->execute();
			return $query->fetch();
		} catch (PDOException $e) {
			die($e->getMessage());
		} // end try
	} // end function get_data

	function update_profile_data($first_name, $last_name, $mobile_num, $description, $email) {
		$first_name = trim($first_name);
		$last_name = trim($last_name);
		$mobile_num = trim($mobile_num);
		$description = trim($description);
		$email = trim($email);

		$is_profile_data_correct = $this->is_profile_data_correct($first_name, $last_name,
			$mobile_num);

		if ($is_profile_data_correct !== true) {
			throw new Exception(implode("<br>", $is_profile_data_correct)); // the array of errors messages
		}

		$query = "UPDATE `" . DB_NAME . "`.user
					SET `f_name`= :first_name, `l_name`= :last_name, `mobile`= :mobile,
						`profile_description`= :profile_description
						WHERE `email`= :email";

		try {
			$query = $this->dbConnection->prepare($query);

			$query->bindParam(':first_name', $first_name, PDO::PARAM_STR);
			$query->bindParam(':last_name', $last_name, PDO::PARAM_STR);
			$query->bindParam(':mobile', $mobile_num, PDO::PARAM_INT);
			$query->bindParam(':profile_description', $description, PDO::PARAM_STR);
			$query->bindParam(':email', $email, PDO::PARAM_STR);

			$query->execute();

			return true;
		} catch (PDOException $pe) {
			//throw new Exception("Something terrible happened. Could not update database.");
			throw new Exception($pe->getMessage());
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
			$query = "UPDATE " . DB_NAME . ".general_user SET `img_loc`= :avatar_img WHERE `id`= :user_id";
			$query = $this->dbConnection->prepare($query);
			$query->bindParam(':avatar_img', $avatar_img_loc, PDO::PARAM_STR);
			$query->bindParam(':user_id', $user_id, PDO::PARAM_INT);

			$query->execute();
			return true;
		} catch (PDOException $e) {
			die ($e->getMessage());
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
			$this->is_password_valid($new_password_1);

		} catch (Exception $e) {
			throw new Exception("New password must contain at least one upper case, lower case, special symbol, letter and be of minimum length 8: " . $e->getMessage());

		}


		$new_password_hashed = password_hash($new_password_1, PASSWORD_DEFAULT);

		try {
			$query = "UPDATE `" . DB_NAME . "`.`user` SET `password`= :password WHERE `id`= :id";
			$query = $this->dbConnection->prepare($query);
			$query->bindParam(':id', $user_id, PDO::PARAM_INT);
			$query->bindParam(':password', $new_password_hashed, PDO::PARAM_STR);
			$query->execute();
		} catch (Exception $e) {
			throw new Exception("Could not update password into database.");
		}

	}

	public function getHashedPassword($user_id) {
		$query = "SELECT password FROM `" . DB_NAME . "`.user WHERE id = :id";
		$query = $this->dbConnection->prepare($query);
		$query->bindParam(':id', $user_id, PDO::PARAM_INT);

		try {

			$query->execute();
			$data = $query->fetch();
			$hash_password = $data['password'];
			return $hash_password;
		} catch (Exception $e) {
			throw new Exception("Could not connect to database." . $e->getMessage());
		}
	}

	/**
	 * Source: http://stackoverflow.com/a/2639151/2790481
	 *
	 * @param $password
	 */
	public function is_password_valid($password) {
		$r1 = '/[A-Z]/'; //Uppercase
		$r2 = '/[a-z]/'; //lowercase
		$r3 = '/[!@#$%^&*()\-_=+{};:,<.>]/'; // whatever you mean by 'special char'
		$r4 = '/[0-9]/'; //numbers

		if (preg_match_all($r1, $password) < 1) throw new Exception("Your password did contain at least one upper case letter.");
		if (preg_match_all($r2, $password) < 1) throw new Exception("Your password did contain at least one small case letter.");
		if (preg_match_all($r3, $password) < 1) throw new Exception("Your password did contain at least one symbol.");
		if (preg_match_all($r4, $password) < 1) throw new Exception("Your password did contain at least one number.");
		if (strlen($password) < 8) throw new Exception("Your password was less than 8 characters long.");

		return true;
	}
}

?>
