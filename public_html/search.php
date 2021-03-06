<?php
require __DIR__ . '/app/init.php';
$general->loggedOutProtect();
// redirect if user elevation is not that of admin
if (!$user->isAdmin())
{
	header('Location: ' . BASE_URL . "error-403");
	exit();
}

$curTerms = TermFetcher::retrieveAll();
$students = StudentFetcher::retrieveAll();
$courses = CourseFetcher::retrieveAll();
$instructors = InstructorFetcher::retrieveAll();
$tutors = TutorFetcher::retrieveAll();

$pageTitle = "Search";
$section = "search";
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
			<h1><?php echo $pageTitle; ?></h1>
		</div>
		<!-- #content-header -->

		<div id="content-container">

			<div class="row">

				<div class="col-md-12 col-sm-12">

					<div class="portlet">

						<div class="portlet-header">

							<h3>
								<i class="fa fa-search"></i>
								<?php echo 'Search Criterias' ?>
							</h3>

						</div>
						<!-- /.portlet-header -->

						<div class="portlet-content">

							<h4>Range of Appointments or term(s)</h4>

							<div class="row">
								<div class="col-sm-3">
									<input type='text' name='dateTimePickerStart' class="form-control"
									       placeholder="Start Date"
									       id='dateTimePickerStart' required/>
								</div>

								<div class="col-sm-3">
									<input type='text' name='dateTimePickerStart' class="form-control"
									       placeholder="End Date"
									       id='dateTimePickerEnd' required/>
								</div>

								<!-- </div> -->

								<div class="col-sm-6">
									<select id="selectedTerms" name="selectedTerms" class="form-control" required
									        multiple placeholder="Select term(s)">
										<?php
										foreach ($curTerms as $term)
										{
											include(ROOT_PATH . "views/partials/term/select-options-view.html.php");
										}
										?>
									</select>
								</div>

							</div>
							<!-- row -->
							<hr>
							<div class="row">

								<div class="col-sm-6">
									<div class="form-group">
										<label for="select-appointments-status">Appointment Status</label>
										<select id="select-appointments-status" class="form-control" multiple
										        placeholder="Statuses list">
											<option>Complete</option>
											<option>Canceled by tutor</option>
											<option>Canceled by student</option>
											<option>No show by student</option>
											<option>No show by tutor</option>
											<option>Disabled by admin</option>
										</select>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="select-reports-status">Report Status</label>
										<select id="select-reports-status" class="form-control" multiple
										        placeholder="Statuses list">
											<option>Complete</option>
											<option>Pending fill</option>
											<option>Pending validation</option>
										</select>
									</div>
								</div>

							</div>
							<!-- row -->
							<hr>
							<div class="row">

								<div class="col-sm-3 ">

									<label for="select-learning-facilitator">Select a L.F.</label>
									<select id="select-learning-facilitator" class="form-control">

										<?php
										foreach ($tutors as $tutor):
											include(ROOT_PATH . "views/partials/tutor/select-options-view.html.php");
										endforeach;
										?>
									</select>

								</div>
								<div class="col-sm-3 ">

									<label for="select-student">Select a Student</label>
									<select id="select-student" name="studentsIds[]" class="form-control"
									        placeholder="Students list">
										<option></option>
										<?php
										foreach ($students as $student):
											include(ROOT_PATH . "views/partials/student/select-options-view.html.php");
										endforeach;
										?>
									</select>

								</div>
								<div class="col-sm-3 ">

									<label for="select-course">Select a Course</label>
									<select id="select-course" name="select-course" class="form-control"
									        placeholder="Courses list">
										<option></option>
										<?php foreach ($courses as $course)
										{
											include(ROOT_PATH . "views/partials/course/select-options-view.html.php");
										}
										?>
									</select>

								</div>
								<div class="col-sm-3 ">

									<label for="select-instructor">Select an Instructor</label>
									<select id="select-instructor" name="select-instructor" class="form-control"
									        placeholder="Instructors list">
										<option></option>
										<?php foreach ($instructors as $instructor)
										{
											include(ROOT_PATH . "views/partials/instructor/select-options-view.html.php");
										}
										?>
									</select>
								</div>

							</div>
							<!-- row -->
							<br>

							<div class="row">
								<div
									class="col-lg-6 col-lg-push-3 col-md-8 col-md-push-2 col-sm-10 col-sm-push-1">
									<div class="form-group">
										<button type="submit" class="btn btn-block btn-primary">Search</button>
										<input type="hidden" name="hiddenSubmitPrsd" value="">
									</div>
								</div>
							</div>
							<!-- /.row -->

						</div>
						<!-- portlet-content -->

					</div>
					<!-- /.portlet -->

				</div>
				<!-- /.col -->

			</div>
			<!-- /.row -->


		</div>
		<!-- content-container -->

	</div>
	<!-- content -->

	<?php include ROOT_PATH . "views/footer.php"; ?>

</div>
<!-- #wrapper -->

<?php include ROOT_PATH . "views/assets/footer_common.php"; ?>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/select2/select2.js"></script>

<script
	src="<?php echo BASE_URL; ?>assets/js/plugins/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js">
</script>

<script>

	$(function () {
		moment().format();
		var startDateDefault = moment();

		$("#selectedTerms").select2();
		$("#select-appointments-status").select2();
		$("#select-reports-status").select2();
		$("#select-learning-facilitator").select2();
		$("#select-student").select2();
		$("#select-course").select2();
		$("#select-instructor").select2();

		$("#dateTimePickerStart").datetimepicker();
		$("#dateTimePickerEnd").datetimepicker();
	});
</script>

</body>
</html>