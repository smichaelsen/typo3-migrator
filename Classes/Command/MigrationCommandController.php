<?php

namespace AppZap\Migrator\Command;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
	 * @var int
	 */
	protected $lastExecutedVersion;

	/**
	 * @var string
	 */
	protected $shellCommandTemplate = '%s --default-character-set=UTF8 -u"%s" -p"%s" -h "%s" -D "%s" -e "source %s" 2>&1';

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
		$this->lastExecutedVersion = intval($this->registry->get('AppZap\\Migrator', 'lastExecutedVersion'));
	}

	/**
	 *
	 */
	public function migrateSqlFilesCommand() {
		$this->initialize();
		$sqlFolderPath = realpath(PATH_site . $this->extensionConfiguration['sqlFolderPath']);
		if (!$sqlFolderPath) {
			$message = 'SQL folder not found. Please make sure "' . $this->extensionConfiguration['sqlFolderPath'] . '" (relative to your web root) exists!';
			$this->flashMessage($message, 'Migration Command', FlashMessage::ERROR);
		} else {
			$iterator = new \DirectoryIterator($sqlFolderPath);
			$highestExecutedVersion = NULL;
			$errors = array();
			$executedFiles = 0;
			foreach ($iterator as $fileinfo) {
				/** @var $fileinfo \DirectoryIterator */
				if ($fileinfo->getExtension() === 'sql') {
					$executedVersion = $this->migrateSqlFile($fileinfo, $errors);
					if ($executedVersion) {
						$executedFiles++;
						$highestExecutedVersion = max($highestExecutedVersion, $executedVersion);
					}
				}
			}
			$this->enqueueFlashMessages($executedFiles, $errors);
			$this->registry->set('AppZap\\Migrator', 'lastExecutedVersion', max($this->lastExecutedVersion, $highestExecutedVersion));
		}
	}

	/**
	 * @param \DirectoryIterator $fileinfo
	 * @param array $errors
	 */
	protected function migrateSqlFile($fileinfo, &$errors) {
		$fileVersion = intval($fileinfo->getBasename('.sql'));
		if ($fileVersion > $this->lastExecutedVersion) {
			$filePath = $fileinfo->getPathname();
			$shellCommand = sprintf(
				$this->shellCommandTemplate,
				$this->extensionConfiguration['mysqlBinaryPath'],
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
						$errors[$fileinfo->getFilename()] = array();
					}
					$errors[$fileinfo->getFilename()][] = $ouputMessage;
				}
			}
			return $fileVersion;
		}
		return NULL;
	}

	/**
	 * @param $message
	 * @param $title
	 * @param int $severity
	 */
	protected function flashMessage($message, $title = '', $severity = FlashMessage::OK) {
		if (defined('TYPO3_cliMode')) {
			$this->output($title . ': ' . $message);
		}
		if (!isset($this->flashMessageService)) {
			$this->flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		}
		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue(
			GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, $title, $severity)
		);
	}

	/**
	 * @param $executedFiles
	 * @param $errors
	 */
	protected function enqueueFlashMessages($executedFiles, $errors) {
		$flashMessageTitle = 'Migration Command';
		if ($executedFiles === 0) {
			$this->flashMessage('Everything up to date. No migrations needed.', $flashMessageTitle, FlashMessage::NOTICE);
		} else {
			if (count($errors) !== $executedFiles) {
				$this->flashMessage('Migration of ' . $executedFiles . ' file' . ($executedFiles > 1 ? 's' : '') . ' completed.', $flashMessageTitle, FlashMessage::OK);
			} else {
				$this->flashMessage('Migration failed.', $flashMessageTitle, FlashMessage::ERROR);
			}
			if (count($errors)) {
				$errorMessage = 'The following error' . (count($errors) > 1 ? 's' : '') . ' occured:';
				$errorMessage .= '<ul>';
				foreach ($errors as $filename => $error) {
					$errorMessage .= '<li>File ' . $filename . ': ' . join('<br>', $error) . '</li>';
				}
				$errorMessage .= '</ul>';
				$this->flashMessage($errorMessage, $flashMessageTitle, FlashMessage::ERROR);
			}
		}
	}

}