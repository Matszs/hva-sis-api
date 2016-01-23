<?php
namespace Models;

class Grade {
    private $courseName;
    private $grade;
	private $date;

    public function __construct($courseName, $grade, $date)
    {
        $this->courseName = $courseName;
        $this->grade = $grade;
		$this->date = $date;
    }

	/**
	 * @return mixed
	 */
	public function getCourseName()
	{
		return $this->courseName;
	}

	/**
	 * @param mixed $courseName
	 */
	public function setCourseName($courseName)
	{
		$this->courseName = $courseName;
	}

	/**
	 * @return mixed
	 */
	public function getGrade()
	{
		return $this->grade;
	}

	/**
	 * @param mixed $grade
	 */
	public function setGrade($grade)
	{
		$this->grade = $grade;
	}

	/**
	 * @return mixed
	 */
	public function getDate()
	{
		return $this->date;
	}

	/**
	 * @param mixed $date
	 */
	public function setDate($date)
	{
		$this->date = $date;
	}



}