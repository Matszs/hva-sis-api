<?php

require __DIR__.'/src/bootstrap.php';

$sisApi = new SisApi();
try {
	$sisApi->verifyUserAuthentication('USERNAME', 'PASSWORD');
	$sisApi->getDetailedUserData();
	$sisApi->getUserGrades();

	$sisApi->printUser();

} catch(\Exceptions\IncorrectUserDetails $e) {
	echo $e->getMessage();
}
