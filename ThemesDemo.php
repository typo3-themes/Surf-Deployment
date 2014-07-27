<?php
$workflow = new \TYPO3\Surf\Domain\Model\SimpleWorkflow();
$deployment->setWorkflow($workflow);

$node = new \TYPO3\Surf\Domain\Model\Node('Testing');
$node->setHostname('');                             // <- fill in 
$node->setOption('username', '');                   // <- fill in 
// avoid cleaning of cache directory
$node->setOption('hardClean', FALSE);

$application = new \TYPO3\Surf\Application\TYPO3\CMS();
$application->setName('Themes.demo');
$application->setDeploymentPath('');               // <- fill in 
$application->setOption('repositoryUrl', 'git://github.com/typo3-themes/Vagrant.Themes.git');
$application->setOption('keepReleases', 2);

$application->addNode($node);
$deployment->addApplication($application);

$workflow->setEnableRollback(FALSE);

$workflow->defineTask(
	'themes.demo:create.symlinks',
	'typo3.surf:shell',
	array(
		'command' => 'cp {sharedPath}/typo3_src/index.php {releasePath}/project/index.php; cp -r {sharedPath}/typo3_src/typo3 {releasePath}/project/;',
		'logOutput' => TRUE
	)
);

$workflow->defineTask(
	'themes.demo:create.dirs',
	'typo3.surf:shell',
	array(
		'command' => 'mkdir {releasePath}/project/typo3temp;',
		'logOutput' => TRUE
	)
);

$workflow->defineTask(
	'themes.demo:get.extensions',
	'typo3.surf:shell',
	array(
		'command' => 'cd {sharedPath}/typo3conf/ext/; GITTYPE=HTTP bash {releasePath}/serverdata/provision/install-extensions.sh;',
		'logOutput' => TRUE
	)
);

$workflow->defineTask(
	'themes.demo:copy.config',
	'typo3.surf:shell',
	array(
		'command' => 'cp -r {sharedPath}/typo3conf/* {releasePath}/project/typo3conf',
		'logOutput' => TRUE
	)
);

$workflow->defineTask(
	'themes.demo:import.database',
	'typo3.surf:shell',
	array(
		'command' => 'mysql --user=secretUser --password=secretPassword --host=hostname dbName < {releasePath}/serverdata/data/sql/t3-latest.sql',
		'logOutput' => TRUE
	)                // <- fill in dbuser and password
);



$deployment->onInitialize(function() use ($workflow, $application) {

	$workflow->beforeStage(
		'migrate',
		array(
			'themes.demo:create.symlinks',
			'themes.demo:create.dirs',
			'themes.demo:import.database',
			'themes.demo:get.extensions',
			'themes.demo:copy.config',
		)
	);
});

?>
