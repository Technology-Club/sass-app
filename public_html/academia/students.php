<?php
require __DIR__ . '/../app/init.php';
$general->loggedOutProtect();

// redirect if user elevation is not that of secretary or admin
if ($user->isTutor())
{
	header('Location: ' . BASE_URL . "error-403");
	exit();
}

$pageTitle = "Academia - Students";
$section = "academia";

try
{
	$students = StudentFetcher::retrieveAll();
	$majors = MajorFetcher::retrieveMajors();
	$appointments = AppointmentFetcher::retrievePendingForAllStudents();
	if (isBtnAddStudentPrsd())
	{
		$majorId = !empty($_POST['userMajorId']) ? $_POST['userMajorId'] : null;

		Student::create($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['studentId'],
			$_POST['mobileNum'], $majorId, $_POST['ciInput'], $_POST['creditsInput']);
		header('Location: ' . BASE_URL . "academia/students/success");
		exit();
	} else
	{
		if (isBtnAddMajorPrsd())
		{
			Major::create($_POST['majorCode'], $_POST['majorName']);
			header('Location: ' . BASE_URL . "academia/students/success");
		} else
		{
			if (isBtnUpdatePrsd())
			{

				if (!isset($_POST['idUpdate']) || ($oldStudentData = getStudent($_POST['idUpdate'], $students)) === false)
				{
					throw new Exception("Data tempering detected. Process stopped.");
				} else
				{
					$id = $oldStudentData[StudentFetcher::DB_COLUMN_ID];
					$newFirstName = $_POST['newFirstName'];
					$newLastName = $_POST['newLastName'];
					$newEmail = $_POST['newEmail'];
					$newStudentId = $_POST['newStudentId'];
					$newMobileNum = $_POST['newMobileNum'];
					$newMajorId = $_POST['newStudentMajorId'];
					$newCI = isset($_POST['newCI']) ? $_POST['newCI'] : null;
					$newCreditsNum = $_POST['newCreditsNum'];


					Student::updateFirstName($id, $newFirstName, $oldStudentData[StudentFetcher::DB_COLUMN_FIRST_NAME]);
					Student::updateLastName($id, $newLastName, $oldStudentData[StudentFetcher::DB_COLUMN_LAST_NAME]);
					Student::updateStudentId($id, $newStudentId, $oldStudentData[StudentFetcher::DB_COLUMN_STUDENT_ID]);
					Student::updateEmail($id, $newEmail, $oldStudentData[StudentFetcher::DB_COLUMN_EMAIL]);
					Student::updateMobileNum($id, $newMobileNum, $oldStudentData[StudentFetcher::DB_COLUMN_MOBILE]);
					Student::updateMajorId($id, $newMajorId, $oldStudentData[StudentFetcher::DB_COLUMN_MAJOR_ID]);
					Student::updateCi($id, $newCI, $oldStudentData[StudentFetcher::DB_COLUMN_CI]);
					Student::updateCredits($id, $newCreditsNum, $oldStudentData[StudentFetcher::DB_COLUMN_CREDITS]);

					header('Location: ' . BASE_URL . "academia/students/success");

				}

			}
		}
	}
} catch (Exception $e)
{
	$errors[] = $e->getMessage();
}

/**
 * http://stackoverflow.com/a/4128377/2790481
 *
 * @param $needle
 * @param $students
 * @return bool
 */
function getStudent($needle, $students)
{
	foreach ($students as $student)
	{
		if ($student[StudentFetcher::DB_COLUMN_ID] === $needle)
		{
			return $student;
		}
	}

	return false;
}


function isEditbtnPressed()
{
	return isset($_GET['id']) && preg_match('/^[0-9]+$/', $_GET['id']);
}

function isBtnAddStudentPrsd()
{
	return isset($_POST['hiddenSubmitPressed']) && empty($_POST['hiddenSubmitPressed']);
}

function isModificationSuccess()
{
	return isset($_GET['success']) && strcmp($_GET['success'], 'y1!q' === 0);
}

function isBtnAddMajorPrsd()
{
	return isset($_POST['hiddenCreateMajor']) && empty($_POST['hiddenCreateMajor']);
}

function isBtnUpdatePrsd()
{
	return isset($_POST['hiddenUpdatePrsd']) && empty($_POST['hiddenUpdatePrsd']);

}

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
			<h1>All Students</h1>
		</div>
		<!-- #content-header -->

		<div id="content-container">

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
				if (isModificationSuccess())
				{
					?>
					<div class="alert alert-success">
						<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
						<strong>Data successfully modified!</strong> <br/>
					</div>
				<?php }
			} ?>
			<div class="row">

				<div class="col-md-12">

					<div class="portlet">

						<div class="portlet-header">

							<h3>
								<i class="fa fa-users"></i>
								View and Manage Students
							</h3>

							<ul class="portlet-tools pull-right">
								<li>
									<div class="btn-group">
										<button data-toggle="dropdown" class="btn btn-md btn-primary dropdown-toggle">
											Add <span class="caret"></span></button>
										<ul class="dropdown-menu" role="menu">
											<li><a data-toggle="modal" href="#addStudentModal">Student</a></li>
											<li class="divider"></li>
											<li><a data-toggle="modal" href="#addMajorModal">Major</a></li>
										</ul>
									</div>
								</li>
							</ul>

						</div>
						<!-- /.portlet-header -->

						<div class="portlet-content">

							<div class="table-responsive">
								<table
									class="table table-striped table-bordered table-hover table-highlight"
									data-provide="datatable"
									data-display-rows="10"
									data-info="true"
									data-search="true"
									data-length-change="true"
									data-paginate="false"
									id="usersTable"
									>
									<thead>
									<tr>
										<th class="text-center" data-filterable="true" data-sortable="true"
										    data-direction="desc">Name
										</th>
										<th class="text-center" data-filterable="true" data-sortable="true"
										    data-direction="desc">ID
										</th>
										<th class="text-center" data-direction="asc" data-filterable="true"
										    data-sortable="false">Email
										</th>
										<?php if (!$user->isTutor()): ?>
											<th class="text-center" data-filterable="false" class="hidden-xs hidden-sm">
												Pending Appointments
											</th>
										<?php endif; ?>
										<th class="text-center" data-filterable="true" data-sortable="false">Mobile</th>
										<th class="text-center" data-filterable="true" data-sortable="true">Major</th>
										<th class="text-center" data-filterable="true" data-sortable="true">CI</th>
										<th class="text-center" data-filterable="true" data-sortable="true">Credits</th>



										<?php if ($user->isAdmin()): ?>
											<th class="text-center" data-filterable="false" class="hidden-xs hidden-sm">
												Action
											</th>
										<?php endif; ?>
									</tr>
									</thead>
									<tbody>

									<?php
									foreach (array_reverse($students) as $student)
									{
										$studentAppointments = Appointment::findAppointmentsForStudentWithId($student[StudentFetcher::DB_COLUMN_ID], $appointments);
										include(ROOT_PATH . "views/partials/student/update-table-data-view.html.php");
									} ?>
									</tbody>
								</table>
							</div>
							<!-- /.table-responsive -->
						</div>
						<!-- /.portlet-content -->

					</div>
					<!-- /.portlet -->

				</div>
				<!-- /.col -->

			</div>
			<!-- /.row -->


		</div>
		<!-- /#content-container -->

	</div>
	<!-- /#content -->

	<div id="addStudentModal" class="modal modal-styled fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<form method="post" id="add-student-form" action="<?php echo BASE_URL . 'academia/students'; ?>"
				      class="form">

					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3 class="modal-title">Add Student Form</h3>
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
								if (isBtnAddStudentPrsd())
								{
									?>
									<div class="alert alert-success">
										<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
										<strong>Student successfully created!</strong> <br/>
									</div>
								<?php }
							} ?>

							<div class="portlet-content">

								<div class="row">


									<div class="col-sm-12">

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="firstName">First Name</label>
											</h5>
											<input type="text" id="firstName" name="firstName" class="form-control"
											       value="<?php if (isset($_POST['firstName']))
											       {
												       echo htmlentities($_POST['firstName']);
											       } ?>"
											       autofocus="on" placeholder="Required" required>
										</div>

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="lastName">Last Name</label>
											</h5>
											<input type="text" id="lastName" name="lastName" class="form-control"
											       value="<?php if (isset($_POST['lastName']))
											       {
												       echo htmlentities($_POST['lastName']);
											       } ?>"
											       required placeholder="Required">
										</div>

										<div class="form-group">

											<i class="fa fa-envelope"></i>
											<label for="email">Email</label>
											<input type="email" required id="email" name="email" class="form-control"
											       value="<?php if (isset($_POST['email']))
											       {
												       echo htmlentities($_POST['email']);
											       } ?>"
											       placeholder="Required">
										</div>


										<div class="form-group">

											<h5>
												<i class="fa fa-tasks"></i>
												<label for="studentId">Student ID</label>
											</h5>

											<input class="form-control" id="studentId" name="studentId" type="text"
											       value="<?php if (isset($_POST['studentId']))
											       {
												       echo htmlentities($_POST['studentId']);
											       } ?>"
											       placeholder="123456">

										</div>

										<div class="form-group">
											<h5>
												<i class="fa fa-tasks"></i>
												<label for="mobileNum">Mobile Number</label>
												<input class="form-control" id="mobileNum" name="mobileNum" type="text"
												       value="<?php if (isset($_POST['mobileNum']))
												       {
													       echo htmlentities($_POST['mobileNum']);
												       } ?>"
												       placeholder="69........">


											</h5>
										</div>


										<div class="form-group">
											<h5>
												<i class="fa fa-tasks"></i>
												<label for="userMajorId">Major</label>
											</h5>
											<select id="userMajorId" name="userMajorId" class="form-control">
												<?php foreach ($majors as $major)
												{
													include(ROOT_PATH . "views/partials/major/select-options-view.html.php");
												}
												?>
											</select>
										</div>


										<div class="form-group">
											<div class="col-sm-6">

												<div class="form-group">
													<div class="input-group">
														<span class="input-group-addon">CI</span>
														<input class="form-control" id="ciInput" name="ciInput"
														       type="text"
														       placeholder="e.g. 3.5"
														       value="<?php if (isset($_POST['ciInput']))
														       {
															       echo htmlentities($_POST['ciInput']);
														       } ?>">

													</div>
												</div>
											</div>
											<div class="col-sm-6">

												<div class="form-group">
													<div class="input-group">
														<input class="form-control" id="creditsInput"
														       name="creditsInput"
														       type="text"
														       value="<?php if (isset($_POST['creditsInput']))
														       {
															       echo htmlentities($_POST['creditsInput']);
														       } ?>"
														       placeholder="e.g. 50">
														<span class="input-group-addon">Credits</span>
													</div>
												</div>

											</div>
										</div>

									</div>
								</div>

							</div>

						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-tertiary" data-dismiss="modal">Close</button>
						<input type="hidden" name="hiddenSubmitPressed" value="">
						<button type="submit" class="btn btn-primary">Create</button>
					</div>
				</form>

			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>


	<div id="addMajorModal" class="modal modal-styled fade">
		<div class="modal-dialog modal-sm">
			<div class="modal-content">
				<form method="post" id="create-form" action="<?php echo BASE_URL . 'academia/students'; ?>"
				      class="form">

					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3 class="modal-title">Create Major</h3>
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
								if (isModificationSuccess())
								{
									?>
									<div class="alert alert-success">
										<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
										<strong>Data successfully modified!</strong> <br/>
									</div>
								<?php }
							} ?>
							<div class="portlet-content">
								<div class="row">
									<div class="col-sm-12">

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="majorCode">Major Code</label>
											</h5>
											<input type="text" id="majorCode" name="majorCode" class="form-control"
											       value="<?php if (isset($_POST['majorCode']))
											       {
												       echo
												       htmlentities($_POST['majorCode']);
											       } ?>"
											       autofocus="on" required>
										</div>

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="majorName">Major Name</label>
											</h5>
											<input type="text" id="majorName" name="majorName" class="form-control"
											       value="<?php if (isset($_POST['majorName']))
											       {
												       echo
												       htmlentities($_POST['majorName']);
											       } ?>"
											       required>

										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-tertiary" data-dismiss="modal">Close</button>
						<input type="hidden" name="hiddenCreateMajor">
						<button type="submit" class="btn btn-primary">Create</button>
					</div>
				</form>

			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>
	<!-- /.modal -->

	<div id="updateStudent" class="modal modal-styled fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<form method="post" id="add-student-form" action="<?php echo BASE_URL . 'academia/students'; ?>"
				      class="form">

					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3 class="modal-title">Update Student Form</h3>
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
								if (isBtnAddStudentPrsd())
								{
									?>
									<div class="alert alert-success">
										<a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
										<strong>Student successfully created!</strong> <br/>
									</div>
								<?php }
							} ?>

							<div class="portlet-content">

								<div class="row">


									<div class="col-sm-12">

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="firstName">First Name</label>
											</h5>
											<input type="text" id="newFirstName" name="newFirstName"
											       class="form-control"
											       value="<?php if (isset($_POST['newFirstName']))
											       {
												       echo htmlentities($_POST['newFirstName']);
											       } ?>"
											       autofocus="on" placeholder="Required" required>
										</div>

										<div class="form-group">
											<h5>
												<i class="fa fa-edit"></i>
												<label for="newLastName">Last Name</label>
											</h5>
											<input type="text" id="newLastName" name="newLastName" class="form-control"
											       value="<?php if (isset($_POST['newLastName']))
											       {
												       echo htmlentities($_POST['newLastName']);
											       } ?>"
											       required placeholder="Required">
										</div>


										<div class="form-group">

											<h5>
												<i class="fa fa-tasks"></i>
												<label for="newStudentId">Student ID</label>
											</h5>

											<input class="form-control" id="newStudentId" name="newStudentId"
											       type="text"
											       value="<?php if (isset($_POST['newStudentId']))
											       {
												       echo htmlentities($_POST['newStudentId']);
											       } ?>"
											       placeholder="123456">

										</div>

										<div class="form-group">

											<i class="fa fa-envelope"></i>
											<label for="newEmail">Email</label>
											<input type="email" required id="newEmail" name="newEmail"
											       class="form-control"
											       value="<?php if (isset($_POST['newEmail']))
											       {
												       echo htmlentities($_POST['newEmail']);
											       } ?>"
											       placeholder="Required">
										</div>


										<div class="form-group">
											<h5>
												<i class="fa fa-tasks"></i>
												<label for="newMobileNum">Mobile Number</label>
												<input class="form-control" id="newMobileNum" name="newMobileNum"
												       type="text"
												       value="<?php if (isset($_POST['newMobileNum']))
												       {
													       echo htmlentities($_POST['newMobileNum']);
												       } ?>"
												       placeholder="69........">


											</h5>
										</div>


										<div class="form-group">
											<h5>
												<i class="fa fa-tasks"></i>
												<label for="newStudentMajorId">Major</label>
											</h5>
											<select id="newStudentMajorId" name="newStudentMajorId"
											        class="form-control">
												<?php foreach ($majors as $major)
												{
													include(ROOT_PATH . "views/partials/major/select-options-view.html.php");
												}
												?>
											</select>
										</div>


										<div class="form-group">
											<div class="col-sm-6">

												<div class="form-group">
													<div class="input-group">
														<span class="input-group-addon">CI</span>
														<input class="form-control" id="newCI" name="newCI" type="text"
														       placeholder="e.g. 3.5"
														       value="<?php if (isset($_POST['newCI']))
														       {
															       echo htmlentities($_POST['newCI']);
														       } ?>">

													</div>
												</div>
											</div>
											<div class="col-sm-6">

												<div class="form-group">
													<div class="input-group">
														<input class="form-control" id="newCreditsNum"
														       name="newCreditsNum"
														       type="text"
														       value="<?php if (isset($_POST['newCreditsNum']))
														       {
															       echo htmlentities($_POST['newCreditsNum']);
														       } ?>"
														       placeholder="e.g. 50">
														<span class="input-group-addon">Credits</span>
														<input type="hidden" value="" id="idUpdate" name="idUpdate"/>

													</div>
												</div>

											</div>
										</div>

									</div>
								</div>

							</div>

						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-tertiary" data-dismiss="modal">Close</button>
						<input type="hidden" name="hiddenUpdatePrsd" value="">
						<button type="submit" class="btn btn-primary">Update</button>
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
<!-- #wrapper -->

<?php include ROOT_PATH . "views/assets/footer_common.php"; ?>

<script src="<?php echo BASE_URL; ?>assets/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/datatables/DT_bootstrap.js"></script>

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

		$("#userMajorId").select2();
		$("#newStudentMajorId").select2();

		$(".btnUpdateStudent").click(function ()
		{
			var id = $(this).next().val();
			var studentsCredits = ($(this).parent().prev().text());
			var studentCi = ($(this).parent().prev().prev().text());
			var studentMajorId = ($(this).parent().prev().prev().prev().find("input:first")).val();
			var studentMobile = ($(this).parent().prev().prev().prev().prev().text());
			var studentEmail = ($(this).parent().prev().prev().prev().prev().prev().prev().text());
			var studentId = ($(this).parent().prev().prev().prev().prev().prev().prev().prev().text());
			var firstName = ($(this).parent().prev().prev().prev().prev().prev().prev().prev().prev().find("span:first")).text();
			var lastName = ($(this).parent().prev().prev().prev().prev().prev().prev().prev().prev().find("span:first")).next().text();

			$("#newFirstName").val(firstName);
			$("#newLastName").val(lastName);
			$("#newEmail").val(studentEmail);
			$("#newStudentId").val(studentId);
			$("#newMobileNum").val(studentMobile);
			$("#newStudentMajorId").select2("val", studentMajorId); // select "5"
			$("#newCI").val(studentCi);
			$("#newCreditsNum").val(studentsCredits);
			$("#idUpdate").val(id);
		});

	});


</script>

</body>
</html>

