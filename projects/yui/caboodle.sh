#!/bin/sh

##############################################################################

# The location of your yuidoc install
# yuidoc_home=yahoo/presentation/tools/yuidoc
yuidoc_home=~/www/yuidoc/yuidoc

basedir=yahoo/presentation/2.x
util=$basedir/util
widget=$basedir/widget

# The location of the files to parse.  Parses subdirectories, but will fail if
# there are duplicate file names in these directories.
parser_in="$util/animation/src/js \
          $widget/autocomplete/src/js \
          $widget/button/src/js \
          $widget/calendar/src/js \
          $util/connection/src/js \
          $widget/container/src/js \
          $util/datasource/src/js \
          $widget/datatable/src/js \
          $util/dom/src/js \
          $util/dragdrop/src/js \
          $util/element/src/js \
          $util/event/src/js \
          $util/history/src/js \
          $widget/logger/src/js \
          $widget/menu/src/js \
          $widget/slider/src/js \
          $widget/tabview/src/js \
          $widget/treeview/src/js \
          $util/yahoo/src/js"

# The location to output the parser data.  This output is a file containing a 
# json string, and copies of the parsed files.
parser_out=tmp/yuidoc_tmp

# The directory to put the html file outputted by the generator
generator_out=tmp/docs

# The location of the template files.  Any subdirectories here will be copied
# verbatim to the destination directory.
template=$yuidoc_home/template

version=`cat version.internal.txt`

##############################################################################

$yuidoc_home/bin/yuidoc.py $parser_in -p $parser_out -o $generator_out -t $template -v $version -s

