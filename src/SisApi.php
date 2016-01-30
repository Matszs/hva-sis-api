<?php

class SisApi {
	private $user = null;
	private $request;

	/**
	 * SisApi constructor.
	 */
	public function __construct() {
		$this->request = new Handlers\Rest(array(
			'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0',
		));
	}

	public function verifyUserAuthentication($username, $password) {
		$username = strtolower($username);
		$this->cleanUpCookieFiles();

		$this->request->params = array(
			'timezoneOffset' => 0,
			'userid' => $username,
			'pwd' => urlencode($password)
		);

		$loginData = $this->request->call('psp/S2PRD/EMPLOYEE/HRMS/?cmd=login&languageCd=ENG');

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

		$userData = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SSS_STUDENT_CENTER.GBL?PORTALPARAM_PTCNAV=HC_SSS_STUDENT_CENTER&EOPP.SCNode=HRMS&EOPP.SCPortal=EMPLOYEE&EOPP.SCName=CO_EMPLOYEE_SELF_SERVICE&EOPP.SCLabel=Self%20Service&EOPP.SCPTfname=CO_EMPLOYEE_SELF_SERVICE&FolderPath=PORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.HC_SSS_STUDENT_CENTER&IsFolder=false&PORTALPARAM_PTCNAV=HC_SSS_STUDENT_CENTER&EOPP.SCNode=HRMS&EOPP.SCPortal=EMPLOYEE&EOPP.SCName=CO_EMPLOYEE_SELF_SERVICE&EOPP.SCLabel=Self%20Service&EOPP.SCPTfname=CO_EMPLOYEE_SELF_SERVICE&FolderPath=PORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.HC_SSS_STUDENT_CENTER&IsFolder=false&PortalActualURL=https%3a%2f%2fsis.hva.nl%3a8011%2fpsc%2fS2PRD%2fEMPLOYEE%2fHRMS%2fc%2fSA_LEARNER_SERVICES.SSS_STUDENT_CENTER.GBL%3fPORTALPARAM_PTCNAV%3dHC_SSS_STUDENT_CENTER%26EOPP.SCNode%3dHRMS%26EOPP.SCPortal%3dEMPLOYEE%26EOPP.SCName%3dCO_EMPLOYEE_SELF_SERVICE%26EOPP.SCLabel%3dSelf%2520Service%26EOPP.SCPTfname%3dCO_EMPLOYEE_SELF_SERVICE%26FolderPath%3dPORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.HC_SSS_STUDENT_CENTER%26IsFolder%3dfalse&PortalContentURL=https%3a%2f%2fsis.hva.nl%3a8011%2fpsc%2fS2PRD%2fEMPLOYEE%2fHRMS%2fc%2fSA_LEARNER_SERVICES.SSS_STUDENT_CENTER.GBL&PortalContentProvider=HRMS&PortalCRefLabel=Student%20Center&PortalRegistryName=EMPLOYEE&PortalServletURI=https%3a%2f%2fsis.hva.nl%3a8011%2fpsp%2fS2PRD%2f&PortalURI=https%3a%2f%2fsis.hva.nl%3a8011%2fpsc%2fS2PRD%2f&PortalHostNode=HRMS&NoCrumbs=yes&PortalKeyStruct=yes', 'get');

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

		$userGrades = $this->request->call('psc/S2PRD/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SSS_MY_CRSEHIST.GBL?Page=SSS_MY_CRSEHIST&Action=U', 'get');

		$tableSplit = explode('id=\'CRSE_HIST$scroll$0\'', $userGrades);
		if(isset($tableSplit[1])) {
			$gradesData = explode('<tr id=\'trCRSE_HIST$0_row', $tableSplit[1]);
			if($gradesData && count($gradesData) > 1) {
				unset($gradesData[0]);

				foreach($gradesData as $gradeRow) {
					$grade = array('courseName' => '', 'grade' => '', 'date' => '');

					if(preg_match('/class=\'PSHYPERLINK\' >(.*?)<\/a>/', $gradeRow, $courseNameMatches)) {
						$grade['courseName'] = $courseNameMatches[1];
					}
					if(preg_match('/id=\'SNS_CRSE_GRADE\$([0-9]{0,10})\'>(.*?)<\/span>/', $gradeRow, $gradeMatches)) {
						$grade['grade'] = $gradeMatches[2];
					}
					if(preg_match('/ id=\'EFFDT\$([0-9]{0,10})\'>(.*?)<\/span>/', $gradeRow, $dateMatches)) {
						$grade['date'] = $dateMatches[2];
					}

					$this->user->grades[] = new \Models\Grade($grade['courseName'], $grade['grade'], date('d-m-Y', strtotime($grade['date'])));
				}

				return $this->user->grades;

			} else {
				throw new \Exceptions\GradesError("Splitsing on grade tr failed");
			}
		} else {
			throw new \Exceptions\GradesError("Table of grades not found");
		}
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