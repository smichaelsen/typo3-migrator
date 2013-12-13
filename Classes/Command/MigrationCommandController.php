<?php

namespace AppZap\Migrator\Command;

use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class MigrationCommandController extends CommandController {

	public function migrateSqlFilesCommand() {
		$this->output('yeah');
	}

}