<?php
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;

$application = new \TYPO3\Surf\Application\TYPO3\Flow();
$application->setOption('repositoryUrl', 'git@github.com:langeland/Distributions-MetaCortex.git');
$application->setOption('localPackagePath', FLOW_PATH_DATA . 'Checkout' . DIRECTORY_SEPARATOR . $deployment->getName() . DIRECTORY_SEPARATOR . 'Live' . DIRECTORY_SEPARATOR);
$application->setOption('composerCommandPath', 'composer');
$application->setOption('transferMethod', 'rsync');
$application->setOption('packageMethod', 'git');
$application->setOption('updateMethod', NULL);
$application->setContext('Production');
$application->setDeploymentPath('/home/hostroot/sites/langeland/metacortex');
$application->setOption('keepReleases', 2);
$deployment->addApplication($application);

$workflow = new SimpleWorkflow();
$workflow->setEnableRollback(TRUE);

// Prevent local Settings.yaml from being transferred
$workflow->defineTask('typo3.surf:shell:removeLocalConfiguration',
	'typo3.surf:shell',
	array('command' => 'cd "{releasePath}" && rm -f Configuration/Settings.yaml')
);
$workflow->beforeStage('migrate', array('typo3.surf:shell:removeLocalConfiguration'), $application);

// Remove resource links since they're absolute symlinks to previous releases (will be generated again automatically)
$workflow->defineTask('typo3.surf:shell:unsetResourceLinks',
	'typo3.surf:shell',
	array('command' => 'cd {releasePath} && rm -rf Web/_Resources/Persistent/*(N)')
);
$workflow->beforeStage('switch', array('typo3.surf:shell:unsetResourceLinks'), $application);

$workflow->afterStage('switch', 'typo3.surf:typo3:flow:publishresources', $application);

// Clear PHP 5.5+ OpCache (required for php-fpm)
//$resetScriptFilename = 'surf-opcache-reset-' . uniqid() . '.php';
//$workflow->defineTask('fn:clearopcache',
//	'typo3.surf:shell',
//	array('command' => 'cd {currentPath}/Web && echo "<?php if (function_exists(\"opcache_reset\")) { opcache_reset(); } @unlink(__FILE__); echo \"cache cleared\";" > ' . $resetScriptFilename . ' && curl -s "http://kmcpr-live.lombard.pil.dk/' . $resetScriptFilename . '" && rm -rf ' . $resetScriptFilename)
//);
//$workflow->afterStage('switch', array('fn:clearopcache'), $application);

$deployment->setWorkflow($workflow);

$deployment->onInitialize(function() use ($workflow, $application) {
	$workflow->setTaskOptions('typo3.surf:generic:createDirectories', array('directories' => array('shared/Data/Web/_Resources', 'shared/Data/Session')));
	$workflow->setTaskOptions('typo3.surf:generic:createSymlinks', array(
		'symlinks' => array(
			'Web/_Resources' => '../../../shared/Data/Web/_Resources/',
			'Data/Session' => '../../../shared/Data/Session/'
		)
	));
});

$node = new Node('Flab');
$node->setHostname('ny.flab.dk');
$node->setOption('username', 'langeland');
$application->addNode($node);