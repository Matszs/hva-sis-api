<?php
namespace Models;

class Requirement {
	private $course;
	private $name;
	private $score;
	private $date;
	private $grade;
	private $isRequired;
	private $isTaken;

	public function __construct($course, $name, $score, $date, $grade, $isRequired, $isTaken) {
		$this->course = $course;
		$this->name = $name;
		$this->score = $score;
		$this->date = $date;
		$this->grade = $grade;
		$this->isRequired = $isRequired;
		$this->isTaken = $isTaken;
	}

	/**
	 * @return mixed
	 */
	public function getCourse()
	{
		return $this->course;
	}

	/**
	 * @param mixed $course
	 */
	public function setCourse($course)
	{
		$this->course = $course;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getScore()
	{
		return $this->score;
	}

	/**
	 * @param mixed $score
	 */
	public function setScore($score)
	{
		$this->score = $score;
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
	public function getIsRequired()
	{
		return $this->isRequired;
	}

	/**
	 * @param mixed $isRequired
	 */
	public function setIsRequired($isRequired)
	{
		$this->isRequired = $isRequired;
	}

	/**
	 * @return mixed
	 */
	public function getIsTaken()
	{
		return $this->isTaken;
	}

	/**
	 * @param mixed $isTaken
	 */
	public function setIsTaken($isTaken)
	{
		$this->isTaken = $isTaken;
	}

}