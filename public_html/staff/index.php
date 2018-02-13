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
 * @author Rizart Dokollari
 * @author George Skarlatos
 * @since 8/16/14.
 */

require __DIR__ . '/../app/init.php';
$general->loggedOutProtect();

$pageTitle = "Personnel";
$section = "staff";

try {

// protect again any sql injections on url
    if (isset($_GET['id']) && preg_match("/^[0-9]+$/", $_GET['id'])) {
        $userId = $_GET['id'];
        $pageTitle = "Profile";
        if (($data = User::getSingle($userId)) === false) {
            header('Location: ' . BASE_URL . 'error-404');
            exit();
        }

        if (strcmp($data['type'], 'tutor') === 0) {
            $tutor = TutorFetcher::retrieveSingle($userId);
            $curUser = new Tutor($data['id'], $data['f_name'], $data['l_name'], $data['email'], $data['mobile'], $data['img_loc'], $data['profile_description'], $data['date'], $data['type'], $data['active'], $tutor[MajorFetcher::DB_COLUMN_NAME]);

            $schedules = ScheduleFetcher::retrieveCurrWorkingHours($curUser->getId());
            $teachingCourses = TutorFetcher::retrieveCurrTermTeachingCourses($curUser->getId());
        } else if (strcmp($data['type'], 'secretary') === 0) {
            $curUser = new Secretary($data['id'], $data['f_name'], $data['l_name'], $data['email'], $data['mobile'], $data['img_loc'], $data['profile_description'], $data['date'], $data['type'], $data['active']);
        } else if (strcmp($data['type'], 'admin') === 0) {
            $curUser = new Admin($data['id'], $data['f_name'], $data['l_name'], $data['email'], $data['mobile'], $data['img_loc'], $data['profile_description'], $data['date'], $data['type'], $data['active']);
        } else {
            throw new Exception("Something terrible has happened with the database. <br/>The software developers will tremble with fear.");
        }

    } else if (isBtnInactivePrsd()) {
        $users = User::retrieveAllInactive();
        $sectionTitle = "Inactive Members";
    } else if (empty($_GET)) { // default page - all membersJ
        $users = User::retrieveAll();
        $courses = CourseFetcher::retrieveAll();
        $sectionTitle = "Active Members";
    } else {
        header('Location: ' . BASE_URL . 'error-404');
        exit();
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

function get($objects, $findId, $column)
{
    foreach ($objects as $object) {
        if ($object[$column] === $findId) return $object;
    }

    return false;
}

function isEditBttnPressed()
{
    return isset($_GET['id']) && preg_match('/^[0-9]+$/', $_GET['id']);
}

function isModifyBttnPressed()
{
    return isset($_POST['hidden_submit_pressed']) && empty($_POST['hidden_submit_pressed']);
}

function isBtnInactivePrsd()
{
    return isset($_POST['inactive']) && empty($_POST['inactive']);
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
            <h1><?php echo $pageTitle; ?></h1>
        </div>
        <!-- #content-header -->


        <?php if (!empty($userId)): ?>
            <div id="content-container">


                <div class="row">

                    <div class="col-md-9">

                        <div class="row">

                            <div class="col-md-4 col-sm-5">

                                <div class="thumbnail">
                                    <img src="<?php echo BASE_URL . $curUser->getAvatarImgLoc(); ?>"
                                         alt="Profile Picture"/>
                                </div>
                                <!-- /.thumbnail -->

                                <br/>

                            </div>
                            <!-- /.col -->


                            <div class="col-md-8 col-sm-7">

                                <h2><?php echo $curUser->getFirstName() . " " . $curUser->getLastName(); ?></h2>

                                <h4>Position: <?php echo ucfirst($curUser->getUserType()) ?></h4>

                                <hr/>


                                <ul class="icons-list">
                                    <li><i class="icon-li fa fa-envelope"></i> <?php echo $curUser->getEmail(); ?></li>
                                    <li>
                                        <i class="icon-li fa fa-phone"></i>Mobile: <?php echo $curUser->getMobileNum() ?>
                                    </li>
                                </ul>
                                <?php if ($curUser->isTutor()) { ?>

                                    Major: <strong><?php echo $curUser->getMajorId(); ?></strong>

                                <?php } ?>
                                <br/>
                                <br/>

                                <h3>About me</h3>

                                <p><?php echo $curUser->getProfileDescription() ?></p>

                                <hr/>

                                <br/>

                            </div>

                        </div>

                    </div>

                </div>
                <!-- /.row -->

                <?php if (!$user->isTutor()): ?>
                    <?php if ($curUser->isTutor()): ?>

                        <!-- /.row -->
                        <div class="row">

                            <div class="col-md-10">
                                <h3 class="heading">Special Information</h3>


                                <div class="panel-group accordion" id="accordion">

                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <h4 class="panel-title">
                                                <a class="accordion-toggle" data-toggle="collapse"
                                                   data-parent=".accordion"
                                                   href="#collapseOne">
                                                    <i class="fa fa-book"></i>
                                                    <?php
                                                    if (empty($teachingCourses)) {
                                                        echo 'The list of Teaching Courses of the current term is empty!';
                                                    } else {
                                                        echo 'Current Teaching Courses - ' . $teachingCourses[0][TermFetcher::DB_TABLE . "_" . TermFetcher::DB_COLUMN_NAME];
                                                    }
                                                    ?>
                                                </a>
                                            </h4>
                                        </div>

                                        <div id="collapseOne" class="panel-collapse collapse in">
                                            <div class="panel-body">
                                                <table class="table table-hover">
                                                    <thead>
                                                    <tr>
                                                        <th class="text-center">#</th>
                                                        <th class="text-center">Course Code</th>
                                                        <th class="text-center">Course Name</th>
                                                        <th class="text-center">Status</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>

                                                    <?php
                                                    if (empty($errors) === true) {
                                                        $counter = 1;
                                                        foreach ($teachingCourses as $course) {
                                                            include(ROOT_PATH . "views/partials/course/table-data-profile-view.html.php");
                                                            $counter = $counter + 1;
                                                        }
                                                    } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- #collapseOne -->
                                    </div>
                                    <!-- /.panel-default -->

                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <h4 class="panel-title">
                                                <a class="accordion-toggle" data-toggle="collapse"
                                                   data-parent=".accordion"
                                                   href="#collapseTwo">
                                                    <i class="fa fa-clock-o"></i>
                                                    <?php
                                                    if (empty($schedules)) {
                                                        echo 'Current schedule is empty!';
                                                    } else {
                                                        echo 'Current Schedule - ' . $teachingCourses[0][TermFetcher::DB_TABLE . "_" . TermFetcher::DB_COLUMN_NAME];
                                                    }
                                                    ?>
                                                </a>
                                            </h4>
                                        </div>

                                        <div id="collapseTwo" class="panel-collapse collapse">
                                            <div class="panel-body">

                                                <div class="table-responsive">
                                                    <table class="table table-hover">

                                                        <thead>
                                                        <tr>
                                                            <th class="text-center">Days</th>
                                                            <th class="text-center">Start - End</th>
                                                            <th class="text-center">Term</th>
                                                            <th class="text-center">Status</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>

                                                        <?php
                                                        if (empty($errors) === true) {
                                                            foreach ($schedules as $schedule) {
                                                                include(ROOT_PATH . "views/partials/schedule/profile-table-data-view.html.php");
                                                            }
                                                        }
                                                        ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- /.table-responsive -->

                                                <!-- <div class="col-md-9">
                                                    <div class="portlet-header">

                                                    </div> -->
                                                <!-- /.portlet-header -->

                                                <!-- <div class="portlet-content">

                                                    <div id="appointments-calendar"></div>
                                                </div>
                                            </div> -->

                                            </div>
                                            <!-- /.panel-default -->
                                        </div>
                                        <!-- #collapseTwo -->
                                    </div>
                                    <!-- /.panel-default -->
                                </div>
                                <!-- /.accordion -->
                            </div>

                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
            <!-- /#content-container -->

        <?php
        // show all users
        else: ?>

            <div id="content-container">
                <?php
                if (empty($errors) === false) {
                    ?>
                    <div class="alert alert-danger">
                        <a class="close" data-dismiss="alert" href="#" aria-hidden="true">×</a>
                        <strong>Oh snap!</strong><?php echo '<p>' . implode('</p><p>', $errors) . '</p>'; ?>
                    </div>
                <?php
                } ?>
                <div class="row">

                    <div class="col-md-12 col-sm-12">

                        <div class="portlet">

                            <div class="portlet-header">

                                <h3>
                                    <i class="fa fa-group"></i>
                                    <?php echo $sectionTitle ?>
                                </h3>

                                <?php if (!$user->isTutor()) { ?>
                                    <div class="portlet-tools pull-right">
                                        <div class="btn-group ">
                                            <button class="btn btn-default btn-sm dropdown-toggle" type="button"
                                                    data-toggle="dropdown">
                                                <i class="fa fa-group"></i> &nbsp;
                                                Active / Inactive <span class="caret"></span>
                                            </button>

                                            <ul class="dropdown-menu" role="menu">
                                                <li>

                                                    <a onclick="submitActiveFunction()">Active Members</a>

                                                    <form id="active" method="post"
                                                          action="">
                                                        <input type="hidden" name="active"/>
                                                    </form>
                                                </li>
                                                <li>
                                                    <a onclick="submitInactiveFunction()">Inactive Members</a>

                                                    <form id="inactive" method="post"
                                                          action="">
                                                        <input type="hidden" name="inactive"/>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                <?php } ?>

                            </div>
                            <!-- /.portlet-header -->

                            <div class="portlet-content">
                                <div class="table-responsive">
                                    <table
                                        class="table table-striped table-bordered table-hover table-highlight table-checkable"
                                        data-provide="datatable"
                                        data-info="true"
                                        data-search="true"
                                        data-length-change="true"
                                        data-paginate="false"
                                        id="usersTable"
                                        >
                                        <thead>
                                        <tr>
                                            <th class="text-center" data-filterable="true" data-sortable="true"
                                                data-direction="desc">
                                                Name
                                            </th>
                                            <th class="text-center" data-direction="asc" data-filterable="true"
                                                data-sortable="false">
                                                Email
                                            </th>
                                            <th class="text-center" data-filterable="true" data-sortable="true">
                                                Position
                                            </th>
                                            <th class="text-center" data-filterable="true" data-sortable="false">
                                                Mobile
                                            </th>
                                            <th class="text-center" data-filterable="false" class="hidden-xs hidden-sm">
                                                Profile
                                            </th>

                                            <?php if (!$user->isTutor()): ?>
                                                <th class="text-center" data-filterable="false"
                                                    class="hidden-xs hidden-sm">Teaching
                                                </th>
                                            <?php endif; ?>

                                            <?php if (!$user->isTutor()): ?>
                                                <th class="text-center" data-filterable="false"
                                                    class="hidden-xs hidden-sm">Schedule
                                                </th>
                                            <?php endif; ?>


                                            <?php if ($user->isAdmin()): ?>
                                                <th class="text-center" data-filterable="false"
                                                    class="hidden-xs hidden-sm">Data
                                                </th>
                                            <?php endif; ?>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        <?php
                                        if (empty($errors) === true) {
                                            foreach (array_reverse($users) as $curUser) {
                                                include(ROOT_PATH . "views/partials/user/all-table-data-view.html.php");
                                            }
                                        } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.table-responsive -->

                            </div>

                        </div>
                        <!-- /.portlet -->

                    </div>
                    <!-- /.col -->

                </div>
                <!-- /.row -->


            </div>
        <?php endif; ?>
    </div>
    <!-- #content -->

    <?php include ROOT_PATH . "views/footer.php"; ?>

</div>
<!-- #wrapper -->

<?php include ROOT_PATH . "views/assets/footer_common.php"; ?>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/select2/select2.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/icheck/jquery.icheck.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/datatables/DT_bootstrap.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/plugins/datepicker/bootstrap-datepicker.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/timepicker/bootstrap-timepicker.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/simplecolorpicker/jquery.simplecolorpicker.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/textarea-counter/jquery.textarea-counter.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/autosize/jquery.autosize.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/demos/form-extended.js"></script>

<script type="text/javascript">

    function submitActiveFunction() {
        document.getElementById("active").submit();
    }
    function submitInactiveFunction() {
        document.getElementById("inactive").submit();
    }
</script>
</body>
</html>


