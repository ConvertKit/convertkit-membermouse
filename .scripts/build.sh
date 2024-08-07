# Build ACTIONS-FILTERS.md
php create-actions-filters-docs.php

# Generate .pot file
php -n $(which wp) i18n make-pot ../ ../languages/convertkit-mm.pot

# Build ZIP file
rm ../convertkit-membermouse.zip
cd .. && zip -r convertkit-membermouse.zip . -x "*.git*" -x ".scripts/*" -x ".wordpress-org/*" -x "tests/*" -x "vendor/*" -x "*.distignore" -x "*.env.*" -x "*codeception.*" -x "composer.json" -x "composer.lock" -x "*.md" -x "log.txt" -x "phpcs.xml" -x "phpstan.neon" -x "phpstan.neon.dist" -x "phpstan.neon.example" -x "*.DS_Store"