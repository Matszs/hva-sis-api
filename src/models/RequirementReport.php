<?php
namespace Models;

class RequirementReport {
	private $date;
	private $score;
	public $requirements = array();


	/**
	 * @return mixed
	 */
	public function getScore() {
		return $this->score;
	}

	/**
	 * @param mixed $score
	 */
	public function setScore($score)
	{
		$this->score = $score;
	}

	public function getDate()
	{
		return $this->date;
	}

	public function setDate($date)
	{
		$this->date = $date;
	}


}