<?php
namespace Models;

class User {
    private $username;
    private $password;

	private $studentNumber;
	private $firstname;
	private $name;
	private $phone;
	private $email;
	private $advisor;

	public $grades;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function getUsername() {
        return $this->username;
    }

	/**
	 * @param mixed $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}


	/**
	 * @return mixed
	 */
	public function getStudentNumber()
	{
		return $this->studentNumber;
	}

	/**
	 * @param mixed $studentNumber
	 */
	public function setStudentNumber($studentNumber)
	{
		$this->studentNumber = $studentNumber;
	}

	/**
	 * @return mixed
	 */
	public function getFirstname()
	{
		return $this->firstname;
	}

	/**
	 * @param mixed $firstname
	 */
	public function setFirstname($firstname)
	{
		$this->firstname = $firstname;
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
	public function getPhone()
	{
		return $this->phone;
	}

	/**
	 * @param mixed $phone
	 */
	public function setPhone($phone)
	{
		$this->phone = $phone;
	}

	/**
	 * @return mixed
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param mixed $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @return mixed
	 */
	public function getAdvisor()
	{
		return $this->advisor;
	}

	/**
	 * @param mixed $advisor
	 */
	public function setAdvisor($advisor)
	{
		$this->advisor = $advisor;
	}
}