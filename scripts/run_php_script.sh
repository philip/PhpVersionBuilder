#!/usr/bin/env bash
PHP_SCRIPT="test.php"
PHP_SCRIPT_OUT_BASEDIR="output/"
USAGE="Usage example: ${0} test.php output/"

# Allow optional PHP file argument
if [ $1 ]; then
	if [ -f $1 ]; then
		PHP_SCRIPT=$1
	fi
fi
# Create if doesn't exist?
if [ $2 ]; then
	if [ -d $1 ]; then
		PHP_SCRIPT_OUT_BASEDIR=$2
	fi
fi

# Check if environment is setup
if [ ! -f $PHP_SCRIPT ]; then
	echo $USAGE
	echo "Failed to find PHP Script at [$PHP_SCRIPT]. Quitting."
	exit
fi
if [ ! -d $PHP_SCRIPT_OUT_BASEDIR ]; then
	echo $USAGE
	echo "Failed to find PHP Script directory to store output. Tried [$PHP_SCRIPT_OUT_BASEDIR]."
	exit
fi

# Execute PHP script
# Allow option to view results instead of file storage?
count=0
for f in `ls -d php*`
do
	echo "Processing $f"

	if [ ! -f "$f/bin/php" ]; then
		echo "No CLI built for [${f}/bin/php]"
		continue;
	fi

	# Ensure the CLI was built
	out=`$f/bin/php -v`
	if ! grep -q cli <<<$out; then
		echo "CLI built but not working for [${f}/bin/php]"
		continue
	fi

	PATH_OUT="${PHP_SCRIPT_OUT_BASEDIR}${f}.txt"
	
	# Finally execute the Script
	`$f/bin/php $PHP_SCRIPT > $PATH_OUT`

	if [ -f $PATH_OUT ]; then
		count=`expr $count + 1`
		echo -e "\tINFO: Creates [${PATH_OUT}] for [${f}] with probable success."
	else
		echo -e "\tERROR: Could not create [${PATH_OUT}] for [${f}]. Fail."
	fi
done

if [ "$count" -gt "0" ]; then
	echo "========================================================"	
	echo "Executed the script            ${count} times"
	echo "Executed the following script: ${PHP_SCRIPT}"
	echo "Output went in here:           ${PHP_SCRIPT_OUT_BASEDIR}"
fi
