<?php
/**
 * @md
 * some useful cli funcions
 * to be included in some other php script
 */


/**
 * @md
 * echo the first docblock comment found in script that includes this file
 * 
 * @return void [description]
 */
function showHelp() {
	// get file contents
	$code = file_get_contents(realpath($_SERVER['PHP_SELF']));

	// get THE FIRST COMMENT ("DOC BLOCK") from file and show as a help (skip @md annotation)
	$tokens = token_get_all($code);
	$comment = array(
		T_DOC_COMMENT   // PHPDoc comments      
	);
	$docBlock = '';
	foreach( $tokens as $token ) {
		if(in_array($token[0], $comment)) {
			$docBlock = $token[1];
			break;
		}
	}

	if($docBlock)
		$help = "\n" . trim(
			preg_replace(
				'#(\s*\*/\s*|\s*\*\s*|\s*/\*\*/\s*|@md)#',
				"\n ",
				$docBlock
			),
			" \t\n\r\0\x0B/"
		) . "\n";
	else
		$help = "\n -- No help documetation for this script -- \n" .
					"\n/**\n * Doc block comment in file is needed to be used as a help!\n */\n";

	run("echo '$help'", false);
}


/**
 * @md
 * run shell command via passthru (and echo commad too)
 *
 * @param  string       $command        [description]
 * @param  bool|boolean $doEchoFirst    [description]
 * @param  array        $returnValuesOk [description]
 */
function run(string $command, bool $doEchoFirst = true, array $returnValuesOk = [0]) {
	if($doEchoFirst)
		passthru('echo "> ' . addcslashes($command, '"`') . '"');
	passthru($command, $return);
	if(!in_array($return, $returnValuesOk, true)) {
		echo "\nexit($return)\n\n";
		exit($return);
	}
}

/**
 * @md
 * color for shell
 * 
 * @param  string $color black, gray, silver, .... see below
 * @return string        symbols for terminal/shell color
 */
function color(string $color = '') {
	static $colors = [
		'black' => '0;30', 'gray' => '1;30', 'silver' => '0;37', 'white' => '1;37',
		'navy' => '0;34', 'blue' => '1;34', 'green' => '0;32', 'lime' => '1;32',
		'teal' => '0;36', 'aqua' => '1;36', 'maroon' => '0;31', 'red' => '1;31',
		'purple' => '0;35', 'fuchsia' => '1;35', 'olive' => '0;33', 'yellow' => '1;33',
		'' => '0',
	];
	$c = explode('/', $color);
	return "\e["
		. str_replace(
			';', "m\e[",
			$colors[$c[0]] . (empty($c[1]) ? '' : ';4' . substr($colors[$c[1]], -1))
		)
		. 'm';
}