<?php
require __DIR__ . '/../app/init.php';
$general->loggedOutProtect();

// redirect if user elevation is not that of secretary or tutor
if (!$user->isAdmin())
{
	header('Location: ' . BASE_URL . "error-403");
	exit();
}

try
{
	$courses = CourseFetcher::retrieveAll();
	$majors = MajorFetcher::retrieveMajors();
	$terms = TermFetcher::retrieveCurrTerm();

	//$majors = array_unique(array_column($courses, 'Major'));
	//$majors_extensions = array_unique(array_column($courses, 'Extension'));
} catch (Exception $e)
{
	$errors[] = $e->getMessage();
}

function is_create_bttn_Pressed()
{
	return isset($_POST['hidden_submit_pressed']) && empty($_POST['hidden_submit_pressed']);
}

if (isSaveBttnPressed())
{
	$first_name = trim($_POST['first_name']);
	$last_name = trim($_POST['last_name']);
	$email = trim($_POST['email']);
	$user_type = trim($_POST['user_type']);
	$userMajorId = (isset($_POST['userMajor']) ? trim($_POST['userMajor']) : "");
	$teachingCoursesIds = isset($_POST['teachingCoursesMulti']) ? $_POST['teachingCoursesMulti'] : null;
	$termIds = isset($_POST['termIds']) ? $_POST['termIds'] : null;

	try
	{
		$newUserId = Admin::createUser($first_name, $last_name, $email, $user_type, $userMajorId, $teachingCoursesIds, $termIds);
		$newUser = User::getSingle($newUserId);
		Mailer::sendNewAccount($newUserId, $newUser[UserFetcher::DB_COLUMN_EMAIL], $newUser[UserFetcher::DB_COLUMN_FIRST_NAME] . " " .
			$newUser[UserFetcher::DB_COLUMN_LAST_NAME]);

	} catch (Exception $e)
	{
		$errors[] = $e->getMessage();
	}
}

function isSaveBttnPressed()
{
	return isset($_POST['hidden_submit_pressed']) && empty($_POST['hidden_submit_pressed']);
}

$pageTitle = "Add staff Member";
$section = "staff";

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
<?php require ROOT_PATH . 'views/head.php'; ?>
<body>
<div id="wrapper">
	<?php
	require ROOT_PATH . 'views/header.php';
	require ROOT_PATH . 'views/sidebar.php';
	?>


	<div id="content">

		<div id="content-header">
			<h1>Add SASS staff Member</h1>

		</div>
		<!-- #content-header -->


		<div id="content-container">

			<h3 class="heading"></h3>


			<div class="row">

				<div class="col-md-3 col-sm-5">

					<ul id="myTab" class="nav nav-pills nav-stacked">
						<li class="active"><a href="#add" data-toggle="tab"><i class="fa fa-plus"></i> &nbsp;&nbsp;Add
								staff Member</a></li>
					</ul>

				</div>
				<!-- /.col -->

				<div class="col-md-9 col-sm-7">

					<div id="myTabContent" class="tab-content stacked-content">
						<div class="tab-pane fade in active" id="add">
							<p>In this section admin is able to add a new user and fill out the appropriate fields. Only
								necessary fields are required in order to create a new user. Users(tutors or
								secretaries)
								are
								able to modify some of their profile data nce they are logged in.</p>

							<p>
								<a data-toggle="modal" id="btnAddUserModal" href="#styledModal" class="btn btn-primary">
									Add new staff member</a>
							</p>
						</div>


					</div>

				</div>
				<!-- /.col -->

			</div>
			<!-- /.row -->


		</div>
		<!-- /#content-container -->

	</div>
	<!-- #content -->

	<div id="styledModal" class="modal modal-styled fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<form method="post" id="create-form" action="" class="form">

					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3 class="modal-title">Create staff Member Form</h3>
					</div>
					<div class="modal-body">
						<div class="portlet">
							<?php
							if (empty($errors) === false)
							{
								?>
								<div class="alert alert-danger">
									<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
									<strong>Oh snap!</strong><?php echo '<p>' . implode('</p><p>', $errors) . '</p>';
									?>
								</div>
							<?php
							} else
							{
								if (is_create_bttn_Pressed())
								{
									?>
									<div class="alert alert-success">
										<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
										<strong>staff member successfully created!</strong> <br/>
										An email is being sent to the email you just specified, with next steps to
										follow.
									</div>
								<?php }
							} ?>

							<div class="portlet-content">

								<div class="row">


									<div class="col-sm-12">

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="first_name">First Name</label>
											</h5>
											<input type="text" id="first_name" name="first_name" class="form-control"
											       value="<?php if (isset($_POST['first_name']))
											       {
												       echo htmlentities($_POST['first_name']);
											       } ?>"
											       autofocus="on" required>
										</div>

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="last_name">Last Name</label>
											</h5>
											<input type="text" id="last_name" name="last_name" class="form-control"
											       value="<?php if (isset($_POST['last_name']))
											       {
												       echo htmlentities($_POST['last_name']);
											       } ?>"
											       required>
										</div>

										<div class="form-group">

											<i class="fa fa-envelope"></i>
											<label for="email">Email</label>
											<input type="email" required id="email" name="email" class="form-control"
											       value="<?php if (isset($_POST['email']))
											       {
												       echo htmlentities($_POST['email']);
											       } ?>">
										</div>

										<div class="form-group">
											<h5>
												<i class="fa fa-check"></i>
												Type
											</h5>


											<div class="radio" id="id_tutor_div">
												<label>
													<input type="radio" name="user_type" id="id_input_user_type"
													       value="tutor"
													       class="icheck-input"
													       checked data-required="true">
													Tutor
												</label>

											</div>


											<div class="radio">
												<label>
													<input type="radio" name="user_type" value="secretary"
													       class="icheck-input"
													       data-required="true">
													Secretary
												</label>
											</div>

											<div class="radio">
												<label>
													<input type="radio" name="user_type" value="admin"
													       class="icheck-input"
													       data-required="true">
													Admin
												</label>
											</div>
										</div>
										<!-- /.form-group -->

										<div class="form-group">

											<h5>
												<i class="fa fa-tasks"></i>
												<label for="userMajor">Tutor's Major</label>
											</h5>
											<select id="userMajor" name="userMajor" class="form-control">
												<?php foreach ($majors as $major)
												{
													include(ROOT_PATH . "views/partials/major/select-options-view.html.php");
												}
												?>
											</select>
										</div>


										<div class="form-group">

											<h5>
												<i class="fa fa-tasks"></i>
												<label for="termIds">Current Terms</label>
											</h5>
											<select id="termIds" name="termIds" class="form-control" required>
												<?php foreach ($terms as $term)
												{
													include(ROOT_PATH . "views/partials/term/select-options-view.html.php");
												}
												?>
											</select>
										</div>


										<div class="form-group">

											<h5>
												<i class="fa fa-tasks"></i>
												<label for="teachingCoursesMulti">Tutor's Courses</label>
											</h5>

											<select id="teachingCoursesMulti" name="teachingCoursesMulti[]"
											        class="form-control"
											        multiple>

												<?php foreach ($courses as $course)
												{
													include(ROOT_PATH . "views/partials/course/select-options-view.html.php");
												}
												?>

											</select>
										</div>


									</div>
								</div>

							</div>

						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-tertiary" data-dismiss="modal">Close</button>
						<input type="hidden" name="hidden_submit_pressed">
						<button type="submit" class="btn btn-primary">Create</button>
					</div>
				</form>

			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>
	<!-- /.modal -->
	<?php include ROOT_PATH . "views/footer.php"; ?>

</div>
<!-- /#wrapper -->

<?php include ROOT_PATH . "views/assets/footer_common.php"; ?>

<script src="<?php echo BASE_URL; ?>assets/js/plugins/select2/select2.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/icheck/jquery.icheck.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/plugins/datepicker/bootstrap-datepicker.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/timepicker/bootstrap-timepicker.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/simplecolorpicker/jquery.simplecolorpicker.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/textarea-counter/jquery.textarea-counter.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/autosize/jquery.autosize.min.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/demos/form-extended.js"></script>


<script type="text/javascript">
	jQuery(function ()
	{
		$("#create-form").submit(function (event)
		{
			var error_last_name = validate($("#last_name"), /^[a-zA-Z]{1,16}$/);
			var error_first_name = validate($("#first_name"), /^[a-zA-Z]{1,16}$/);

//			if ($('input[name=user_type').val() === "tutor") {
//				alert("tutor");
//			}
			if (!error_last_name || !error_first_name)
			{
				//event.preventDefault();
			}
		});

		setTimeout(function ()
		{
			$("#btnAddUserModal").trigger("click");
			//window.location.href = $href;
		}, 10);

		$("#userMajor").select2();
		$("#teachingCoursesMulti").select2();
		$("#termIds").select2();

		// TODO: add error messages
		// TODO: add option for second major
		// TODO: check email ^ user major & course teaching are inputt if user is tutor type.
		// TODO: hide major & courses & user type NOT tutor
		var validate = function (element, regex)
		{
			var str = $(element).val();
			var $parent = $(element).parent();

			if (regex.test(str))
			{
				$parent.attr('class', 'form-group has-success');
				return true;
			}
			else
			{
				$parent.attr('class', 'form-group has-error');
				return false;
			}
		};

		$("#last_name").blur(function ()
		{
			validate(this, /^[a-zA-Z]{1,16}$/);
		});

		$("#first_name").blur(function ()
		{
			validate(this, /^[a-zA-Z]{1,16}$/);
		});

		$('input[name=user_type]').on('ifChecked', function (event)
		{
			if ($(this).val() === "tutor")
			{
				$("#userMajor").select2("enable", true);
				$("#teachingCoursesMulti").select2("enable", true);
				$("#termIds").select2("enable", true);
			}
			else
			{
				$("#userMajor").select2("enable", false);
				$("#teachingCoursesMulti").select2("enable", false);
				$("#termIds").select2("enable", false);
			}
		});

		$('input[name=iCheck]').each(function ()
		{
			var self = $(this),
				label = self.next(),
				label_text = label.text();

			label.remove();
			self.iCheck({
				checkboxClass: 'icheckbox_line-red',
				radioClass   : 'iradio_line-red',
				insert       : '<div class="icheck_line-icon"></div>' + label_text
			});
		});

	});


</script>

</body>
</html>
