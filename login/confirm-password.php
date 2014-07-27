<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) <year> <copyright holders>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @author Rizart Dokollari & George Skarlatos
 * @since 6/29/14.
 */

?>
<?php
ob_start();
// TODO: Add option-functionality to resend email if password forgot
// TODO: sql make 'img' of database to NOT NULL & refactor name to 'img_location'
require '../app/init.php';

// if there is an active log in process redirect to edit.php; load page only if no
// logged in user exists
$general->logged_in_protect();
$page_title = "Log In";


/**
 * @return bool
 */
function isContinueBtnPressed() {
	return isset($_POST['hidden_forgot_continue']) && empty($_POST['hidden_forgot_continue']);
}

/**
 * @return bool
 */
function isVerified() {
	return isset($_GET['success']) === true && empty ($_GET['success']);
}

if (isContinueBtnPressed()) {
	try {
		if ($users->email_exists($_POST['email'])) {
			$users->confirm_recover($_POST['email']);
			header('Location:confirm-password/success');
			exit();
		} else {
			$errors[] = 'Sorry, that email doesn\'t exist.';
		} // end else if

	} catch (Exception $e) {
		$errors[] = $e->getMessage();
	}
} // end outer if
?>


<!DOCTYPE html>
<!--[if lt IE 7]>
<html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>
<html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>
<html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js"> <!--<![endif]-->
<head>

	<title>Login - Canvas Admin</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="description" content="">
	<meta name="author" content=""/>
	<link rel="shortcut icon" href="<?php echo BASE_URL; ?>app/assets/img/logos/logo-login.png">

	<link rel="stylesheet"
	      href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,800italic,400,600,800"
	      type="text/css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>app/assets/css/font-awesome.min.css" type="text/css"/>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>app/assets/css/bootstrap.min.css" type="text/css"/>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>app/assets/js/libs/css/ui-lightness/jquery-ui-1.9.2.custom.css"
	      type="text/css"/>

	<link rel="stylesheet" href="<?php echo BASE_URL; ?>app/assets/css/App.css" type="text/css"/>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>app/assets/css/Login.css" type="text/css"/>

	<link rel="stylesheet" href="<?php echo BASE_URL; ?>app/assets/css/custom.css" type="text/css"/>

</head>

<body>
<div id="login-container">

	<div id="logo">
		<a href="<?php echo BASE_URL; ?>login">
			<img src="<?php echo BASE_URL; ?>app/assets/img/logos/logo-login.png" alt="Logo"/>
		</a>
	</div>

	<?php if (isVerified()) { ?>

		<!-- /#forgot -->
		<div id="login">
			<h4>Thank you.</h4>
			<hr/>

			<h5>Please check your email to confirm your request for a new password.</h5>

			<hr/>

			<div class="form-group text-center">
				<input type="hidden" name="hidden_forgot_pressed">
				<a href="<?php echo BASE_URL; ?>login" name="forgot" class="btn btn-default">
					Log In
				</a>
			</div>
			<?php
			if (empty($errors) === false) {
				?>
				<div class="alert alert-danger">
					<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
					<strong>Oh snap!</strong><?php echo '<p>' . implode('</p><p>', $errors) . '</p>'; ?>
				</div>
			<?php
			}
			?>
		</div>
		<!-- /#forgot -->

	<?php } else { ?>
		<!-- /#forgot -->
		<div id="login">
			<h4>Password Recovery</h4>
			<h5>Submit your email address and we'll send you a link to reset it.</h5>

			<form method="post" id="login-form" action="confirm-password.php" class="form">
				<div class="form-group">
					<label for="login-email">Email</label>
					<input required type="email" class="form-control" id="login-email" name="email" placeholder="Email">
				</div>

				<div class="form-group">
					<input type="hidden" name="hidden_forgot_continue">
					<button type="submit" id="login-btn" class="btn btn-primary btn-block">Continue <i
							class="glyphicon glyphicon-envelope"></i></button>
				</div>
			</form>

			<?php
			if (empty($errors) === false) {
				?>
				<div class="alert alert-danger">
					<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
					<strong>Oh snap!</strong><?php echo '<p>' . implode('</p><p>', $errors) . '</p>'; ?>
				</div>
			<?php
			}
			?>
		</div>
		<!-- /#forgot -->

	<?php } ?>
</div>
<!-- /#forgot-container -->

<script src="<?php echo BASE_URL; ?>app/assets/js/libs/jquery-1.9.1.min.js"></script>
<script src="<?php echo BASE_URL; ?>app/assets/js/libs/jquery-ui-1.9.2.custom.min.js"></script>
<script src="<?php echo BASE_URL; ?>app/assets/js/libs/bootstrap.min.js"></script>

<script src="<?php echo BASE_URL; ?>app/assets/js/App.js"></script>

<script src=".<?php echo BASE_URL; ?>app/assets/s/Login.js"></script>
</body>
</html>