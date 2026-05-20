#!/usr/bin/env bash

set -euo pipefail

# Source repository details for boilerplate download.
REPO_OWNER="vijayhardaha"
REPO_NAME="wordpress-plugin-boilerplate"
REPO_REF="${BOILERPLATE_REF:-HEAD}"

#
# Print a standardized section header block.
# Usage: print_header "Section Title"
#
print_header() {
  local title="$1"
  echo
  echo "============================================================"
  echo "==== ${title}"
  echo "============================================================"
}

#
# Print a standardized section footer block.
# Usage: print_footer "Status Message"
#
print_footer() {
  local message="$1"
  echo "============================================================"
  echo "==== ${message}"
  echo "============================================================"
  echo
}

#
# Print an error footer and exit with non-zero status.
# Usage: fail "Message"
#
fail() {
  print_footer "ERROR: $1"
  exit 1
}

#
# Assert that a command exists in PATH.
# Usage: require_cmd "curl"
#
require_cmd() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || fail "Required command not found: $cmd"
}

# Accept only letters and spaces, and require the first character to be a letter.
# Usage: validate_plugin_name "WP Mail Checker"
validate_plugin_name() {
  local input="$1"
  [[ "$input" =~ ^[A-Za-z][A-Za-z\ ]*$ ]]
}

# Convert human plugin name into required casing variants.
# Usage: change_case "WP Mail Checker" domain|function|class|constant|title
change_case() {
  local input="$1"
  local type="${2:-title}"

  input="$(echo "$input" | tr -s ' ' | sed 's/^ *//;s/ *$//')"

  IFS=' ' read -r -a words <<< "$input"

  local titled=()
  local i=0
  for w in "${words[@]}"; do
    local lower="$(echo "$w" | tr '[:upper:]' '[:lower:]')"
    if [[ $i -eq 0 && ( "$lower" == "wp" || "$lower" == "wc" ) ]]; then
      titled+=("$(echo "$lower" | tr '[:lower:]' '[:upper:]')")
    else
      local first="${lower:0:1}"
      local rest="${lower:1}"
      titled+=("$(echo "$first" | tr '[:lower:]' '[:upper:]')${rest}")
    fi
    ((i+=1))
  done

  local title_case="${titled[*]}"

  case "$type" in
    title)
      echo "$title_case"
      ;;
    class)
      echo "$title_case" | tr ' ' '_'
      ;;
    constant)
      echo "$title_case" | tr ' ' '_' | tr '[:lower:]' '[:upper:]'
      ;;
    domain)
      echo "$title_case" | tr '[:upper:]' '[:lower:]' | tr ' ' '-'
      ;;
    function)
      echo "$title_case" | tr '[:upper:]' '[:lower:]' | tr ' ' '_'
      ;;
    *)
      echo "$title_case"
      ;;
  esac
}

#
# Run full installer workflow:
# prompt -> validate -> download -> extract -> replace -> cleanup.
# Usage: main
#
main() {
  print_header "WordPress Plugin Boilerplate Installer"
  echo "This installer downloads the boilerplate from GitHub and prepares it."
  print_footer "Starting"

  require_cmd "tar"
  require_cmd "find"
  require_cmd "perl"

  # Need one HTTP client to download the archive.
  if ! command -v curl >/dev/null 2>&1 && ! command -v wget >/dev/null 2>&1; then
    fail "Install curl or wget to download boilerplate from GitHub."
  fi

  # Prompt until a valid plugin name is provided.
  local plugin_name=""
  while true; do
    read -r -p "What will be your Plugin name? " plugin_name
    plugin_name="$(echo "$plugin_name" | tr -s ' ' | sed 's/^ *//;s/ *$//')"

    if validate_plugin_name "$plugin_name"; then
      break
    fi

    echo "Please provide a valid plugin name. Example: WP Bulk Uploader"
  done

  # Build all naming formats used by placeholder replacements.
  local title_case class_case constant_case domain_case function_case
  title_case="$(change_case "$plugin_name" title)"
  class_case="$(change_case "$plugin_name" class)"
  constant_case="$(change_case "$plugin_name" constant)"
  domain_case="$(change_case "$plugin_name" domain)"
  function_case="$(change_case "$plugin_name" function)"

  print_header "Validated Plugin Name"
  echo "Plugin Name      : $plugin_name"
  echo "Directory / Slug : $domain_case"
  echo "Class Prefix     : $class_case"
  echo "Constant Prefix  : $constant_case"
  echo "Function Prefix  : $function_case"
  print_footer "Name formats generated"

  # Final plugin folder name uses the kebab-case slug.
  local target_dir="${PWD}/${domain_case}"

  print_header "Validating Target Directory"
  # Stop early if target directory already exists to avoid overwriting.
  [[ -e "$target_dir" ]] && fail "Target directory already exists: $target_dir"
  echo "Target directory is available: $target_dir"
  print_footer "Directory validation complete"

  print_header "Downloading Boilerplate"
  # Download and unpack in a temporary workspace.
  local tmp_dir archive_url archive_file
  tmp_dir="$(mktemp -d)"
  archive_url="https://codeload.github.com/${REPO_OWNER}/${REPO_NAME}/tar.gz/${REPO_REF}"
  archive_file="${tmp_dir}/boilerplate.tar.gz"

  echo "Repository : ${REPO_OWNER}/${REPO_NAME}"
  echo "Branch     : ${REPO_REF}"
  echo "Download   : ${archive_url}"

  # Prefer curl, fallback to wget.
  if command -v curl >/dev/null 2>&1; then
    curl -fsSL "$archive_url" -o "$archive_file" || fail "Failed to download archive via curl."
  else
    wget -qO "$archive_file" "$archive_url" || fail "Failed to download archive via wget."
  fi

  mkdir -p "$target_dir"
  tar -xzf "$archive_file" -C "$tmp_dir" || fail "Failed to extract archive."

  # Locate extracted root and copy into target plugin directory.
  local extracted_root
  extracted_root="$(find "$tmp_dir" -mindepth 1 -maxdepth 1 -type d -name "${REPO_NAME}-*" | head -n 1)"
  [[ -z "$extracted_root" ]] && fail "Extracted boilerplate directory not found."

  cp -R "$extracted_root"/. "$target_dir" || fail "Failed to copy boilerplate files."
  rm -rf "$tmp_dir"

  echo "Boilerplate extracted to: $target_dir"
  print_footer "Download complete"

  cd "$target_dir"

  print_header "Collecting Files"
  # Gather files that contain boilerplate placeholders.
  mapfile -t replace_files < <(
    find . \
      -type f \
      ! -path './.git/*' \
      ! -path './node_modules/*' \
      ! -path './vendor/*' \
      ! -path './dist/*' \
      ! -path './.build/*' \
      \( \
        -name '*custom-plugin*' -o \
        -path './bin/*' -o \
        -path './.github/workflows/*' -o \
        -path './includes/*' -o \
        -path './languages/*' -o \
        -name 'gulpfile.mjs' -o \
        -name 'package.json' -o \
        -name 'phpcs.xml' \
      \) \
      | sort
  )

  [[ ${#replace_files[@]} -eq 0 ]] && fail "Unable to find files for replacements."

  echo "Found ${#replace_files[@]} files to process."
  print_footer "File discovery complete"

  print_header "Renaming Paths"
  # Rename files/directories that include the default slug.
  mapfile -t rename_targets < <(find . -depth -name '*custom-plugin*' | sort)
  local rename_count=0
  for oldpath in "${rename_targets[@]}"; do
    local newpath
    newpath="${oldpath//custom-plugin/$domain_case}"
    if [[ "$oldpath" != "$newpath" ]]; then
      mv "$oldpath" "$newpath"
      ((rename_count+=1))
    fi
  done

  echo "Renamed ${rename_count} paths."
  print_footer "Path rename complete"

  print_header "Replacing File Content"
  local replaced_count=0
  # Replace all placeholder tokens in file content.
  for f in "${replace_files[@]}"; do
    local target
    target="${f//custom-plugin/$domain_case}"
    [[ -f "$target" ]] || continue

    perl -0pi -e "s/Custom Plugin/$title_case/g; s/Custom_Plugin/$class_case/g; s/CUSTOM_PLUGIN/$constant_case/g; s/custom-plugin/$domain_case/g; s/custom_plugin/$function_case/g" "$target"
    ((replaced_count+=1))
  done

  echo "Updated ${replaced_count} files."
  print_footer "Content replacement complete"

  print_header "Cleanup"
  # Cleanup scaffolding-only metadata from generated plugin.
  rm -f README.md bun.lock bun.lockb || true
  rm -rf .git || true
  echo "Removed: README.md, bun.lock, bun.lockb, .git (if present)."
  print_footer "Cleanup complete"

  print_header "Complete"
  echo "Your plugin boilerplate is ready at: $target_dir"
  echo "Next step: cd $domain_case && bun install && composer install"
  print_footer "Success"
# Cleanup: remove installer script after successful run
if [ -f "$0" ]; then
  rm -f "$0"
fi
}

main "$@"
