<?php

include("mysql_database.php");

class Crypt {
	public static $iv;

	public static function encrypt($string) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_DEV_URANDOM);
		$key = pack('H*', "87435943758943758934563489756438752657843657834265783426589") ."\0"."\0";


		return json_encode(array('key' => self::base64url_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv)), 'iv' => self::base64url_encode($iv)));
	}

	public static function decrypt($data) {
		$key = pack('H*', "87435943758943758934563489756438752657843657834265783426589")."\0"."\0";
		$data = json_decode($data, true);

		$string = self::base64url_decode($data['key']);
		$iv = self::base64url_decode($data['iv']);

		$decryption = mcrypt_decrypt ( MCRYPT_RIJNDAEL_128 , $key, $string, MCRYPT_MODE_CBC, $iv);

		return rtrim($decryption, "\0");
	}

	public static function base64url_encode($s) {
		return str_replace(array('+', '/'), array('-', '_'), base64_encode($s));
	}

	public static function base64url_decode($s) {
		return base64_decode(str_replace(array('-', '_'), array('+', '/'), $s));
	}
}

function printJson($success, $message = null) {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');

	die(json_encode(array('success' => $success, 'message' => $message)));
}

Database::init(array('host' => 'localhost', 'user' => 'sis', 'password' => '', 'database' => 'sis'));
//@Database::query("CREATE TABLE tokens (id INTEGER PRIMARY KEY, username TEXT, password TEXT, telegram_user_id TEXT)"); // create table
//@Database::query("CREATE TABLE courses (id INTEGER PRIMARY KEY, name TEXT, exam_date TEXT)"); // create table

if(!empty($_GET['action']) && $_GET['action'] == 'connect' && !empty($_GET['telegram_user_id'])) {
	if(isset($_POST['username']) && isset($_POST['password'])) {
		$encryption = Crypt::base64url_encode(Crypt::encrypt(json_encode(array('password' => $_POST['password']))));

		Database::setParam('username', $_POST['username']);
		Database::setParam('telegram_user_id', $_GET['telegram_user_id']);

		Database::query("INSERT INTO tokens (username, password, telegram_user_id) VALUES ('{username}', '" . $encryption . "', '{telegram_user_id}');");
		echo "<div style='background: #fff; text-align: center; height: 20px; margin: 20px; border: 2px solid #e67d21; border-radius: 5px;'>Succesvol gekoppeld. Probeer nu de bot met de volgende command: &nbsp; &nbsp; &nbsp;  /sis</div>";
	}

	include('telegram/login.php');

} else if(!empty($_GET['action']) && $_GET['action'] == 'grades' && !empty($_GET['telegram_user_id'])) {
	try {
		Database::setParam('telegram_user_id', $_GET['telegram_user_id']);
		$userData = Database::query('SELECT * FROM tokens WHERE telegram_user_id = \'{telegram_user_id}\' ORDER BY id DESC LIMIT 1');


		if ($userData && $userData = Database::getArray($userData)) {
			if (isset($userData[0])) {
				if ($userData = $userData[0]) {

					$decryption = Crypt::decrypt(Crypt::base64url_decode($userData['password']));
					$decryption = json_decode($decryption, true);
					if ($decryption) {
						if (isset($decryption['password'])) {
							require __DIR__ . '/src/bootstrap.php';

							$sisApi = new SisApi();
							$sisApi->verifyUserAuthentication($userData['username'], $decryption['password']);
							$sisApi->getDetailedUserData(); // To display the username
							$grades = $sisApi->getUserGrades();
							$user = $sisApi->getUser();
							$sisApi->getRequirements();

							$gradesArray = array();
							foreach ($grades as $grade) {
//								if($grade->getGrade() == "no result")
//									continue;
								if (count($gradesArray) > 10 && (!isset($_GET['list']) || (isset($_GET['list']) && !$_GET['list'])))
									continue;
								$gradesArray[] = array(
									'courseName' => $grade->getCourseName(),
									'grade' => str_replace(array('not sat.', 'pass', 'no result'), array('Onvoldoende', 'voldoende', 'geen resultaat'), $grade->getGrade())
								);
							}

							if(count($grades) > 0) {
								$gradesArray[] = array('courseName' => '--------', 'grade' => '');
								$gradesArray[] = array('courseName' => 'Gemiddelde', 'grade' => $user->getAverageGrade());
								if($user->getFailCount() > 0)
								    $gradesArray[] = array('courseName' => 'Onvoldoendes', 'grade' => $user->getFailCount());
							}
							if($user->requirementReport->getScore() > 0) {
								$gradesArray[] = array('courseName' => 'Studiepunten', 'grade' => $user->requirementReport->getScore() . '/240 (' . number_format(floatval($user->requirementReport->getScore())/240*100, 0, ',', '.') . '%)');
							}

							printJson(true, array('user' => $user->getFirstname() . ' - ' . $user->getStudentNumber(), 'grades' => $gradesArray));
						}
					}
				}
			}
		}

		printJson(false, "User not connected.");
	} catch(Exception $e) {
		printJson(false, "Error connecting to SIS.");
	}
} else if(!empty($_GET['action']) && $_GET['action'] == 'cron') {

	$telegramUserIds = array(176808727, 172749143);

	foreach($telegramUserIds as $telegramUserId) {

		Database::setParam('telegram_user_id', $telegramUserId);
		$userData = Database::query('SELECT * FROM tokens WHERE telegram_user_id = \'{telegram_user_id}\' ORDER BY id DESC LIMIT 1');


		if ($userData && $userData = Database::getArray($userData)) {

			if (isset($userData[0])) {
				if ($userData = $userData[0]) {
					$decryption = Crypt::decrypt(Crypt::base64url_decode($userData['password']));
					$decryption = json_decode($decryption, true);

					if ($decryption) {
						if (isset($decryption['password'])) {
							require __DIR__ . '/src/bootstrap.php';

							$sisApi = new SisApi();
							$sisApi->verifyUserAuthentication($userData['username'], $decryption['password']);
							$grades = $sisApi->getUserGrades();

							$gradesArray = array();
							foreach ($grades as $grade) {
								if (count($gradesArray) > 10)
									break;
								if ($grade->getGrade() == 'no result')
									continue;

								Database::setParam('courseName', $grade->getCourseName());
								Database::setParam('courseDate', $grade->getDate());
								$courseData = Database::query('SELECT count(*) as count FROM courses WHERE name = \'{courseName}\' AND exam_date = \'{courseDate}\' LIMIT 1');
								if ($courseData && $courseData = Database::getArray($courseData)) {
									if (isset($courseData[0]) && isset($courseData[0]['count']) && $courseData[0]['count'] == 0) {

										echo $grade->getCourseName();

										Database::setParam('courseName', $grade->getCourseName());
										Database::setParam('courseDate', $grade->getDate());

										Database::query("INSERT INTO courses (name, exam_date) VALUES ('{courseName}', '{courseDate}');");

										$request = new Handlers\Rest(array(
											'root' => 'https://chinchilla.plebtier.com/',
											'user_agent' => 'SIS-api',
											'cookies' => false
										));
										$request->params = array('course' => $grade->getCourseName());
										$request->call('chinchilla/sis-notifier.php', 'post');

										$request->params = array('course' => $grade->getCourseName());
										$request->call('qtkoreanbot/sis-notifier.php', 'post');

										$request = new Handlers\Rest(array(
											'root' => 'https://api.pushbullet.com/v2/',
											'user_agent' => 'SIS-api',
											'cookies' => false,
											'headers' => array(
												'Authorization: Bearer'
											)
										));
										$request->params = array('type' => 'note', 'title' => 'Nieuw cijfer: ' . $grade->getCourseName(), 'body' => 'Nieuw cijfer ' . $userData['username'] . ' voor het vak ' . $grade->getCourseName() . ', cijfer: ' . $grade->getGrade());
										$request->call('pushes', 'post');
									}
								}
							}
						}
					}
				}
			}
		}
	}

	printJson(true);

} else
	printJson(false, "Unknown action.");