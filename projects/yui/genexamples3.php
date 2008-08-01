#!/home/y/bin/php -d open_basedir= 
<?php

define("BUILDPATH", "../../build/");

// Default values for input arguments
$templatesBaseUrl = "http://localhost/templates";
$yuiDistRoot = "examples_dist";
$templatesRoot = "yahoo/presentation/3.x/templates";
$isYDNBuild = false;
$forceBuildPath = false;
$yuiVersion = null;

if ($argc > 1) {
	if ($argv[1] == "-h") {
		printHelp();
		return;
	}  else {
		$args = parseArgs($argv); 
		if (isset($args["u"])) { $templatesBaseUrl = $args["u"]; }
		if (isset($args["d"])) { $yuiDistRoot = $args["d"]; }
		if (isset($args["t"])) { $templatesRoot = $args["t"]; }
		if (isset($args["b"])) { $forceBuildPath = $args["b"]; }
		if (isset($args["y"])) { $isYDNBuild = $args["y"]; }

		if (!isset($args["v"])) {
			echo "\nFailed: -v argument specifying the YUI version is required.\n";
			return;
		} else {
			$yuiVersion = $args["v"];
	    }
	}
}

echo "\nCreating Static Examples\n\n";

$strIsYDNBuild = ($isYDNBuild) ? "true" : "false";
$strForceBuildPath = ($forceBuildPath) ? "true" : "false";

echo "Using Template Base URL: $templatesBaseUrl\n";
echo "Using YUI Build Root: $yuiDistRoot\n";
echo "Using Template Root: $templatesRoot\n";
echo "Is YDN Build: $strIsYDNBuild\n";
echo "Force Build Path: $strForceBuildPath\n";
echo "YUI Version: $yuiVersion\n";

$dataroot = $templatesRoot."/examples/data/";
include($templatesRoot."/examples/module/modules.php");

// 0. Create folders
createFolders($modules);

// 1. Main Landing Page
if($isYDNBuild === false) {
	echo "\n=================================";
	echo "\nCreating Root Index Page";
	echo "\n=================================";

	generateExampleFile("index.php", "index.html", $forceBuildPath);
}

echo "\n=================================";
echo "\nCreating Examples Index Page";
echo "\n=================================";

// 2. Examples Landing Page (uber example list)
generateExampleFile("examples/index.php", "examples/index.html", $forceBuildPath);

// 3. Generate Examples
generateExamples($modules, $examples);

// 4. Copy Assets
copyAssets(); 

echo "\n\nDone\n";

return;

#######################################################################
# Function Definitions
#######################################################################

/**
 * Create top level example folders under Dist
 */
function createFolders($modules) {

	global $yuiDistRoot;
	global $isYDNBuild;

	echo "\n=================================";
	echo "\nCreating Folders";
	echo "\n=================================";

	foreach($modules as $moduleKey=>$module) {

		$folderPath = "$yuiDistRoot/examples/$moduleKey";

		if (file_exists($folderPath) === false) {
			echo "\nCreating $folderPath";
			if (mkdir($folderPath, 0777, true)) {
				echo " - OK";
			} else {
				echo " - Failed";
			}
		}

	}

	$assetsPath = "$yuiDistRoot/assets"; 

	if (file_exists($assetsPath) === false) {
		echo "\nCreating $assetsPath";
 		if(mkdir($assetsPath, 0777, true)) {
			echo " - OK";
		} else {
			echo " - Failed";
	 	}	
	}

	if ($isYDNBuild) {
		$incFolderPath = "$yuiDistRoot/inc/examplesNav";
	
		if (file_exists($incFolderPath) === false) {
			echo "\nCreating $incFolderPath";
			if (mkdir($incFolderPath, 0777, true)) {
				echo " - OK";
			} else {
				echo " - Failed";
			}
		}
	}
}

/**
 * Copies shared assets from Templates to Dist
 */
function copyAssets() {

	global $yuiDistRoot;
	global $templatesRoot;

	echo "\n=================================";
	echo "\nCopying Top Level Assets";
	echo "\n=================================";

	$src = "$templatesRoot/assets"; 
	$dest = "$yuiDistRoot/"; 

	copyDirectory($src, $dest, "");
}


/**
 * Copies module assets from Templates to Dist
 */
function copyModuleAssets($moduleKey) {

	global $yuiDistRoot;
	global $templatesRoot;
	global $isYDNBuild;

	$src = "$templatesRoot/examples/data/$moduleKey/assets";
	$dest = "$yuiDistRoot/examples/$moduleKey/";

	copyDirectory($src, $dest, "--exclude=exampleslib*.inc");

	if ($isYDNBuild) {
		if (file_exists("$src/exampleslib_ydn.inc")) {
			copy("$src/exampleslib_ydn.inc", $dest."assets/exampleslib.inc");
		}
	} else {
		if (file_exists("$src/exampleslib_dist.inc")) {
			copy("$src/exampleslib_dist.inc", $dest."assets/exampleslib.inc");
		}
	}
}

/**
 * Copies one directory to another, using rsync -r --exclude=CVS 
 */
function copyDirectory($s, $d, $args) {

	// NOTE: Deciding to use rsync as opposed to DirectoryIterator, 
	// to save checking for CVS files. Also using --exclude
	// to limit exclusion to just the CVS dir. 
	// Could use -C if we wanted to exclude everything in 
	// CVSIGNORE, but thought this maybe too much (*.out, *.bak etc..) 
	// and also introduces ENV dependancies.

	if (file_exists($s)) {
		echo "\nCopying Directory $s to $d";

		$cmd = "rsync -r --exclude=CVS $args $s $d";
		exec($cmd, $out, $ret);

		if ($ret > 0) {
			echo " - Failed";
		} else {
			echo " - OK";
		}
	} else {
		echo "\nNo $s to copy - SKIPPED";
	}
}

/**
 * Helper to add query params to a url string.
 * 
 * Url encodes parameter values and checks whether or not to
 * append with ? or &
 */
function addQueryParam($url, $paramName, $paramValue) {
	$containsParams = strpos($url, "?");
	if ($containsParams === false) {
		$url = $url."?".$paramName."=".urlencode($paramValue);
	} else {
		$url = $url."&".$paramName."=".urlencode($paramValue);
	}
	return $url;
}

/**
 * Generates a file with the given filename/path under Dist
 * from the output of the given URL. The file path needs to 
 * be the 'real' non-symlinked path.
 */
function generateExampleFile($srcUrl, $fileName, $useBuildPath) {

	global $yuiDistRoot;
	global $templatesBaseUrl;
	global $isYDNBuild;
	global $yuiVersion;

	$file = $yuiDistRoot."/".$fileName;
	$url = $templatesBaseUrl."/".$srcUrl;

	if ($useBuildPath) {
		$url = addQueryParam($url, "buildpath", BUILDPATH);
	}
	
	if ($isYDNBuild) {
		$url = addQueryParam($url, "ydn", "true");
	}

	$url = addQueryParam($url, "v", $yuiVersion);

	echo "\nGenerating: $file\nfrom [$url]";

	$cUrl = curl_init($url);
	$fHandle = fopen($file, "w");

	if ($fHandle) {
		curl_setopt($cUrl, CURLOPT_FILE, $fHandle);
		curl_setopt($cUrl, CURLOPT_HEADER, false);
		curl_exec($cUrl);
		curl_close($cUrl);
		fclose($fHandle);
		echo " - OK";
	} else {
		echo "- Failed";
	}
}


/**
 * Generates the set of static example HTML files under Dist, 
 * by iterating over the module/examples arrays
 */
function generateExamples($modules, $examples) {

	global $forceBuildPath;
	global $isYDNBuild;

	// COULDN'T REALLY REMEMBER WHY WE WERE FILTERING BY TYPE, WHEN 
        // WE WANT TO RUN THROUGH ALL MODULES ANYWAY. HENCE COMMENTING
	// OUT, OUTER LOOP.

	// MAKE CONSTANT?
	// $types = array('css', 'utility', 'widget', 'tool', 'core');

	// foreach($types as $type) {

		// $modulesForType = getModulesByType($type, $modules);

		foreach($modules as $moduleKey=>$module) {

			echo "\n=================================";
			echo "\nGenerating $moduleKey examples";
			echo "\n=================================";

			copyModuleAssets($moduleKey);
			
			//$useBuildPath = ($isYDNBuild === false || $forceBuildPath);
			$useBuildPath = $forceBuildPath;

			// Index - Dist: build path, YDN: yui.yahooapis
			generateExampleFile("examples/module/examplesModuleIndex.php?module=".urlencode($moduleKey), 
						"examples/$moduleKey/index.html", $useBuildPath);

			// Left Nav for YDN
			if ($isYDNBuild) {
				generateExampleFile("examples/module/examplesLandingPageNav.php?module=".urlencode($moduleKey), 
						"inc/examplesNav/$moduleKey.inc", $useBuildPath);
			}

			$moduleExamples = getExamplesByModule($moduleKey, $examples);
	
			if ($moduleExamples) {
				
				foreach($moduleExamples as $exampleIdx=>$example) {

					$exampleKey = $example["key"];

					if ($example["modules"][0] == $moduleKey) {
	
						// Default Presentation (XXX.html) - Dist: build path, YDN: build path if logger required, else yui.yahooapis
						generateExampleFile("examples/module/example.php?name=".urlencode($exampleKey),
									"examples/$moduleKey/$exampleKey".".html", $useBuildPath);
	
						// Requires New Window (XXX_source.html) - Dist: build path, YDN: yui.yahooapis
						if ($example["newWindow"] == "require") {
//							generateExampleFile("examples/data/$moduleKey/$exampleKey"."_source.php",
//										"examples/$moduleKey/$exampleKey"."_source.html", $useBuildPath);
							generateExampleFile("examples/module/example.php?name=".urlencode($exampleKey)."&clean=true", 
										"examples/$moduleKey/$exampleKey"."_source.html", $useBuildPath);
						}
					
						// Supports New Window (XXX_clean.html) - Dist: build path, YDN: yui.yahooapis
						if ($example["newWindow"] != "require" && $example["newWindow"] != "suppress") {
							generateExampleFile("examples/module/example.php?name=".urlencode($exampleKey)."&clean=true", 
										"examples/$moduleKey/$exampleKey"."_clean.html", $useBuildPath);
						} 
					
						// Supports Logging (XXX_log.html) - Dist: build path, YDN: build path (debug files not hosted on yui.yahooapis)
						if (isset($example["loggerInclude"]) && $example["loggerInclude"] != "require" && $example["loggerInclude"] != "suppress") {
							generateExampleFile("examples/module/example.php?name=".urlencode($exampleKey)."&log=true", 
										"examples/$moduleKey/$exampleKey"."_log.html", $useBuildPath);
						}
					}
				}
			}
		}
	// }
}


/**
 * Parses input args into a hashmap with argname => argvalue key/value
 * pairs (argname is saved without the -). No extensive error checking
 * performed. Expects a valid input.
 */ 
function parseArgs($argsArray) {

    $arr = array();

    for ($i=1; $i < count($argsArray); $i++) {
        $val = $argsArray[$i];
		if ($val == "-u") {
			$arr["u"] = $argsArray[++$i];       
		}
		if ($val == "-d") {
			$arr["d"] = $argsArray[++$i];
		}
		if ($val == "-t") {
			$arr["t"] = $argsArray[++$i];
		}
		if ($val == "-b") {
			$arr["b"] = ($argsArray[++$i] != "false") ? true : false;
		}
		if ($val == "-y") {
			$arr["y"] = ($argsArray[++$i] != "false") ? true : false;
		}
		if ($val == "-v") {
			$arr["v"] = $argsArray[++$i];
		}
    }
    return $arr;
}


/**
 * Prints help
 */ 
function printHelp() {
	echo "\nUsage: ./genexamples.php [-u templatesurl] [-d yuibuildroot] [-b true|false] [-y true|false] [-t templatesroot]";

	echo "\n\n-t templatesurl\n"
		."\n    The absolute URL for the templates folder on a server hosting"
		."\n    the yui build."
		."\n"
		."\n    Defaults to 'http://localhost/templates'";

	echo "\n\n-d yuibuildroot\n"
		."\n    The path to the root directory under which the examples "
		."\n    directory structure will be created. Needs to be the 'real'"
		."\n    non-symlinked path, due to php fopen limitations."
		."\n"
		."\n    Defaults to 'examples_dist'";

	echo "\n\n-t templatesroot\n"
		."\n    The path to the templates folder. Can be relative to "
		."\n    gendistexamples.php."
		."\n"
		."\n    Defaults to 'yahoo/presentation/templates'";

	echo "\n\n-y true|false\n"
		."\n    Specifies if the build is for YDN (or dist)."
		."\n"
		."\n    If true, files are generated for YDN."
		."\n    If false, they are generated for the distribution."
		."\n"
		."\n    Defaults to false (generate files for distribution)";

	echo "\n\n-v YUI version\n"
		."\n    Specifies the YUI version, used to set the yuiCurrentVersion variable for examples to use"
		."\n"
		."\n    No Default";

	echo "\n\n-b true|false\n"
		."\n    Use a local build path instead of yui.yahooapis.com"
		."\n"
		."\n    If true, all examples will use the local build path."
		."\n    If false, all examples will use yui.yahooapis URLs."
		."\n"
		."\n    Defaults to false";
	
	echo "\n\nNOTE: All paths should be specified without trailing slashes.\n\n";
} 

?>
