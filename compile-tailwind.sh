#!/bin/bash

# Check if the --minify argument is passed
MINIFY=""

if [[ "$1" == "--minify" ]]; then
  MINIFY="--minify"
fi

# This script compiles Tailwind CSS using the specified configuration and input/output files.

npx tailwindcss -c tailwindcss-config.js -i view/frontend/web/css/tailwind/input.css -o view/frontend/web/css/tailwind/twint.css $MINIFY

echo "Tailwind CSS compilation completed."
