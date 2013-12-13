<?php

namespace AppZap\Migrator\Command;

use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class MigrationCommandController extends CommandController {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var array
	 */
	protected $extensionConfiguration;

	/**
	 * @var \TYPO3\CMS\Core\Registry
	 * @inject
	 */
	protected $registry;

	/**
	 *
	 */
	protected function initialize() {
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
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
		$lastExecutedVersion = intval($this->registry->get('AppZap\\Migrator', 'lastExecutedVersion'));
		foreach ($iterator as $fileinfo) {
			/** @var $fileinfo \DirectoryIterator */
			if ($fileinfo->getExtension() === 'sql') {
				$fileVersion = intval($fileinfo->getBasename('.sql'));
				if ($fileVersion > $lastExecutedVersion) {
					$sql = file_get_contents($fileinfo->getPath());
					$this->databaseConnection->sql_query($sql);
					$highestExecutedVersion = max($highestExecutedVersion, $fileVersion);
				}
			}
		}
		$this->registry->set('AppZap\\Migrator', 'lastExecutedVersion', $lastExecutedVersion);
	}

}