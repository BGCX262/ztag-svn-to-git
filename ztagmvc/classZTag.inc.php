<?php
abstract class zTag {
  /**
	 * @global array Have the last processed zTag value
	 */
	$ztagLastValue = array();
	
	/**
	 * @global int Have the last processed zTag Id.
	 */
	$ztagLastId    = null;
	$arrayTagSys   = array();
	
	$ztagError = 0;
	$ztagLastTag      = "";
	
	/**
	 * @global array Last processed zTag Function.
	 */
	$ztagLastFunction = "";
	
  public function __construct() {
  }

  public function __destruct() {
  }  
}

abstract class ztagControler {
	
}
?>