<?php

class SisApi {
	private $user = null;
	private $request;

	/**
	 * SisApi constructor.
	 */
	public function __construct() {
		$this->request = new Handlers\Rest();
	}

	public function verifyUserAuthentication($username, $password) {
		$username = strtolower($username);
		$this->cleanUpCookieFiles();

		$this->request->params = array(
			'timezoneOffset' => -120,
			'userid' => $username,
			'pwd' => urlencode($password),
			'ticket' => ''
		);

		//$loginData = $this->request->call('psp/S2PRD/EMPLOYEE/HRMS/?cmd=login&languageCd=ENG');
		$loginData = $this->request->call('psp/S2PRD/EMPLOYEE/HRMS/?cmd=login&languageCd=ENG');
		//$loginData = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/s/WEBLIB_PTBR.ISCRIPT1.FieldFormula.IScript_StartPage', 'get');
		//$loginData = $this->request->call('psp/S2PRD/EMPLOYEE/HRMS/h/?tab=DEFAULT', 'get');
		$loginData = $this->request->call('psp/S2PRD/EMPLOYEE/HRMS/h/?cmd=getCachedPglt&pageletname=MENU&tab=DEFAULT&PORTALPARAM_COMPWIDTH=Narrow&t=1460302053424&ptlayout=N', 'get');

		if(strpos($loginData, 'Self Service') === false) // Check if we are successfully logged in.
			throw new \Exceptions\IncorrectUserDetails("User details are incorrect.");

		$this->user = new \Models\User($username, $password);

		return true;
	}

	public function getUser() {
		return $this->user;
	}

	public function getDetailedUserData() {
		if(!$this->user)
			throw new \Exceptions\IncorrectUserDetails("User details are not found.");

		$this->request->setConfig(array('use_cookie_class' => true));
		$this->request->cookies->addRule('PS_DEVICEFEATURES=width:1920 height:1080 pixelratio:1 touch:0 geolocation:1 websockets:1 webworkers:1 datepicker:0 dtpicker:0 timepicker:0 dnd:1 sessionstorage:1 localstorage:1 history:1 canvas:1 svg:1 postmessage:1 hc:0');
		$userData = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SSS_STUDENT_CENTER.GBL?PORTALPARAM_PTCNAV=HC_SSS_STUDENT_CENTER&EOPP.SCNode=HRMS&EOPP.SCPortal=EMPLOYEE&EOPP.SCName=CO_EMPLOYEE_SELF_SERVICE&EOPP.SCLabel=Self%20Service&EOPP.SCPTfname=CO_EMPLOYEE_SELF_SERVICE&FolderPath=PORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.HC_SSS_STUDENT_CENTER&IsFolder=false&PortalActualURL=https%3a%2f%2fsis.hva.nl%3a8011%2fpsc%2fS2PRD%2fEMPLOYEE%2fHRMS%2fc%2fSA_LEARNER_SERVICES.SSS_STUDENT_CENTER.GBL&PortalContentURL=https%3a%2f%2fsis.hva.nl%3a8011%2fpsc%2fS2PRD%2fEMPLOYEE%2fHRMS%2fc%2fSA_LEARNER_SERVICES.SSS_STUDENT_CENTER.GBL&PortalContentProvider=HRMS&PortalCRefLabel=Student%20Center&PortalRegistryName=EMPLOYEE&PortalServletURI=https%3a%2f%2fsis.hva.nl%3a8011%2fpsp%2fS2PRD%2f&PortalURI=https%3a%2f%2fsis.hva.nl%3a8011%2fpsc%2fS2PRD%2f&PortalHostNode=HRMS&NoCrumbs=yes&PortalKeyStruct=yes', 'get');

		if(preg_match('/{EMPLID:"([0-9]{1,20})"};/', $userData, $studentNumberMatch)) {
			$this->user->setStudentNumber($studentNumberMatch[1]);
		}

		if(preg_match('/>' . $this->user->getStudentNumber() . '(.*?)<\/span>/', $userData, $nameMatch)) {
			$nameString = $nameMatch[1];

			if(preg_match('/\(([a-zA-Z0-9]{0,100})\)/', $nameString, $firstNameMatch)) {
				$this->user->setFirstname($firstNameMatch[1]);
			}

			if(strpos($nameString, '(') !== false) {
				$nameSplit = explode('(', $nameString);
				$this->user->setName(trim($nameSplit[0]));
			}
		}

		if(preg_match('/id=\'DERIVED_SSS_SCL_DESCR50\'>(.*?)<\/span>/', $userData, $phoneMatch)) {
			$this->user->setPhone($phoneMatch[1]);
		}

		if(preg_match('/id=\'DERIVED_SSS_SCL_EMAIL_ADDR\'>(.*?)<\/span>/', $userData, $emailMatch)) {
			$this->user->setEmail($emailMatch[1]);
		}

		if(preg_match('/class=\'PSHYPERLINKDISABLED\'  title=\'Display Name\' >(.*?)<\/span>/', $userData, $advisorMatch)) {
			$this->user->setAdvisor($advisorMatch[1]);
		}
	}

	public function getUserGrades() {
		if(!$this->user)
			throw new \Exceptions\IncorrectUserDetails("User details are not found.");

		$this->user->grades = array();

		$this->request->setConfig(array('use_cookie_class' => true));
		$this->request->cookies->addRule('PS_DEVICEFEATURES=width:1920 height:1080 pixelratio:1 touch:0 geolocation:1 websockets:1 webworkers:1 datepicker:0 dtpicker:0 timepicker:0 dnd:1 sessionstorage:1 localstorage:1 history:1 canvas:1 svg:1 postmessage:1 hc:0');
		$userGrades = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SSS_MY_CRSEHIST.GBL?Page=SSS_MY_CRSEHIST&Action=U', 'get');

		$tableSplit = explode('id=\'CRSE_HIST$scroll$0\'', $userGrades);
		if(isset($tableSplit[1])) {
			$gradesData = explode('<tr id=\'trCRSE_HIST$0_row', $tableSplit[1]);
			if($gradesData && count($gradesData) > 1) {
				unset($gradesData[0]);

				$highestGrades = array();
				$gradeCounter = 0;
				$gradesSum = 0;

				foreach($gradesData as $gradeRow) {
					$grade = array('courseName' => '', 'grade' => '', 'date' => '');

					if(preg_match('/a .*? class=\'PSHYPERLINK\' .*?>(.*?)<\/a>/', $gradeRow, $courseNameMatches)) {
						$grade['courseName'] = strip_tags($courseNameMatches[1]);
					}
					if(preg_match('/id=\'SNS_CRSE_GRADE\$([0-9]{0,10})\'>(.*?)<\/span>/', $gradeRow, $gradeMatches)) {
						$grade['grade'] = $gradeMatches[2];
					}
					if(preg_match('/ id=\'EFFDT\$([0-9]{0,10})\'>(.*?)<\/span>/', $gradeRow, $dateMatches)) {
						$grade['date'] = $dateMatches[2];
					}

					$numberFormattedGrade = str_replace(',', '.', $grade['grade']);
					if ($grade['grade'] == 'no result' || $grade['grade'] == 'not sat.')
						$numberFormattedGrade = 1; // if you have no result, the grade is 1.
					if ($grade['grade'] == 'pass')
						$numberFormattedGrade = 1337;

					if(is_numeric($numberFormattedGrade)) {
						if(array_key_exists($grade['courseName'], $highestGrades)) {

							if($numberFormattedGrade > $highestGrades[$grade['courseName']]) {
								if($numberFormattedGrade == 1337) {
								    $gradesSum -= $highestGrades[$grade['courseName']];
									$gradeCounter--; // take one off in case of 'no result'.
									$highestGrades[$grade['courseName']] = $numberFormattedGrade;
								} else {
									$gradesSum += ($numberFormattedGrade - $highestGrades[$grade['courseName']]);
									$highestGrades[$grade['courseName']] = $numberFormattedGrade;
								}
							}

						} else {
							$highestGrades[$grade['courseName']] = $numberFormattedGrade;
							if($numberFormattedGrade != 1337) {
                                $gradesSum += $numberFormattedGrade;
                                $gradeCounter++;
                            }
						}
					}

					$this->user->grades[] = new \Models\Grade($grade['courseName'], $grade['grade'], date('d-m-Y', strtotime($grade['date'])));
				}

                foreach($highestGrades as $courseName => $grade) {
                    if ((is_numeric($grade) && $grade >= 5.5) || $grade == 0)
                        $this->user->addPass();
                    else {
                        $this->user->addFail();
                    }
                }

				$this->user->setAverageGrade(number_format($gradesSum / $gradeCounter, 1, ',', '.'));

				return $this->user->grades;

			} else {
				throw new \Exceptions\GradesError("Splitsing on grade tr failed");
			}
		} else {
			throw new \Exceptions\GradesError("Table of grades not found");
		}
	}

	public function getRequirements() {
		if(!$this->user)
			throw new \Exceptions\IncorrectUserDetails("User details are not found.");


		$this->request->setConfig(array('use_cookie_class' => true, 'referer' => 'https://sis.hva.nl:8011/psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SAA_SS_DPR_ADB.GBL?Page=SAA_SS_DPR_ADB&Action=A&ICAction=DERIVED_SAA_DPR_SSS_EXPAND_ALL'));
		$this->request->cookies->addRule('PS_DEVICEFEATURES=width:1920 height:1080 pixelratio:1 touch:0 geolocation:1 websockets:1 webworkers:1 datepicker:0 dtpicker:0 timepicker:0 dnd:1 sessionstorage:1 localstorage:1 history:1 canvas:1 svg:1 postmessage:1 hc:0');


		//$requirementsData = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SAA_SS_DPR_ADB.GBL?Page=SAA_SS_DPR_ADB&Action=A&ICAction=DERIVED_SAA_DPR_SSS_EXPAND_ALL', 'get');
		$requirementsData = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SAA_SS_DPR_ADB.GBL?Page=SAA_SS_DPR_ADB&Action=A', 'get');


		$date = null;
		$score = 0;

		if(preg_match("/id='SAA_ADB_REPORT_SAA_RPT_DTTM_STAMP'>(.*?)<\/span>/", $requirementsData, $dateMatches)) {
			$date = strip_tags($dateMatches[1]);
		}

		if(preg_match_all("/<span class=PSLONGEDITBOX><ul><LI>(.*?)</", $requirementsData, $unitsMatches)) {
			if(isset($unitsMatches[1]) && count($unitsMatches[1]) > 0) {
				foreach($unitsMatches[1] as $unitsMatch) {
					if (preg_match_all('/([0-9.]{1,10})/', $unitsMatch, $match)) {
						list($required, $taken, $needed) = $match[1];

						if($required == 240) { // all the points needed
							$score = $taken;
							break;
						}
					}
				}
			}
		}

		$requirement = new \Models\RequirementReport();
		$requirement->setDate($date);
		$requirement->setScore($score);

		$this->user->requirementReport = $requirement;



		/*$this->request->setConfig(array('use_cookie_class' => true));
		$this->request->cookies->addRule('PS_DEVICEFEATURES=width:1920 height:1080 pixelratio:1 touch:0 geolocation:1 websockets:1 webworkers:1 datepicker:0 dtpicker:0 timepicker:0 dnd:1 sessionstorage:1 localstorage:1 history:1 canvas:1 svg:1 postmessage:1 hc:0');



		$this->request->cookies->addRule('psback=""url":"https://sis.hva.nl:8011/psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SAA_SS_DPR_ADB.GBL?" "label":"My Academic Requirements" "origin":"PIA" "layout":"0""');
		$this->request->params = array(
			'ICAJAX' => '1',
			'ICNAVTYPEDROPDOWN' => '1',
			'ICType' => 'Panel',
			'ICElementNum' => '0',
			'ICEStateNum' => '8',
			'ICAction' => 'DERIVED_SAA_DPR_SSS_EXPAND_ALL',
			'ICXPos' => '0',
			'ICYPos' => '0',
			'ResponsetoDiffFrame' => '-1',
			'TargetFrameName' => 'None',
			'FacetPath' => 'None',
			'ICFocus' => '',
			'ICSaveWarningFilter' => '0',
			'ICChanged' => '-1',
			'ICAutoSave' => '0',
			'ICResubmit' => '0',
			'ICSID' => 'k+KEkzZje/MQYTaK1uhqpjnKB6/Zv3eVNx33H6BlJWU=',
			'ICActionPrompt' => 'false',
			'ICBcDomData' => '"undefined"',
			'ICFind' => '',
			'ICAddCount' => '',
			'ICAPPCLSDATA' => '',
			'#ICDataLang' => 'ENG',
			'DERIVED_SSTSNAV_SSTS_MAIN_GOTO$7$' => '0100',
			'DERIVED_SSTSNAV_SSTS_MAIN_GOTO$8$' => '0100',
			'ptus_defaultlocalnode' => 'PSFT_HR',
			'ptus_dbname' => 'S2PRD',
			'ptus_portal' => 'EMPLOYEE',
			'ptus_node' => 'HRMS',
			'ptus_workcenterid' => '',
			'ptus_componenturl' => 'https://sis.hva.nl:8011/psp/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SAA_SS_DPR_ADB.GBL',
		);
		$requirementsData = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SAA_SS_DPR_ADB.GBL', 'post');

		print_r($requirementsData);*/
	}

	/**
	 *	To prevent printing the password we create a temp user and delete the password from that user.
	 */
	public function printUser() {
		$tmpUser = $this->user;
		$tmpUser->setPassword('-');
		echo "<pre>";
		print_r($tmpUser);
		unset($tmpUser);
	}

	private function cleanUpCookieFiles() {
		$files = glob(TMP . "/cookies/*");
		$now   = time();

		foreach ($files as $file)
			if (is_file($file))
				if ($now - filemtime($file) >= 60 * 60 * 24 * 2) // 2 days
					unlink($file);
	}

}