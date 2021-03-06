<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Zach Davis <zach@castironcoding.com>, Cast Iron Coding, Inc
*  			Lucas Thurston <lucas@castironcoding.com>, Cast Iron Coding, Inc
*  			
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Address Model
 *
 * @version $Id$
 * @copyright Copyright belngs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */

class Tx_Cicbase_Domain_Model_Zip extends Tx_Extbase_DomainObject_AbstractEntity {
	
/**
* zip
* @var string
*/
protected $zip; 

/**
* lat
* @var string
*/
protected $lat; 

/**
* lng
* @var string
*/
protected $lng;

/**
* Setter for zip
* @param string $zip the zip code
* @return void
*/
public function setZip($zip) {
	$this->zip = $zip;
}

/**
* Getter for zip
* @return string
*/
public function getZip() {
	return $this->zip; 
}

/**
* Getter for lat
* @return string
*/
public function getLat() {
	return $this->lat; 
}

/**
* Getter for lng
* @return string
*/
public function getLng() {
	return $this->lng; 
}



	
	
}

?>