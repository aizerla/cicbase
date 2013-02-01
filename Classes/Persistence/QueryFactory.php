<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Zach Davis <zach@castironcoding.com>, Cast Iron Coding
 *  Lucas Thurston <lucas@castironcoding.com>, Cast Iron Coding
 *  Gabe Blair <gabe@castironcoding.com>, Cast Iron Coding
 *  Peter Soots <peter@castironcoding.com>, Cast Iron Coding
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

class Tx_Cicbase_Persistence_QueryFactory extends Tx_Extbase_Persistence_QueryFactory {

	/**
	 * Creates a query object working on the given class name
	 *
	 * Adds the ability to set storagePids for any domain object using typoscript:
	 * config.extBase.persistence.classes.CLASSNAME.storagePid
	 *
	 * Not implemented here, but you should also set the newRecordStoragePid too:
	 * config.extBase.persistence.classes.CLASSNAME.newRecordStoragePid
	 *
	 * @param string $className The class name
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function create($className) {
		$query = $this->objectManager->create('Tx_Extbase_Persistence_QueryInterface', $className);
		$querySettings = $this->objectManager->create('Tx_Extbase_Persistence_QuerySettingsInterface');
		$frameworkConfiguration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$classes = $frameworkConfiguration['persistence']['classes'];
		if(isset($classes[$className]) && !empty($classes[$className]['storagePid'])) {
			$storagePids = t3lib_div::intExplode(',', $classes[$className]['storagePid']);
		} else {
			$storagePids = t3lib_div::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
		}
		$querySettings->setStoragePageIds($storagePids);
		$query->setQuerySettings($querySettings);

		return $query;
	}
}

?>