## Status ##
This is old and unmaintained. Do not use or look at this.

## What it does ##
- Finds PHP downloads, based on minimum PHP version desires
- Extracts said downloads
- Builds each PHP based on optional [per branch] configure options
- Executes PHP code, and yields results as plain text or colored HTML (based on output)

## Usage ##
1. Edit configure options within inc/config.php
2. Execute run.php
3. Execute execute_php.php to test PHP code against each version
-  Example: ./execute_php.php scripts/out/version_minor.php

## Requirements ##
- PHP
- tar with gzip support (gets executed in shell)
- allow_url_fopen enabled
- Note: system checks for these requirements

## What it doesn't do ##
- See the TODO

## What may be considered broken ##
- See the FIXME comments that litter the code, and also the TODO

## History ##
- Originally a simple downloader/extractor for scanning PHP code, build and execute components were later added.

## Notes ##
- This all takes a good amount of time and space
