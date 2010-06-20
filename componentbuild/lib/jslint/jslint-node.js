/**
 * Javascript Shell Script to Load and JSLint js files through Rhino Javascript Shell
 * The jslint source file is expected as the first argument, followed by the list of JS files to JSLint
 * 
 * e.g. 
 * 	java -j js.jar /tools/fulljslint.js testFile1.js testFile2.js testFile3.js
 **/

JSLINT = require("./fulljslint").JSLINT;

(function(){ // Just to keep stuff seperate from JSLINT code

    var PORT = parseInt(process.argv[2]) || 8081;

	var OPTS = {
		browser : true,
		//laxLineEnd : true,
        undef: true,
		newcap: false
	};
	
	function test(js) {
        if (!js) throw new Error("No JS provided");

        var body = "";
		var success = JSLINT(js, OPTS);
		if (success) {
			return "OK";
		} else {
			for (var i=0; i < JSLINT.errors.length; ++i) {
				var e = JSLINT.errors[i];
				if (e) {
					body += ("\t" + e.line + ", " + e.character + ": " + e.reason + "\n\t" + clean(e.evidence) + "\n");
				}
			}
            body += "\n";
            return body;
		}
	}
	
	function clean(str) {
		var trimmed = "";
		if (str) {
			if(str.length <= 500) {
				trimmed = str.replace(/^\s*(\S*(\s+\S+)*)\s*$/, "$1");
			} else {
				trimmed = "[Code Evidence Omitted: Greater than 500 chars]";
			}
		}
		return trimmed;
	}

    var qs = require("querystring");
    var fs = require("fs");
    var EventEmitter = require("events").EventEmitter;

    require("http").createServer(function (req, res) {
        var data = "";
        req.addListener("data", function (chunk) {
            data += chunk;
        });
        var proc = new EventEmitter();
        proc.addListener("end", function (code, body, die) {
            res.writeHead(code, {"Content-type" : "text/plain"});
            res.end(body);
            if (die) process.exit(0);
        });
        req.addListener("end", function () {
            var code = 200, body;
            var die = "/kill" === req.url;
            if (die) body = "Goodbye.";
            else if (req.method === "POST") {
                var files = qs.parse(data)["files"].split("' '");
                var results = [];
                files.forEach(function (file) {
                    fs.readFile(file, function (err, data) {
                        data = data.toString("utf8");
                        try {
                            results.push(test(data));
                        } catch (ex) {
                            proc.emit("end", 500, err.message);
                        }
                        if (results.length == files.length) proc.emit("end", code, results.join("\n"));
                    });
                });
            } else {
                body = "Ready.";
            }
            if (body) proc.emit("end", code, body, die);
        });
    }).listen(PORT, "127.0.0.1");

    require("sys").puts("Server started on port " + PORT);

})();
	
