#!/bin/sh

cd ../alpha.geotales.io/src/
bash compile.sh
cd ../../deploy/

rm -rf build/
mkdir build

cd ../alpha.geotales.io/
cp -r assets/ \
	  lib/ \
	  main.css \
	  robots.txt \
	  *.php \
	  ../deploy/build/
