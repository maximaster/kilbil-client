set dotenv-load := false

# Rector and PHPStan compile a DI container keyed by config hash into $TMPDIR;
# scope it per checkout so the cache does not outlive a worktree.
export TMPDIR := justfile_directory() / "build/cache/tmp"

# List available recipes
default:
    @just --list

[private]
tmpdir:
    @mkdir -p "{{ TMPDIR }}"

# Full local gate
check: tmpdir lint test

# Run all linters
lint: tmpdir lint-cs-fixer lint-phpstan lint-rector

lint-cs-fixer: tmpdir
    php-cs-fixer fix --diff --verbose --dry-run

lint-phpstan: tmpdir
    phpstan analyse --memory-limit=512M

lint-rector: tmpdir
    rector --dry-run

# Regenerate the committed tree. poagen is not a dependency of this package;
# point POAGEN at a generator checkout when running this.
generate:
    ${POAGEN:-poagen} generate poagen.php

format: tmpdir
    php-cs-fixer fix --diff --verbose

format-rector: tmpdir
    rector

test: tmpdir
    phpunit
