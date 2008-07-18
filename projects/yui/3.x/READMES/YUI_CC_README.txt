Dropping these files into a stock CruiseControl distribution will set
up the yui automated build.

http://cruisecontrol.sourceforge.net/

One the files are in place, you must do an initial checkout of the build dir:

cd projects/yui
cvs co -P yahoo/presentation/2.x

Then run cruisecontrol.sh to start the server.
