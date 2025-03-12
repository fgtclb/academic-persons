#!/usr/bin/env bash

composer_cleanup() {
    echo -e "💥 Cleanup folders"
    rm -Rf \
        .Build/vendor/* \
        .Build/var/* \
        .Build/bin/* \
        .Build/Web/typo3conf/ext/* \
        .Build/Web/typo3/* \
        .Build/Web/typo3temp/* \
        composer.lock
}

composer_update() {
    echo -e "🔥 Update to selected dependencies"
    composer install

    echo -e "🌊 Restore composer.json"
    git restore composer.json
}

update_v12() {
    echo -e "💪 Enforce TYPO3 v12"
    composer require --no-update \
        "typo3/minimal":"^12.4"
}

update_v13() {
    echo -e "💪 Enforce TYPO3 v13"
    composer require --no-update \
        "typo3/minimal":"^13.4"
}

case "$1" in
12)
    composer_cleanup
    update_v12
    composer_update
    ;;
13)
    composer_cleanup
    update_v13
    composer_update
    ;;
*)
    echo -e "🌀 Usage: ddev update-to (12|13)" >&2
    exit 0
    ;;
esac
