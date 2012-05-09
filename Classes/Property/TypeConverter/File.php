<?php

class Tx_Cicbase_Property_TypeConverter_File extends Tx_Extbase_Property_TypeConverter_PersistentObjectConverter {

	/**
	 * The source types this converter can convert.
	 *
	 * @var array<string>
	 * @api
	 */
	protected $sourceTypes = array('string');

	/**
	 * The target type this converter can convert to.
	 *
	 * @var string
	 * @api
	 */
	protected $targetType = 'Tx_Cicbase_Domain_Model_File';

	/**
	 * The priority for this converter.
	 *
	 * @var integer
	 * @api
	 */
	protected $priority = 2;

	/**
	 * @var Tx_Cicbase_Factory_FileFactory
	 */
	protected $fileFactory;

	/**
	 * inject the documentFactory
	 *
	 * @param Tx_Cicbase_Factory_FileFactory documentFactory
	 * @return void
	 */
	public function injectFileFactory(Tx_Cicbase_Factory_FileFactory $documentFactory) {
		$this->fileFactory = $documentFactory;
	}

	/**
	 * This implementation always returns TRUE for this method.
	 *
	 * @param mixed $source the source data
	 * @param string $targetType the type to convert to.
	 * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
	 * @api
	 */
	public function canConvertFrom($source, $targetType) {
		return TRUE;
	}

	/**
	 * Return the target type this TypeConverter converts to.
	 * Can be a simple type or a class name.
	 *
	 * @return string
	 * @api
	 */
	public function getSupportedTargetType() {
		return $this->targetType;
	}

	/**
	 * Actually convert from $source to $targetType, taking into account the fully
	 * built $convertedChildProperties and $configuration.
	 * The return value can be one of three types:
	 * - an arbitrary object, or a simple type (which has been created while mapping).
	 *   This is the normal case.
	 * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
	 * - An instance of Tx_Extbase_Error_Error -- This will be a user-visible error message lateron.
	 * Furthermore, it should throw an Exception if an unexpected failure occured or a configuration issue happened.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration
	 * @return mixed the target type
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration = NULL) {
		$propertyPath = $configuration->getConfigurationValue('Tx_Cicbase_Property_TypeConverter_File', 'propertyPath');
		if($source['__identity'] && !$this->fileFactory->wasUploadAttempted($propertyPath)) {
			// We have an identity, and no upload was attempted, so we restore the previous file record.
			$source = $source['__identity'];
			$out = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);
			if($out->getOwner() == $GLOBALS['TSFE']->fe_user->user['uid']) {
				Tx_Extbase_Utility_Debugger::var_dump('AAA', __FILE__ . " " . __LINE___);
				return $out;
			} else {
				Tx_Extbase_Utility_Debugger::var_dump('BBB',__FILE__ . " " . __LINE___);
				return NULL;
			}
		} else {
			// Otherwise, we create a new file object.
			$allowedTypes = $configuration->getConfigurationValue('Tx_Cicbase_Property_TypeConverter_File', 'allowedTypes');
			$maxSize = $configuration->getConfigurationValue('Tx_Cicbase_Property_TypeConverter_File', 'maxSize');
			$result = $this->fileFactory->createFile($source, $propertyPath, $allowedTypes, $maxSize);
			return $result;
		}
	}

}

?>