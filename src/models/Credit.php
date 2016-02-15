<?php
namespace Models;

class Credit
{
	private $description;
	private $units;
	private $date;
	private $grade;

	public function __construct($courseName, $grade, $date)
	{
		$this->courseName = $courseName;
		$this->grade = $grade;
		$this->date = $date;
	}

}