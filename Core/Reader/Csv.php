<?php

namespace MillenniumFalcon\Core\Reader;

class Csv {

	private $reader;

	public function __construct($filepath) {

		$this->file = fopen ( $filepath, "r" );
	
	}

	public function getNextRow() {

		if ($this->file !== FALSE) {
			return fgetcsv ( $this->file, 999999, "," );
		}
		return FALSE;
	
	}

	public function getAllRows() {

		$rows = array ();
		while ( ($row = $this->getNextRow()) !== FALSE ) {
			$rows [] = $row;
		}
		fclose ( $this->file );
		return $rows;
	
	}

}