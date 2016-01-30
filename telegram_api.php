<?php

include("sqlite_database.php");

class Crypt {
	public static $iv;

	public static function encrypt($string) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_DEV_URANDOM);
		$key = pack('H*', "87435943758943758934563489756438752657843657834265783426589");

		return json_encode(array('key' => self::base64url_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv)), 'iv' => self::base64url_encode($iv)));
	}

	public static function decrypt($data) {
		$key = pack('H*', "87435943758943758934563489756438752657843657834265783426589");
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

function printJson($success, $message) {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');

	die(json_encode(array('success' => $success, 'message' => $message)));
}

Database::init(array('file' => '../databases/telegram_sis_api.db'));
@Database::query("CREATE TABLE tokens (id INTEGER PRIMARY KEY, username TEXT, password TEXT, telegram_user_id TEXT)"); // create table

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

							$gradesArray = array();
							foreach ($grades as $grade) {
								if (count($gradesArray) > 10)
									break;
								if ($grade->getGrade() == 'no result')
									continue;
								$gradesArray[] = array(
									'courseName' => $grade->getCourseName(),
									'grade' => str_replace('sat.', 'voldoende', $grade->getGrade())
								);
							}

							printJson(true, array('user' => $user->getFirstname() . ' - ' . $user->getStudentNumber(), 'grades' => $gradesArray));
						}
					}
				}
			}
		}

		printJson(false, "User not connected.");
	} catch(Exception $e) {
		printJson(false, "Unknown action.");
	}
} else
	printJson(false, "Unknown action.");