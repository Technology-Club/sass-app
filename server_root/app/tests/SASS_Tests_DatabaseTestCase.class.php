<?php

/**
 * Created by PhpStorm.
 * User: rdok
 * Date: 10/10/2014
 * Time: 7:16 AM
 */
abstract class SASS_Tests_DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{

	// only instantiate pdo once for test clean-up/fixture load
	static private $pdo = null;

	// only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
	private $conn = null;

	final public function getConnection() {
		if ($this->conn === null) {
			if (self::$pdo === null) {
				self::$pdo = new PDO('sqlite::memory:');
			}
			$this->conn = $this->createDefaultDBConnection(self::$pdo, ':memory:');
		}

		return $this->conn;
	}
} 