rm -rf yahoo
cvs co -P yahoo/presentation/3.x/README yahoo/presentation/3.x/build yahoo/presentation/3.x/src yahoo/presentation/3.x/templates yahoo/presentation/3.x/tests  yahoo/presentation/3.x/site yahoo/presentation/tools
#cvs co -P yahoo/presentation/3.x/README yahoo/presentation/3.x/build yahoo/presentation/3.x/src yahoo/presentation/3.x/tests  yahoo/presentation/tools yahoo/presentation/templates
echo "3.0.0" > version.txt
