<?php

/**
 * Created by PhpStorm.
 * User: rdok
 * Date: 9/26/2014
 * Time: 9:50 PM
 */
class Report
{

	const LABEL_COLOR_WARNING = "warning";
	const LABEL_MESSAGE_PENDING_FILL = "pending fill";
	const LABEL_MESSAGE_PENDING_VALIDATION = "pending validation";
	const LABEL_MESSAGE_COMPLETE = "complete";
	const LABEL_COLOR_SUCCESS = "success";


	public static function getAllWithAppointmentId($appointmentId) {
		Appointment::validateId($appointmentId);
		return ReportFetcher::retrieveAllWithAppointmentId($appointmentId);
	}

	public static function updateLabel($formReportId, $message, $color) {
		ReportFetcher::updateLabel($formReportId, $message, $color);

	}

	public static function updateConclusionWrapUp($reportId, $newOptions, $oldOptions) {
		if ($newOptions === NULL) $newOptions = [];
		self::validateOptionsConclusionWrapUp($newOptions);
		// TODO: check if update is needed. if not, return false.
		self::validateId($reportId);
		return ConclusionWrapUpFetcher::update($reportId, $newOptions, $oldOptions);
	}

	public static function validateOptionsConclusionWrapUp($newOptions) {
		foreach ($newOptions as $option => $value) {
			switch ($option) {
				case ConclusionWrapUpFetcher::DB_COLUMN_QUESTIONS_ADDRESSED:
				case ConclusionWrapUpFetcher::DB_COLUMN_ANOTHER_SCHEDULE:
				case ConclusionWrapUpFetcher::DB_COLUMN_CLARIFY_CONCERNS:
					break;
				default:
					throw new Exception("Data have been malformed. Aborting process.");
					break;
			}
		}
	}

	public static function validateId($id) {
		if (!preg_match('/^[0-9]+$/', $id) || !ReportFetcher::existsId($id)) {
			throw new Exception("Data tempering detected.
			<br/>You&#39;re trying to hack this app.<br/>Developers are being notified about this.<br/>Expect Us.");
		}
	}

	public static function convertFocusOfConferenceToString($options) {
		$purposes = "";
		$commaPattern = ", ";
		if (strcmp($options[PrimaryFocusOfConferenceFetcher::DB_COLUMN_DISCUSSION_OF_CONCEPTS], '1') === 0) {
			$purposes = PrimaryFocusOfConferenceFetcher::STRING_DISCUSSION_OF_CONCEPTS . $commaPattern;
		}

		if (strcmp($options[PrimaryFocusOfConferenceFetcher::DB_COLUMN_ORGANIZATION_THOUGHTS_IDEAS], '1') === 0) {
			$purposes = $purposes . PrimaryFocusOfConferenceFetcher::STRING_ORGANIZATION_THOUGHTS_IDEAS . $commaPattern;
		}

		if (strcmp($options[PrimaryFocusOfConferenceFetcher::DB_COLUMN_EXPRESSION_GRAMMAR_SYNTAX_ETC], '1') === 0) {
			$purposes = $purposes . PrimaryFocusOfConferenceFetcher::STRING_EXPRESSION_GRAMMAR_SYNTAX_ETC . $commaPattern;
		}

		if (strcmp($options[PrimaryFocusOfConferenceFetcher::DB_COLUMN_EXERCISES], '1') === 0) {
			$purposes = $purposes . PrimaryFocusOfConferenceFetcher::STRING_EXERCISES . $commaPattern;
		}
		if (strcmp($options[PrimaryFocusOfConferenceFetcher::DB_COLUMN_ACADEMIC_SKILLS], '1') === 0) {
			$purposes = $purposes . PrimaryFocusOfConferenceFetcher::STRING_ACADEMIC_SKILLS . $commaPattern;
		}

		if (strcmp($options[PrimaryFocusOfConferenceFetcher::DB_COLUMN_CITATIONS_REFERENCING], '1') === 0) {
			$purposes = $purposes . PrimaryFocusOfConferenceFetcher::STRING_CITATIONS_REFERENCING . $commaPattern;
		}

		if (strcmp($options[PrimaryFocusOfConferenceFetcher::DB_COLUMN_OTHER], '1') === 0) {
			$purposes = $purposes . PrimaryFocusOfConferenceFetcher::STRING_OTHER . $commaPattern;
		}

		return rtrim($purposes, $commaPattern);
	}

	public static function deleteWithAppointmentId($appointmentId) {
		Appointment::validateId($appointmentId);
		return ReportFetcher::deleteWithAppointmentId($appointmentId);
	}

	public static function updatePrimaryFocusOfConference($reportId, $newOptions, $oldOptions) {
		if ($newOptions === NULL) $newOptions = [];
		self::validateOptionsPrimaryFocusOfConference($newOptions);
		// TODO: check if update is needed. if not, return false.
		self::validateId($reportId);
		return PrimaryFocusOfConferenceFetcher::update($reportId, $newOptions, $oldOptions);
	}

	public static function validateOptionsPrimaryFocusOfConference($newOptions) {
		foreach ($newOptions as $option => $value) {
			switch ($option) {
				case PrimaryFocusOfConferenceFetcher::DB_COLUMN_DISCUSSION_OF_CONCEPTS:
				case PrimaryFocusOfConferenceFetcher::DB_COLUMN_ORGANIZATION_THOUGHTS_IDEAS:
				case PrimaryFocusOfConferenceFetcher::DB_COLUMN_EXPRESSION_GRAMMAR_SYNTAX_ETC:
				case PrimaryFocusOfConferenceFetcher::DB_COLUMN_EXERCISES:
				case PrimaryFocusOfConferenceFetcher::DB_COLUMN_ACADEMIC_SKILLS:
				case PrimaryFocusOfConferenceFetcher::DB_COLUMN_CITATIONS_REFERENCING:
				case PrimaryFocusOfConferenceFetcher::DB_TABLE . "_" . PrimaryFocusOfConferenceFetcher::DB_COLUMN_OTHER:
					break;
				default:
					throw new Exception("Data have been malformed. Aborting process.");
					break;
			}
		}
	}

	public static function getWithAppointmentId($allReports, $appointmentId) {
		$reports = [];
		foreach ($allReports as $report) {
			if (strcmp($report[AppointmentFetcher::DB_TABLE . "_" . AppointmentFetcher::DB_COLUMN_ID], $appointmentId) === 0) {
				$reports[] = $report;
			}
		}

		return $reports;
	}

	public static function getSingle($reportId) {
		self::validateId($reportId);
		return ReportFetcher::retrieveSingle($reportId);
	}

	public static function updateProjectTopicOtherText($reportId, $oldText, $newText) {
		if (strcmp($oldText, $newText) === 0) return false;
		self::validateId($reportId);
		self::validateTextArea($newText, true);
		return ReportFetcher::updateSingleColumn($reportId, $newText, ReportFetcher::DB_COLUMN_PROJECT_TOPIC_OTHER);
	}

	public static function validateTextArea($text, $notRequired) {
		$notReqStringValidation = "/^[\\w\t\n\r\\ .,\\-]{0,512}$/";
		$reqStringValidation = "/^[\\w\t\n\r\\ .,\\-]{1,512}$/";
		$stringValidation = !$notRequired ? $reqStringValidation : $notReqStringValidation;

		if (!preg_match($stringValidation, $text)) {
			throw new Exception("Textareas can contain only <a href='http://www.regular-expressions.info/shorthand.html'
			target='_blank'>word characters</a>, spaces, carriage returns, line feeds and special characters <strong>.,</strong> of max size 512 characters.");
		}
	}

	public static function updateOtherText($reportId, $oldText, $newText) {
		if (strcmp($oldText, $newText) === 0) return false;
		self::validateId($reportId);
		self::validateTextArea($newText, true);
		return ReportFetcher::updateSingleColumn($reportId, $newText, ReportFetcher::DB_COLUMN_OTHER_TEXT_AREA);
	}

	public static function updateStudentsConcerns($reportId, $oldText, $newText) {
		if (strcmp($oldText, $newText) === 0) return false;
		self::validateId($reportId);
		self::validateTextArea($newText, true);
		return ReportFetcher::updateSingleColumn($reportId, $newText, ReportFetcher::DB_COLUMN_STUDENT_CONCERNS);
	}

	public static function updateRelevantFeedbackGuidelines($reportId, $oldText, $newText) {
		if (!isset($newText) || strcmp($oldText, $newText) === 0) return false;
		self::validateId($reportId);
		self::validateTextArea($newText, true);
		return ReportFetcher::updateSingleColumn($reportId, $newText, ReportFetcher::DB_COLUMN_RELEVANT_FEEDBACK_OR_GUIDELINES);
	}

	public static function updateAdditionalComments($reportId, $oldText, $newText) {
		if (strcmp($oldText, $newText) === 0) return false;
		self::validateId($reportId);
		self::validateTextArea($newText, true);
		return ReportFetcher::updateSingleColumn($reportId, $newText, ReportFetcher::DB_COLUMN_ADDITIONAL_COMMENTS);
	}

	public static function updateAllFields
	($reportId, $projectTopicOtherNew, $otherTextArea, $studentsConcernsTextArea,
	 $relevantFeedbackGuidelines, $studentBroughtAlongNew, $studentBroughtAlongOld,
	 $conclusionAdditionalComments, $primaryFocusOfConferenceNew, $primaryFocusOfConferenceOld, $conclusionWrapUpNew, $conclusionWrapUpOld) {
		self::validateId($reportId);
		self::validateTextArea($projectTopicOtherNew, false);
		self::validateTextArea($otherTextArea, true);
		self::validateTextArea($studentsConcernsTextArea, false);
		self::validateTextArea($relevantFeedbackGuidelines, true);
		self::validateOptionsStudentBroughtAlong($studentBroughtAlongNew);
		self::validateOptionsPrimaryFocusOfConference($primaryFocusOfConferenceNew);
		self::validateOptionsConclusionWrapUp($conclusionWrapUpNew);
		self::validateTextArea($conclusionAdditionalComments, true);
		return ReportFetcher::updateAllColumns($reportId, $projectTopicOtherNew, $otherTextArea, $studentsConcernsTextArea,
			$relevantFeedbackGuidelines, $studentBroughtAlongNew, $studentBroughtAlongOld, $conclusionAdditionalComments,
			$primaryFocusOfConferenceNew, $primaryFocusOfConferenceOld, $conclusionWrapUpNew, $conclusionWrapUpOld);
	}

	public
	static function validateOptionsStudentBroughtAlong($newOptions) {
		foreach ($newOptions as $option => $value) {
			switch ($option) {
				case StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_GRADED:
				case StudentBroughtAlongFetcher::DB_COLUMN_DRAFT:
				case StudentBroughtAlongFetcher::DB_COLUMN_INSTRUCTORS_FEEDBACK:
				case StudentBroughtAlongFetcher::DB_COLUMN_TEXTBOOK:
				case StudentBroughtAlongFetcher::DB_COLUMN_NOTES:
				case StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_SHEET:
				case StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON:
				case StudentBroughtAlongFetcher::DB_COLUMN_OTHER:
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON . "text":
				case StudentBroughtAlongFetcher::DB_COLUMN_OTHER . "text":
//					self::validateTextarea($newOptions[$option], true);
					// TODO: validate input fields
					break;
				default:
					throw new Exception("Data have been malformed.");
					break;
			}
		}
	}

	public static function updateStudentBroughtAlong($reportId, $newOptions, $oldOptions) {
		if ($newOptions === NULL) $newOptions = [];
		self::validateOptionsStudentBroughtAlong($newOptions);
		if (!self::validateIfUpdateIsNeeded($newOptions, $oldOptions)) return false;
		self::validateId($reportId);
		return StudentBroughtAlongFetcher::update($newOptions, $oldOptions, $reportId);
	}

	public static function validateIfUpdateIsNeeded($newOptions, $oldOptions) {

		foreach ($oldOptions as $key => $oldOption) {
			switch ($key) {
				case StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_GRADED:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_GRADED])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_SELECTED) === 0)
						|| (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_GRADED])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_NOT_SELECTED) === 0)
					) return true;
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_DRAFT:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_DRAFT])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_SELECTED) === 0)
						|| (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_DRAFT])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_NOT_SELECTED) === 0)
					) return true;
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_INSTRUCTORS_FEEDBACK:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_INSTRUCTORS_FEEDBACK])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_SELECTED) === 0)
						|| (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_INSTRUCTORS_FEEDBACK])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_NOT_SELECTED) === 0)
					) return true;
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_TEXTBOOK:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_TEXTBOOK])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_SELECTED) === 0)
						|| (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_TEXTBOOK])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_NOT_SELECTED) === 0)
					) return true;
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_NOTES:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_NOTES])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_SELECTED) === 0)
						|| (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_NOTES])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_NOT_SELECTED) === 0)
					) return true;
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_SHEET:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_SHEET])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_SELECTED) === 0)
						|| (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_ASSIGNMENT_SHEET])
							&& strcmp($oldOption, StudentBroughtAlongFetcher::IS_NOT_SELECTED) === 0)
					) return true;
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON . "text"])
						&& $oldOption !== NULL)
					) return true;
					if (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON])) {
						if (!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON . "text"]) ||
							!preg_match("/^[\\w\t\n\r\\ .,\\-]{1,512}$/", $newOptions[StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON . "text"])
						) {
							throw new Exception("Please input what exercise book/page/number the student brought along. (Word characters accepted only.");
						}
						if (strcmp($oldOption, $newOptions[StudentBroughtAlongFetcher::DB_COLUMN_EXERCISE_ON . "text"]) !== 0) {
							return true;
						}
					}
					break;
				case StudentBroughtAlongFetcher::DB_COLUMN_OTHER:
					if ((!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_OTHER . "text"])
						&& $oldOption !== NULL)
					) return true;
					if (isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_OTHER])) {
						if (!isset($newOptions[StudentBroughtAlongFetcher::DB_COLUMN_OTHER . "text"]) ||
							!preg_match("/^[\\w\t\n\r\\ .,\\-]{1,512}$/", $newOptions[StudentBroughtAlongFetcher::DB_COLUMN_OTHER . "text"])
						) {
							throw new Exception("Please input what exercise book/page/number the student brought along. (Word characters accepted only.");
						}
						if (strcmp($oldOption, $newOptions[StudentBroughtAlongFetcher::DB_COLUMN_OTHER . "text"]) !== 0) {
							return true;
						}
					}
					break;
				default:
					return false;
					break;
			}
		}
	}
} 