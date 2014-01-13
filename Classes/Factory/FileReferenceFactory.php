<?php
namespace CIC\Cicbase\Factory;
use CIC\Cicbase\Domain\Model\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/***************************************************************
 *  Copyright notice
 *  (c) 2012 Peter Soots <peter@castironcoding.com>, Cast Iron Coding
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class FileReferenceFactory implements \TYPO3\CMS\Core\SingletonInterface {


	/** @var string  */
	protected $storagePath = 'cicbase/uploads/';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * A list of property mapping messages (errors, warnings) which have occurred on last mapping.
	 *
	 * @var \TYPO3\CMS\Extbase\Validation\Error
	 */
	protected $messages;

	/**
	 * @var string
	 */
	protected $propertyPath = '';

	/**
	 * @var \CIC\Cicbase\Persistence\Limbo
	 * @inject
	 */
	protected $limbo;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 * @inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 * @inject
	 */
	protected $fileFactory;

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileRepository
	 * @inject
	 */
	protected $fileRepository;

	/**
	 * @var \TYPO3\CMS\Core\Resource\StorageRepository
	 * @inject
	 */
	protected $folderRepository;

	/**
	 * @var \TYPO3\CMS\Core\Utility\File\BasicFileUtility
	 * @inject
	 */
	protected $fileUtility;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Inject the configuration manager
	 *
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
	}


	/**
	 * @param string $propertyPath
	 * @param array $additionalReferenceProperties
	 * @param array $allowedTypes
	 * @param int $maxSize
	 * @return \TYPO3\CMS\Extbase\Error\Error
	 */
	public function createFileReference($propertyPath, $additionalReferenceProperties, $allowedTypes, $maxSize) {
		$this->messages = new \TYPO3\CMS\Extbase\Error\Result();
		$key = $propertyPath ? $propertyPath : '';

		$uploadedFileData = $this->getUploadedFileData($propertyPath);
		$this->handleUploadErrors($uploadedFileData);

		if($this->messages->hasErrors()) {
			$this->fileRepository->clearHeld($key);
			return $this->messages->getFirstError();
		} else {
//			if(!$this->settings['file']['dontValidateType']) {
//				$this->validateType($uploadedFileData,$allowedTypes);
//			}
//			if(!$this->settings['file']['dontValidateSize']) {
//				$this->validateSize($uploadedFileData,$maxSize);
//			}
		}

		if($this->messages->hasErrors()) {
			$this->fileRepository->clearHeld($key);
			return $this->messages->getFirstError();
		} else {
			$fileReference = $this->buildFileReference($propertyPath, $additionalReferenceProperties);

			$this->limbo->hold($fileReference, $key);

			return $fileReference;
		}
	}


	/**
	 * Checks for errors in $_FILES array
	 *
	 * @param $propertyPath
	 * @return bool
	 */
	public function wasUploadAttempted($propertyPath) {
		$data = $this->getUploadedFileData($propertyPath);
		return $data['error'] != 4 && ($data['error'] || $data['size'] || $data['tmp_name']);
	}


	/**
	 * @param string $propertyPath
	 * @param null $additionalReferenceProperties
	 * @return \CIC\Cicbase\Domain\Model\FileReference
	 */
	protected function buildFileReference($propertyPath, $additionalReferenceProperties = NULL) {
		$pathExists = $this->temporaryPathExists();
		$relFolderPath = $this->buildTemporaryPath(FALSE);

		$folder = $this->createFolderObject($relFolderPath, $pathExists);

		// Folder data from $_FILES
		$source = $this->getUploadedFileData($propertyPath);

		// Use the file hash as the name of the file
		$source['name'] = md5_file($source['tmp_name']) . '.' . pathinfo($source['name'], PATHINFO_EXTENSION);

		// Create the FileObject by adding the uploaded file to the FolderObject.
		$file = $folder->addUploadedFile($source, 'replace');

		// Default properties for our reference object from our File object.
		$referenceProperties = array(
			'uid_local' => $file->getUid(),
			'table_local' => 'sys_file'
		);

		// Allow for additional reference properties to be added
		if (is_array($additionalReferenceProperties)) {
			$referenceProperties = array_merge($referenceProperties, $additionalReferenceProperties);
		}

		// Build a FileReference object using our reference properties
		$ref = $this->fileFactory->createFileReferenceObject($referenceProperties);

		// Convert the Core FileReference we made to an ExtBase FileReference
		$fileReference = $this->objectManager->getEmptyObject('CIC\Cicbase\Domain\Model\FileReference');
		$fileReference->setOriginalResource($ref);
		return $fileReference;

	}

	/**
	 * @param FileReference $fileReference
	 * @param string $key
	 */
	public function save(FileReference $fileReference, $key = '') {
		$pathExists = $this->permanentPathExists();
		$relFolderPath = $this->buildPermanentPath(FALSE);

		$folder = $this->createFolderObject($relFolderPath, $pathExists);

		$ref = $fileReference->getOriginalResource();
		$file = $ref->getOriginalFile();

		$file->moveTo($folder, $file->getName(), 'replace');

		$this->limbo->clearHeld($key);
	}

	/**
	 * @param bool $absolute
	 * @param bool $mkdir
	 * @return string
	 */
	public function buildTemporaryPath($absolute = FALSE, $mkdir = TRUE) {
		$path = '_temp_/'. $this->storagePath;
		$fileadminPath = PATH_site.'fileadmin/'.$path;
		$absPath = GeneralUtility::getFileAbsFileName($fileadminPath);
		if($mkdir) {
			if(!is_dir($absPath)) {
				GeneralUtility::mkdir_deep($fileadminPath);
			}
		}
		return $absolute ? $absPath : $path;
	}


	/**
	 * @param bool $absolute
	 * @param bool $mkdir
	 * @return string
	 */
	public function buildPermanentPath($absolute = FALSE, $mkdir = TRUE) {
		$path = $this->storagePath.date('Y').'/'.date('n').'/'.date('j');
		$fileadminPath = PATH_site.'fileadmin/'.$path;
		$absPath = GeneralUtility::getFileAbsFileName($fileadminPath);
		if($mkdir) {
			if(!is_dir($absPath)) {
				GeneralUtility::mkdir_deep($fileadminPath);
			}
		}
		return $absolute ? $absPath : $path;
	}


	/**
	 * @param $relFolderPath
	 * @param $pathExists
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	protected function createFolderObject($relFolderPath, $pathExists) {
		if(!$pathExists) {
			$storage = $this->folderRepository->findByUid(1);
			return $this->fileFactory->createFolderObject($storage, $relFolderPath, 'upload_folder');
		} else {
			return $this->fileFactory->getFolderObjectFromCombinedIdentifier("1:$relFolderPath");
		}
	}


	/**
	 * @return bool
	 */
	protected function temporaryPathExists() {
		$path = dir($this->buildTemporaryPath(TRUE, FALSE));
		return is_dir($path);
	}


	/**
	 * @return bool
	 */
	protected function permanentPathExists() {
		$path = dir($this->buildPermanentPath(TRUE, FALSE));
		return is_dir($path);
	}


	/**
	 * Returns the current plugin namespace
	 * @return string
	 */
	protected function getNamespace() {
		$framework = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$extension = $framework['extensionName'];
		$plugin = $framework['pluginName'];
		$namespace = 'tx_' . strtolower($extension) . '_' . strtolower($plugin);
		return $namespace;
	}


	/**
	 * @param string $propertyPath
	 * @return array
	 */
	protected function getUploadedFileData($propertyPath = '') {
		$fileData = array();
		$fd = $_FILES[$this->getNamespace()];

		$fileData['error'] = $this->valueByPath($fd['error'], $propertyPath);
		$fileData['type'] = $this->valueByPath($fd['type'], $propertyPath);
		$fileData['name'] = $this->valueByPath($fd['name'], $propertyPath);
		$fileData['size'] = $this->valueByPath($fd['size'], $propertyPath);
		$fileData['tmp_name'] = $this->valueByPath($fd['tmp_name'], $propertyPath);
		return $fileData;
	}


	/**
	 * Access array variables using the dot path notation
	 * i.e. document.image.file
	 *
	 * @param $subject
	 * @param string $path
	 * @return mixed
	 */
	protected function valueByPath($subject, $path = '') {
		$parts = explode('.', $path);
		return $this->_valueByPath($subject, $parts);
	}

	/**
	 * Recursive grunt for valueByPath
	 *
	 * @param $subject
	 * @param array $parts
	 * @return mixed
	 */
	protected function _valueByPath($subject, $parts = array()) {
		if(count($parts) == 0) {
			return $subject;
		}
		return $this->_valueByPath($subject[$parts[0]], array_slice($parts, 1));
	}


	/**
	 * Using error code from $_FILES array, creates the appropriate error message
	 *
	 * @param $uploadedFileData
	 * @return null
	 */
	protected function handleUploadErrors($uploadedFileData) {
		if($uploadedFileData['error']) {
			switch ($uploadedFileData['error']) {
				case 1:
				case 2:
					$this->addError('File exceeds upload size limit', 1336597081);
				break;
				case 3:
					$this->addError('File was only partially uploaded. Please try again', 1336597082);
				break;
				case 4:
					$this->addError('No file was uploaded.', 1336597083);
				break;
				case 5:
				case 6:
				case 7:
					$this->addError('Bad destination error.', 1336597084);
				break;
				default:
					$this->addError('Unknown error.', 1336597085);
				break;
			}
		} else {
			return NULL;
		}
	}


	/**
	 * @param $uploadedFileData
	 * @param $allowedTypes
	 * @return null
	 */
	protected function validateType($uploadedFileData,$allowedTypes) {
		$pathInfo = pathinfo($uploadedFileData['name']);
		$extension = $pathInfo['extension'];
		$allowedMimes = t3lib_div::trimExplode(',',$allowedTypes[$extension]);
		if(in_array($uploadedFileData['type'],$allowedMimes)) {
			return NULL;
		} else {
			$this->addError('Invalid mime type: '.$uploadedFileData['type'], 1336597086);
		}
	}


	/**
	 * @param $uploadedFileData
	 * @param $maxSize
	 * @return null
	 */
	protected function validateSize($uploadedFileData,$maxSize) {
		if($uploadedFileData['size'] > $maxSize) {
			$this->addError('Uploaded file size ('.$uploadedFileData['size'].') exceeds max allowed size', 1336597087);
		} else {
			return NULL;
		}
	}


	/**
	 * Creates a new error message and
	 * adds to to our ongoing list of errors
	 *
	 * @param $msg
	 * @param $key
	 */
	protected function addError($msg, $key) {
		$error = new \TYPO3\CMS\Extbase\Validation\Error($msg, $key);
		$this->messages->addError($error);
	}


	/**
	 * Grabs string values from the locallang.xml file.
	 *
	 * @static
	 * @param string $string The name of the key in the locallang.xml file.
	 * @return string The value of that key
	 */
	protected static function translate($string) {
		return htmlspecialchars(Tx_Extbase_Utility_Localization::translate('tx_sjcert_domain_model_municipalityclaim.' . $string, 'sjcert'));
	}

}

?>