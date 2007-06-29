#!/home/y/bin/php -d open_basedir= 
<?php

include('../../../../templates/examples/data/examplesInfo.php');

// Input Args default values

$templatesBaseUrl = "http://localhost/templates";
$yuiDistRoot = "yuidist";
$templatesRoot = "../../../../templates"; 

if ($argc > 1) {
	if ($argv[1] == "-h") {
		printHelp();
		return;
	}  else {
		$args = parseArgs($argv); 
		if (isset($args["u"])) { $templatesBaseUrl = $args["u"]; }
		if (isset($args["d"])) { $yuiDistRoot = $args["d"]; }
		if (isset($args["t"])) { $templatesRoot = $args["t"]; }
	}
}

echo "\nStart\n";

echo "Using Template Base URL: $templatesBaseUrl\n";
echo "Using YUI Dist Root: $yuiDistRoot\n";
echo "Using Template Root: $templatesRoot\n";

// 0. Create folders
createFolders($modules);

// 1. Main Landing Page (Mega Uber List)
generateExampleFile("index.php", "index.html");

// 2. Per Example Landing Page (Not so Uber List)
generateExampleFile("examples/index.php", "examples/index.html");

// 3. Generate Examples
generateExamples($modules, $examples);

// 4. Copy Assets
copyAssets(); 

echo "\n\nDone\n";

return;

#######################################################################
# Function Definitions
#######################################################################

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

function copyAssets() {

	global $yuiDistRoot;
	global $templatesRoot;

	echo "\n=================================";
	echo "\nCopying Assets";
	echo "\n=================================";
	echo "\nCopying $templatesRoot/assets/*.* to $yuiDistRoot/assets";

        // TODO: Copying flat for now, to avoid CVS. 
        // Update to recurse, without copying CVS.
	
	$cmd = "cp $templatesRoot/assets/*.* $yuiDistRoot/assets"; 
	exec ($cmd, $out, $ret);

	if ($ret > 0) {
		echo " - Failed";
	} else {
		echo " - OK";
	}
}

function generateExampleFile($srcUrl, $fileName) {

	global $yuiDistRoot;
	global $templatesBaseUrl;

	$file = $yuiDistRoot."/".$fileName;
	$url = $templatesBaseUrl."/".$srcUrl;

	echo "\nGenerating: $file [$url]";

	$cUrl = curl_init($url);
	$fHandle = fopen($file, "w");

	if ($fHandle) {
		curl_setopt($cUrl, CURLOPT_FILE, $fHandle);
		curl_setopt($cUrl, CURLOPT_HEADER, false);
		curl_exec($cUrl);
		curl_close($cUrl);
		fclose($fHandle);
		echo "- OK";
	} else {
		echo "- Failed";
	}
}

function generateExamples($modules, $examples) {

	// MAKE CONSTANT?
	$types = array('css', 'utility', 'widget');

	foreach($types as $type) {

		$modulesForType = getModulesByType($type, $modules);

		foreach($modulesForType as $moduleKey=>$module) {

			echo "\n=================================";
			echo "\nGenerating $moduleKey examples";
			echo "\n=================================";

			generateExampleFile("examples/module/examplesModuleIndex.php?module=$moduleKey", 
									"examples/$moduleKey/index.html");

			$moduleExamples = getExamplesByModule($moduleKey, $examples);
	
			if ($moduleExamples) {
				
				// 3. Example Pages
				foreach($moduleExamples as $exampleKey=>$example) {
	
					// Default Presentation (XXX.html)
					generateExampleFile("examples/module/example.php?name=$exampleKey",
									"examples/$moduleKey/$exampleKey".".html");

					// Requires New Window (XXX_source.html)
					if ($example["newWindow"] == "require") {
						generateExampleFile("examples/data/src/$moduleKey/$exampleKey"."_source.php", 
												"examples/$moduleKey/$exampleKey"."_source.html");
					}
					
					// Supports New Window (XXX_clean.html)
					if ($example["newWindow"] == "default") {
						generateExampleFile("examples/module/example.php?name=$exampleKey&clean=true", 
												"examples/$moduleKey/$exampleKey"."_clean.html");
					} 
					
					// Supports Logging (XXX_log.html)
					if ($example["loggerInclude"] == "default") {
						generateExampleFile("examples/module/example.php?name=$exampleKey&log=true", 
												"examples/$moduleKey/$exampleKey"."_log.html");
					}
				}
			}
		}
	}
}

function parseArgs($argsArray) {

    $arr = array();

    for ($i=1; $i < count($argsArray); $i++) {
        $val = $argsArray[$i];
	echo $val;
	if ($val == "-u") {
		$arr["u"] = $argsArray[++$i];       
	}
	if ($val == "-d") {
		$arr["d"] = $argsArray[++$i];
	}
	if ($val == "-t") {
		$arr["t"] = $argsArray[++$i];
	}
    }

    return $arr;
}

function printHelp() {
	echo "\nUsage: ./gendistexamples.php [-u templatesurl] [-d yuidistroot] [-t templatesroot]\n";

	echo "\n\ntemplatesurl\n\tThe absolute URL for the templates folder"
		."\n\ton a server hosting the yui build.\n\tDefaults to 'http://localhost/templates'";
	echo "\n\nyuidistroot\n\tThe path to the base directory for the yuidist package."
		."\n\tNeeds to be the 'real' non-symlinked path, due to php limitations with fopen."
		."\n\tDefaults to 'yuidist' in the same folder as gendistexamples.php";
	echo "\n\ntemplatesroot\n\tThe path to the templates folder."
		."\n\tCan be relative to gendistexamples.php.\n\tDefaults to '../../../../templates'\n\n";

	echo "\n\nNOTE: All paths should be specified without trailing slashes.";
} 

?>
