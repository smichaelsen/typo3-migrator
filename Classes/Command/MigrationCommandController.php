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
	 * @var string
	 */
	protected $mySqlBinary = '/usr/local/mysql/bin/mysql';

	/**
	 * @var string
	 */
	protected $shellCommandTemplate = '%s --default-character-set=UTF8 -u"%s" -p"%s" -h "%s" -D "%s" < "%s" 2>&1';

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
		$errors = array();
		foreach ($iterator as $fileinfo) {
			/** @var $fileinfo \DirectoryIterator */
			if ($fileinfo->getExtension() === 'sql') {
				$fileVersion = intval($fileinfo->getBasename('.sql'));
				if ($fileVersion > $lastExecutedVersion) {
					$filePath = $fileinfo->getPathname();
					$shellCommand = sprintf(
						$this->shellCommandTemplate,
						$this->mySqlBinary,
						$GLOBALS['TYPO3_CONF_VARS']['DB']['username'],
						$GLOBALS['TYPO3_CONF_VARS']['DB']['password'],
						$GLOBALS['TYPO3_CONF_VARS']['DB']['host'],
						$GLOBALS['TYPO3_CONF_VARS']['DB']['database'],
						$filePath
					);
					$output = shell_exec($shellCommand);
					$ouputMessages = explode("\n", $output);
					foreach ($ouputMessages as $ouputMessage) {
						if (trim($ouputMessage) && strpos($ouputMessage, 'Warning') === FALSE) {
							if (!is_array($errors[$fileVersion])) {
								$errors[$fileVersion] = array();
							}
							$errors[$fileVersion][] = $ouputMessage;
						}
					}
					$highestExecutedVersion = max($highestExecutedVersion, $fileVersion);
				}
			}
		}
		$this->registry->set('AppZap\\Migrator', 'lastExecutedVersion', max($lastExecutedVersion, $highestExecutedVersion));
	}

}