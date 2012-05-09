<?php
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 *	Wraps some of the basic Typolink settings in a viewhelper. Good for rendering links to pages and page titles
 *	when all you have is the page id.
 *
 *
 */
class Tx_Cicbase_ViewHelpers_TypolinkViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {
	/**
	 * @param parameter Same as the parameter config value in Typoscript, can accept a page id, or
	 * @param string target
	 * @param integer noCache
	 * @param integer useCacheHash
	 * @param array $additionalParams
	 */
	public function render($parameter, $target='',$noCache=0,$useCacheHash=1,$additionalParams=array()) {
		$typoLinkConf = array(
			'parameter' => $parameter,
		);

		if($target) {
			$typoLinkConf['target'] = $target;
		}

		if($noCache) {
			$typoLinkConf['no_cache'] = 1;
		}

		if($useCacheHash) {
			$typoLinkConf['useCacheHash'] = 1;
		}

		if(count($additionalParams)) {
			$typoLinkConf['additionalParams'] = t3lib_div::implodeArrayForUrl('',$additionalParams);
		}

		$linkText = $this->renderChildren();

		$textContentConf = array(
			'typolink.' => $typoLinkConf,
			'value' => $linkText
		);

		return $GLOBALS['TSFE']->cObj->cObjGetSingle('TEXT',$textContentConf);
	}
}
?>