#!/bin/bash

# Check if the --minify argument is passed
MINIFY=""

if [[ "$1" == "--minify" ]]; then
  MINIFY="--minify"
fi

# This script compiles Tailwind CSS using the specified configuration and input/output files.
sass view/frontend/web/css/source/modal.scss view/frontend/web/css/source/modal.css
sass view/frontend/web/css/source/button.scss view/frontend/web/css/source/button.css
sass view/frontend/web/css/source/loader.scss view/frontend/web/css/source/loader.css

npx tailwindcss -c tailwindcss-config.js -i view/frontend/web/css/source/input.css -o view/frontend/web/css/twint.css $MINIFY

# Remove on fly files
cd view/frontend/web/css/source && rm -rf *.css.map  modal.css button.css loader.css
cd .. && rm -rf *.css.map

echo "Tailwind CSS compilation completed."
