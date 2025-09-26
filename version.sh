#!/bin/bash
# ./version.sh
# ./version.sh minor
# ./version.sh major

VERSION_FILE="VERSION"

get_current_version() {
    cat "$VERSION_FILE"
}

set_new_version() {
    echo "$1" > "$VERSION_FILE"
}

bump_version() {
    IFS='.' read -r major minor patch <<< "$1"

    case $2 in
        major)
            ((major++))
            minor=0
            patch=0
            ;;
        minor)
            ((minor++))
            patch=0
            ;;
        patch|*)
            ((patch++))
            ;;
    esac

    echo "$major.$minor.$patch"
}

if [[ ! -f "$VERSION_FILE" ]]; then
    echo "0.0.0" > "$VERSION_FILE"
fi

CURRENT_VERSION=$(get_current_version)
BUMP_TYPE=${1:-patch}
NEW_VERSION=$(bump_version "$CURRENT_VERSION" "$BUMP_TYPE")

echo "Versão atual: $CURRENT_VERSION"
echo "Nova versão:  $NEW_VERSION"
read -p "Confirmar e aplicar? [s/n]: " confirm

if [[ "$confirm" =~ ^[sS]$ ]]; then
    set_new_version "$NEW_VERSION"
    git add .
    git commit -m "Versão $NEW_VERSION"
    git tag -a $NEW_VERSION -m "Versão $NEW_VERSION"
    git push
    git push --tags
    echo "✅ Versão $NEW_VERSION publicada com sucesso!"
else
    echo "❌ Operação cancelada."
fi
