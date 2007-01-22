/**
 * Javascript Shell Script to Load and JSLint js files through Rhino Javascript Shell
 * The jslint source file is expected as the first argument, followed by the list of JS files to JSLint
 * 
 * e.g. 
 * 	java -j js.jar /tools/fulljslint.js testFile1.js testFile2.js testFile3.js
 **/

var jslintsrc = arguments.splice(0,1);
var scripts = arguments;

load(jslintsrc);

(function(){ // Just to keep stuff seperate from JSLINT code

	var OPTS = {
		widget : true,
		browser : true,
		laxLineEnd : true
	};
	
	function test(jsFile) {
		
		print("Running JSLint on : " + jsFile);
		
		var js = readFile(jsFile);

		var success = JSLINT(js, OPTS);
		if (success) {
			print("- OK");
		} else {
			print(" \n");
			for (var i=0; i < JSLINT.errors.length; ++i) {
				var e = JSLINT.errors[i];
				if (e) {
					print("\t" + e.line + ", " + e.character + ": " + e.reason + "\n\t" + clean(e.evidence) + "\n");
				}
			}
		   print(" \n");
		}
	}
	
	function clean(str) {
		var trimmed = "";
		if (str) {
			trimmed = str.replace(/^\s*(\S*(\s+\S+)*)\s*$/, "$1");
		} 
		return trimmed; 
	}
	 
	function jslint(aScripts) {
		for (var i = 0; i < aScripts.length; ++i) {
			test(aScripts[i]);
	   }
	};
	
	jslint(scripts);

})();