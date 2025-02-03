<?php declare( strict_types = 1 );

$config = array();
$workingDirectory = getcwd();
$maybePluginFile = basename( $workingDirectory );

foreach ( array( 'dependencies' ) as $discoverDirectory ) {
	if ( is_dir( $workingDirectory . '/' . $discoverDirectory ) ) {
		$config['parameters']['scanDirectories'][] = $workingDirectory . '/' . $discoverDirectory;
	}
}

foreach ( array( "$maybePluginFile.php", 'functions-bootstrap.php', 'functions.php' ) as $analyzeFile ) {
	if ( is_file( $workingDirectory . '/' . $analyzeFile ) ) {
		$config['parameters']['paths'][] = $workingDirectory . '/' . $analyzeFile;
	}
}
foreach ( array( 'src', 'includes', 'models', 'blocks', 'templates' ) as $analyzeDirectory ) {
	if ( is_dir( $workingDirectory . '/' . $analyzeDirectory ) ) {
		$config['parameters']['paths'][] = $workingDirectory . '/' . $analyzeDirectory;
	}
}

if ( is_file( "$workingDirectory/$maybePluginFile.php" ) ) {
	$config['parameters']['WPCompat']['pluginFile'] = "$workingDirectory/$maybePluginFile.php";
}

return $config;
