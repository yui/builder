#!/bin/sh

##############################################################################

# The location of your yuidoc install
yuidoc_home=~/dev/yahoo/presentation/tools/yuidoc
# yuidoc_home=~/www/yuidoc/yuidoc
# yuidoc_home=yahoo/presentation/tools/yuidoc

basedir=yahoo/presentation/2.x
util=$basedir/util
widget=$basedir/widget
tool=$basedir/tool
src=$basedir/src


# The location of the files to parse.  Parses subdirectories, but will fail if
# there are duplicate file names in these directories.
parser_in="$src/animation \
          $src/autocomplete \
          $src/button \
          $src/calendar \
          $src/carousel \
          $src/charts \
          $src/colorpicker \
          $src/connection \
          $src/container \
          $src/cookie \
          $src/datastore \
          $src/datasource \
          $src/datatable \
          $src/dom \
          $src/dragdrop \
          $src/editor \
          $src/element \
          $src/event \
          $src/get \
          $src/history \
          $src/imagecropper \
          $src/imageloader \
          $src/json \
          $src/layout \
          $src/logger \
          $src/menu \
          $src/paginator \
          $src/profiler \
          $src/profilerviewer \
          $src/resize \
          $src/selector \
          $src/slider \
          $src/tabview \
          $src/treeview \
          $src/uploader \
          $src/yahoo \
          $src/yuiloader \
          $src/yuitest"

          # $src/datasource \
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

$yuidoc_home/bin/yuidoc.py $parser_in -p $parser_out -o $generator_out -t $template -v $version -s $*

