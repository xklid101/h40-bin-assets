#!/usr/bin/php7.2
<?php
/**
 * Description of file
 * 
 * @md
 * 
 * Generates markdown file from all files (extension doesn't matter) contents in specified directory (or file).
 * Simply by getting text from php-like docblock-comments annotaded with @md and inserting it into .md file.
 * See this file comment annotations to understand how it works
 * 'composer.json' file in defined directory is used to generate heading and basic description.
 * Markdown file will be saved into parsed directory or next to parsed file.;
 *
 * for help and how to use run from terminal:
 * ```bash
 * ~$ php generate.mdfile --help
 * ```
 *
 * @md (optional)
 *
 * above annotation is optional, if ommited, this comment will be in .md file all
 */

//php error handler
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline, array $errcontext) {
    echo "\n\n   " . chr(27) . "[1;33m" . chr(27) . "[41m Error: $errstr" . chr(27) . "[0m\n";
    echo chr(27) . "[41m" . chr(27) . "[1;33m (in file $errfile on line $errline)" . chr(27) . "[0m\n\n";
    showHelpHint();
    exit(500);
}, E_ALL);

//set opts
$defaults = [
    'namemd' => "README.md",
    'skip' => ["vendor", ".git", ".gitignore", "composer.json", "composer.lock",
                "www/node_modules", "www/package.json", "www/package-lock.json",
                ":REGEXP:([^/]+/)*robots\.txt", ":REGEXP:([^/]+/)*\.htaccess",
                ":REGEXP:([^/]+/)*[^/]+\.md", ":REGEXP:([^/]+/)*[^/]+\.neon",
                ":REGEXP:([^/]+/)*temp", ":REGEXP:([^/]+/)*tests/([^/]+/)*output"],
];
$opts = getopt('', [
    'namemd:',
    'skip:',
    'skip-overwrite',
    'help',
], $optind);

if(isset($opts['help'])) {
    showHelp();
    exit(0);
}

//test directory
$dirToParseArr = array_slice($argv, $optind);
if(!$dirToParseArr) {
    // trigger_error('No directory or file to parse passed!');
    showHelp();
    exit(0);
}
$dirToParse = reset($dirToParseArr);
if(!is_dir($dirToParse) && !is_file($dirToParse))
    trigger_error("Directory or file '$dirToParse' not found!");

//get merged options with defaults
$options = $opts + $defaults;
if(!is_array($options['skip']))
    $options['skip'] = [$options['skip']];
$options['skip'][] = $options['namemd'];
if(!isset($opts['skip-overwrite'])) {
    foreach ($defaults['skip'] as $value) {
        if(!in_array($value, $options['skip']))
            $options['skip'][] = $value;
    }
}


//get all files
$filesToParse = [];
if(is_file($dirToParse)) {
    $filesToParse[] = $dirToParse;
    $dirToSave = dirname($dirToParse);
}
else {
    $dirToSave = $dirToParse;
    $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dirToParse,
                                                    FilesystemIterator::KEY_AS_PATHNAME |
                                                    FilesystemIterator::CURRENT_AS_FILEINFO |
                                                    FilesystemIterator::SKIP_DOTS
                                                    )
                    );

    //to sort by path name
    $objectsArray = [];
    foreach ($objects as $name => $object) {
        $objectsArray[$name] = $object;
    }
    ksort($objectsArray);

    foreach($objectsArray as $name => $object){
        //skip files
        $skip = false;
        foreach ($options['skip'] as $fileToSkip) {
            if(strpos($fileToSkip, ':REGEXP:') === 0)
                $strToSkip = str_replace(':REGEXP:', '', $fileToSkip);
            else
                $strToSkip = preg_quote(trim($fileToSkip, '/'), "#");
            
            if(preg_match("#^" . preg_quote(rtrim($dirToParse, '/'), "#") . "/" . $strToSkip . "(/|$)#i", $name)) {
                $skip  = true;
                break;
            }
        }
        if(!$skip)
            $filesToParse[] = $name;
    }
}
$dirToSave = rtrim($dirToSave, '/');
$fileToSave = $dirToSave . '/' . trim($options['namemd'], '/');
$fileComposerJson = $dirToSave . '/composer.json';

if(file_exists($fileToSave)) {
    echo "\nThe file '$fileToSave' Already exists!\n\n";
    echo chr(27) . "[1;37mDo you want to continue and overwrite existing file by a new one generated?\n";
    echo "Type 'y' to confirm, or 'n' (or anything else) to exit: " . chr(27) . "[0m";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    if(strtolower(trim($line)) != 'y'){
        echo "ABORTING!\n\n";
        exit;
    }
    echo "\n";
    echo "Thank you, continuing...\n\n";
}

generateMd($filesToParse, $fileToSave, $fileComposerJson, $dirToSave);

echo "\n   " . chr(27) . "[1;37m" . chr(27) . "[42m Markdown content generated in '$fileToSave'" . chr(27) . "[0m\n\n";


function showHelp() {
    global $argv;
    global $defaults;

    echo "\n";
    echo "Usage:\n";
    echo "php " . $argv[0] . " ";
    echo "[OPTIONS] ";
    echo "/path/to/dir/or/file/toparse";
    echo "\n\n";
    echo "Generates markdown file from all files (extension doesn't matter) contents in specified directory (or file).\n";
    echo "Simply by getting text from php-like docblock-comments annotaded with @md and inserting it into .md file.\n";
    echo "e.g. /** This is not included @md and now it is in our .md file */\n";
    echo "'composer.json' file in defined directory is used to generate heading and basic description.\n";
    echo "Markdown file will be saved into parsed directory or next to parsed file.";
    echo "\n\n\n";
    echo "OPTIONS:";
    echo "\n\n";
    echo " --help   \t\tsee this help";
    echo "\n\n";
    echo " --namemd \t\t--namemd=output_file_name.md";
    echo "\n\t\t\t(default \"$defaults[namemd]\")";
    echo "\n\t\t\tset generated .md file name";
    echo "\n\n";
    echo " --skip   \t\t--skip=subdir_so_skip --skip=next_subfile_toskip";
    echo "\n\t\t\t(default array of \"" . implode(', ', $defaults['skip']) . "\"\n and all binary files)";
    echo "\n\t\t\tcase insensitive - skip subdirectories or files when parsing;";
    echo "\n\t\t\t(only applied when /path/to/dir/or/file/toparse is a directory)";
    echo "\n\t\t\trepeat --skip option multiple times to use array of values";
    echo "\n\n";
    echo " --skip-overwrite\tif presented, then --skip options replace defaults instead of append to defaults";
    echo "\n\t\t\t(default to off: means --skip options are appended to --skip defaults)";
    echo "\n\n";
}

function showHelpHint() {
    global $argv;

    echo "\n";
    echo "To show help just run:\n";
    echo "php " . $argv[0] . " ";
    echo "--help";
    echo "\n\n";
}

function generateMd(array $filesToParse, string $fileToSave, string $fileComposerJson, string $dirToSave) {
    $composeData = [];
    if(file_exists($fileComposerJson))
        $composeData = json_decode(file_get_contents($fileComposerJson), true);

    $name = $composeData['name'] ?? '';
    $description = $composeData['description'] ?? '';
    $licenses = $composeData['license'] ?? [];
    if(!is_array($licenses))
        $licenses = [$licenses];
    $authors = $composeData['authors'] ?? [];

    $contents = '';
    if($name)
        $contents .= "# $name\n\n";
    if($description)
        $contents .= "$description\n\n";

    $contents .= "**************************\n";
    $contents .= "**************************\n\n";

    $filesWithoutMdAnnotation = [];
    foreach ($filesToParse as $fileToParse) {

        $fileToParseContents = file_get_contents($fileToParse);
        if(!empty(trim($fileToParseContents)) && isBinary($fileToParseContents))
            continue;

        // some special operations for specific extensions
        //  (like nginx .conf files - which do not support multilne comments)
        if(pathinfo($fileToParse, PATHINFO_EXTENSION) === 'conf') {
            // replace "# /**" for "/**"
            $fileToParseContents = preg_replace('~(\n|^)\s*#\s*(/\*\*)~', '$1$2', $fileToParseContents);
            // replace "# */" for "*/"
            $fileToParseContents = preg_replace('~(\n)\s*#\s*(\*/)~', '$1$2', $fileToParseContents);
            // replace "# *" for "*"
            $fileToParseContents = preg_replace('~(\n)\s*#\s*(\*)~', '$1$2', $fileToParseContents);
        }

        $docComments = array_filter(
            //to parse non-php files for php-like docblock comments
            //  suppress warnings like unterminated comments etc
            @token_get_all('<?php ' . $fileToParseContents),
            function($entry) {
                return $entry[0] == T_DOC_COMMENT;
            }
        );
        $doccommentConetnts = [];
        foreach ($docComments as $docComment) {
            $cont = null;
            //remove begin and end of doccomment (/** and */)
            $c = trim(
                        substr(
                            str_replace(["\r\n", "\r"], "\n", $docComment[1]),
                            3,
                            -2)
                        );
            //remove staring asterisks
            $commentRows = [];
            foreach (explode("\n", $c) as $commentRow) {
                // $commentRows[] = ltrim(ltrim($commentRow), '*');
                $commentRows[] = preg_replace("#^\*\s?#", '', trim($commentRow));
            }
            //modify line endings to fit markdown (two trailing spaces before lineend)
            $comment = implode("  \n", $commentRows);
            //match our annotation with strict ending
            if(preg_match('/@md(.+)@md/s', $comment, $matches)) {
                $cont = trim($matches[1]);
            }
            //match our annotation without ending
            elseif(preg_match('/@md(.+)$/s', $comment, $matches)) {
                $cont = trim($matches[1]);
            }
            if($cont) {
                $doccommentConetnts[] = "\n$cont\n";
                $doccommentConetnts[] = "**************************";
            }
        }
        $fileName = preg_replace("#^$dirToSave#", '', $fileToParse);
        if($doccommentConetnts) {
            $contents .= "### " . $fileName . "\n";
            $contents .= "**************************\n";
            // if($doccommentConetnts) {
                $contents .= implode("  \n", $doccommentConetnts) . "\n";
            // }
            // else {
            //     $contents .= "`no documentation for this file (no @md docblock)...`\n";
            //     $contents .= "**************************\n";
            // }

            $contents .= "**************************\n\n";
        }
        else {
            $filesWithoutMdAnnotation[] = $fileName;
        }
    }
    if($filesWithoutMdAnnotation) {
        $contents .= "### Files without .md documentation (no @md docBlock in file)\n";
        $contents .= implode("  \n", $filesWithoutMdAnnotation) . "\n";
        $contents .= "**************************\n\n";
    }

    if($licenses) {
        $contents .= "## License  \n";
        foreach ($licenses as $val) {
            $contents .= "$val  \n";
        }
        $contents .= "\n";
    }
    if($authors) {
        $contents .= "## Authors  \n";
        foreach ($authors as $val) {
            $contents .= json_encode($val, JSON_PRETTY_PRINT) . "  \n";
        }
    }

    // $contents .= "*******************************\n";
    // $contents .= "Timestamp  \n";
    // $contents .= date('j.n.Y H:i:s') . "\n";

    file_put_contents($fileToSave, $contents);
}

/**
 * test if trimmed string is binary or empty
 *     is using Nette\Utils\Strings::toAscii function 
 *         - found out that binary files returns empty string,
 *             so this is the most reliable test for binary string now
 *     
 * @param  string  $str [description]
 * @return boolean      [description]
 */
function isBinary(string $str): bool {
    return empty(trim(toAscii($str)));
}

function toAscii($s) {
    static $transliterator = null;
    if ($transliterator === null && class_exists('Transliterator', false)) {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
    }

    $s = preg_replace('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{2FF}\x{370}-\x{10FFFF}]#u', '', $s);
    $s = strtr($s, '`\'"^~?', "\x01\x02\x03\x04\x05\x06");
    $s = str_replace(
        ["\xE2\x80\x9E", "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x9A", "\xE2\x80\x98", "\xE2\x80\x99", "\xC2\xB0"],
        ["\x03", "\x03", "\x03", "\x02", "\x02", "\x02", "\x04"], $s
    );
    if ($transliterator !== null) {
        $s = $transliterator->transliterate($s);
    }
    if (ICONV_IMPL === 'glibc') {
        $s = str_replace(
            ["\xC2\xBB", "\xC2\xAB", "\xE2\x80\xA6", "\xE2\x84\xA2", "\xC2\xA9", "\xC2\xAE"],
            ['>>', '<<', '...', 'TM', '(c)', '(R)'], $s
        );
        $s = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT//IGNORE', $s);
        $s = strtr($s, "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e"
            . "\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3"
            . "\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8"
            . "\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe"
            . "\x96\xa0\x8b\x97\x9b\xa6\xad\xb7",
            'ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt- <->|-.');
        $s = preg_replace('#[^\x00-\x7F]++#', '', $s);
    } else {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    }
    $s = str_replace(['`', "'", '"', '^', '~', '?'], '', $s);
    return strtr($s, "\x01\x02\x03\x04\x05\x06", '`\'"^~?');
}