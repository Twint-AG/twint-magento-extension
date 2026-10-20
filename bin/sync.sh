#!/usr/bin/env bash

set -euo pipefail

RELEASE_HOST=github.com
RELEASE_REPOSITORY=git@${RELEASE_HOST}:Twint-AG/twint-magento-extension.git

# Internal-only paths that must never reach the public GitHub mirror. These are
# also `export-ignore`d in .gitattributes (so they're absent from Composer dist
# archives), but a plain `git push` ignores export-ignore — hence the scrub below.
EXCLUDE_PATHS=(devbox docs zinfra infra Test)

echo "Syncing release ${CI_COMMIT_TAG}"

export GIT_COMMITTER_NAME="TWINT Release Bot"
export GIT_COMMITTER_EMAIL="plugin@twint.ch"
export GIT_AUTHOR_NAME="TWINT Release Bot"
export GIT_AUTHOR_EMAIL="plugin@twint.ch"

mkdir -p ~/.ssh
chmod 400 "${TWINT_GITHUB_DEPLOY_KEY}"
ssh-keyscan ${RELEASE_HOST} >> ~/.ssh/known_hosts

# Build a scrubbed commit to mirror: take HEAD's tree, drop the internal paths,
# and wrap it in a *parentless* commit. Parentless (orphan) is important — a
# commit that kept HEAD as a parent would still carry the internal files in the
# reachable history that gets pushed. A throwaway index keeps the real index/
# working tree untouched.
echo "Stripping internal paths from the GitHub mirror: ${EXCLUDE_PATHS[*]}"
TMP_INDEX="$(mktemp)"
GIT_INDEX_FILE="$TMP_INDEX" git read-tree HEAD
GIT_INDEX_FILE="$TMP_INDEX" git rm -r --cached --quiet --ignore-unmatch "${EXCLUDE_PATHS[@]}"
SCRUBBED_TREE="$(GIT_INDEX_FILE="$TMP_INDEX" git write-tree)"
rm -f "$TMP_INDEX"
SCRUBBED_COMMIT="$(git commit-tree "$SCRUBBED_TREE" -m "Release ${CI_COMMIT_TAG}")"

GIT_SSH_COMMAND="ssh -i ${TWINT_GITHUB_DEPLOY_KEY}" git push --force "${RELEASE_REPOSITORY}" \
  "${SCRUBBED_COMMIT}:refs/heads/latest" \
  "${SCRUBBED_COMMIT}:refs/tags/${CI_COMMIT_TAG}"
