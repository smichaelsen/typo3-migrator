<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "migrator".
 ***************************************************************/
$EM_CONF['migrator'] = array(
	'title' => 'DB Migrator',
	'description' => 'TYPO3 DB Migrator',
	'category' => 'be',
	'state' => 'beta',
	'author' => 'Sebastian Michaelsen',
	'author_email' => 'sebastian@app-zap.de',
	'author_company' => 'app zap',
	'version' => '1.2.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-7.99.99',
		),
	),
);
