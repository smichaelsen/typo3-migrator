<?php

namespace AppZap\Migrator\Command;

use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class MigrationCommandController extends CommandController {

	/**
	 * @var \TYPO3\CMS\Core\Registry
	 * @inject
	 */
	protected $registry;

	/**
	 * @var array
	 */
	protected $extensionConfiguration;

	/**
	 *
	 */
	protected function initialize() {
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['migrator']);
	}

	/**
	 *
	 */
	public function migrateSqlFilesCommand() {
		$this->initialize();
		$sqlFolderPath = realpath(PATH_site . $this->extensionConfiguration['sqlFolderPath']);
		$iterator = new \DirectoryIterator($sqlFolderPath);
		$highestExecutedVersion = NULL;
		foreach ($iterator as $fileinfo) {
			/** @var $fileinfo \DirectoryIterator */
			if ($fileinfo->getExtension() === 'sql') {
				$fileVersion = intval($fileinfo->getBasename('.sql'));
				$this->output($fileVersion);
			}
		}
	}

}