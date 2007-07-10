#!/home/y/bin/php -d open_basedir= 
<?php

define("BUILDPATH", "../../build/");

// Default values for input arguments
$templatesBaseUrl = "http://localhost/templates";
$yuiDistRoot = "examples_dist";
$templatesRoot = "yahoo/presentation/templates";
$isYDNBuild = false;
$forceBuildPath = false;

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

include("$templatesRoot/examples/data/examplesInfo.php");

// 0. Create folders
createFolders($modules);

echo "\n=================================";
echo "\nCreating Uber Index Pages";
echo "\n=================================";

// 1. Main Landing Page (Mega Uber List)
generateExampleFile("index.php", "index.html", false);

// 2. Per Example Landing Page (Not so Uber List)
generateExampleFile("examples/index.php", "examples/index.html", false);

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
 		if(mkdir($assetsPath)) {
			echo " - OK";
		} else {
			echo " - Failed";
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

	copyDirectory($src, $dest);
}


/**
 * Copies module assets from Templates to Dist
 */
function copyModuleAssets($moduleKey) {

	global $yuiDistRoot;
	global $templatesRoot;

	$src = "$templatesRoot/examples/$moduleKey/assets";
	$dest = "$yuiDistRoot/examples/$moduleKey/"; 

	copyDirectory($src, $dest);
}


/**
 * Copies one directory to another, using rsync -r --exclude=CVS 
 */
function copyDirectory($s, $d) {

	// NOTE: Deciding to use rsync as opposed to DirectoryIterator, 
	// to save checking for CVS files. Also using --exclude
	// to limit exclusion to just the CVS dir. 
	// Could use -C if we wanted to exclude everything in 
	// CVSIGNORE, but thought this maybe too much (*.out, *.bak etc..) 
	// and also introduces ENV dependancies.

	if (file_exists($s)) {
		echo "\nCopying Directory $s to $d";

		$cmd = "rsync -r --exclude=CVS $s $d";
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

	$file = $yuiDistRoot."/".$fileName;
	$url = $templatesBaseUrl."/".$srcUrl;

	if ($useBuildPath) {
		$url = addQueryParam($url, "buildpath", BUILDPATH);
	}
	
	if ($isYDNBuild) {
		$url = addQueryParam($url, "ydn", "true");
	}

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

	// MAKE CONSTANT?
	$types = array('css', 'utility', 'widget');

	foreach($types as $type) {

		$modulesForType = getModulesByType($type, $modules);

		foreach($modulesForType as $moduleKey=>$module) {

			echo "\n=================================";
			echo "\nGenerating $moduleKey examples";
			echo "\n=================================";

			copyModuleAssets($moduleKey);
			
			$useBuildPath = ($isYDNBuild === false || $forceBuildPath);

			// Index - Dist: build path, YDN: yui.yahooapis
			generateExampleFile("examples/module/examplesModuleIndex.php?module=".urlencode($moduleKey), 
						"examples/$moduleKey/index.html", $useBuildPath);

			$moduleExamples = getExamplesByModule($moduleKey, $examples);
	
			if ($moduleExamples) {
				
				foreach($moduleExamples as $exampleIdx=>$example) {

					$exampleKey = $example["key"];

					if ($example["modules"][0] == $moduleKey) {
	
						// Default Presentation (XXX.html) - Dist: build path, YDN: build path if logger required, else yui.yahooapis
						generateExampleFile("examples/module/example.php?name=".urlencode($exampleKey),
									"examples/$moduleKey/$exampleKey".".html", ($useBuildPath || $example["loggerInclude"] == "require"));
	
						// Requires New Window (XXX_source.html) - Dist: build path, YDN: yui.yahooapis
						if ($example["newWindow"] == "require") {
							generateExampleFile("examples/$moduleKey/$exampleKey"."_source.php",
										"examples/$moduleKey/$exampleKey"."_source.html", $useBuildPath);
						}
					
						// Supports New Window (XXX_clean.html) - Dist: build path, YDN: yui.yahooapis
						if ($example["newWindow"] != "require" && $example["newWindow"] != "suppress") {
							generateExampleFile("examples/module/example.php?name=".urlencode($exampleKey)."&clean=true", 
										"examples/$moduleKey/$exampleKey"."_clean.html", $useBuildPath);
						} 
					
						// Supports Logging (XXX_log.html) - Dist: build path, YDN: build path (debug files not hosted on yui.yahooapis)
						if ($example["loggerInclude"] != "require" && $example["loggerInclude"] != "suppress") {
							generateExampleFile("examples/module/example.php?name=".urlencode($exampleKey)."&log=true", 
										"examples/$moduleKey/$exampleKey"."_log.html", true);
						}
					}
				}
			}
		}
	}
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
    }
    return $arr;
}


/**
 * Prints help
 */ 
function printHelp() {
	echo "\nUsage: ./gendistexamples.php [-u templatesurl] [-d yuibuildroot] [-b true|false] [-y true|false] [-t templatesroot]";

	echo "\n\n-t templatesurl\n\tThe absolute URL for the templates folder"
		."\n\ton a server hosting the yui build."
		."\n\tDefaults to 'http://localhost/templates'";
	echo "\n\n-d yuibuildroot\n\tThe path to the root directory under which the examples directory structure will be created."
		."\n\tNeeds to be the 'real' non-symlinked path, due to php limitations with fopen."
		."\n\tDefaults to 'examples_dist'";
	echo "\n\n-t templatesroot\n\tThe path to the templates folder."
		."\n\tCan be relative to gendistexamples.php."
		."\n\tDefaults to 'yahoo/presentation/templates'";
	echo "\n\n-b true:false \n\tForce build to use a buildPath?"
		."\n\tBy default YDN examples will use yui.yahooapis.com URLs for non-debug example. Set this flag to true to force YDN to be built with a local build path for all examples. NOTE: Dist examples are always built with a local build path"
		."\n\tIf true, all examples will be generated with a buildPath (loader will pickup files from local build dir). If false, YDN examples will use yui.yahooapis URLs for non-debugger examples"
		."\n\tDefaults to false";
	echo "\n\n-y true:false \n\tIs this build for YDN?"
		."\n\tIf true, files are generated for YDN, if false, they are generated for the distribution"
		."\n\tDefaults to false";
	
	echo "\n\nNOTE: All paths should be specified without trailing slashes.\n\n";
} 

?>
