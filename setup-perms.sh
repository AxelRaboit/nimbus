#!/bin/bash

# To be run with: sudo ./setup-perms.sh
set -e

if [ "$EUID" -ne 0 ]; then
	echo "This script must be executed with sudo"
	exit 1
fi

APP_USER="axel"
WEB_GROUP="www-data"

# Create necessary directories
create_directories() {
	echo "Creating directories..."
	mkdir -p var/cache
	mkdir -p var/log
	mkdir -p var/uploads/tus_tmp
	mkdir -p var/uploads/transfers
	mkdir -p var/cache/tus
	mkdir -p public/build
	echo "Directories created!"
}

# Set permissions for code source (restrictive - not writable by www-data)
set_code_source_permissions() {
	echo "Setting code source permissions..."
	find . -type d \( -name node_modules -o -name .git -o -path ./var -o -path ./public/build \) -prune -o -type d -print0 2>/dev/null | \
		xargs -0 -r chown ${APP_USER}:${APP_USER} 2>/dev/null || true

	find . -type f \( -path ./node_modules -o -path ./.git -o -path ./var -o -path ./public/build \) -prune -o -type f -print0 2>/dev/null | \
		xargs -0 -r chown ${APP_USER}:${APP_USER} 2>/dev/null || true

	find . -type d \( -name node_modules -o -name vendor -o -name .git -o -path ./var -o -path ./public/build \) -prune -o -type d -print0 2>/dev/null | \
		xargs -0 -r chmod 755 2>/dev/null || true

	find . -type f \( -path ./node_modules -o -path ./vendor -o -path ./.git -o -path ./var -o -path ./public/build \) -prune -o -type f -print0 2>/dev/null | \
		xargs -0 -r chmod 644 2>/dev/null || true

	find . -type f -name "*.sh" \( -path ./node_modules -o -path ./vendor -o -path ./.git \) -prune -o -name "*.sh" -print0 2>/dev/null | \
		xargs -0 -r chmod 755 2>/dev/null || true

	# Restrict .env permissions
	if [ -f ".env" ]; then
		chown ${APP_USER}:${WEB_GROUP} .env 2>/dev/null || true
		chmod 640 .env 2>/dev/null || true
		echo ".env permissions restricted (640, ${APP_USER}:${WEB_GROUP})"
	fi

	echo "Code source permissions set!"
}

# Set permissions for writable directories (writable by www-data)
set_writable_directories_permissions() {
	echo "Setting writable directories permissions..."

	# var/ must be writable by both deploy user and web server
	chown -R ${APP_USER}:${WEB_GROUP} var/ 2>/dev/null || true
	chmod -R 2775 var/ 2>/dev/null || true

	# public/build/ owned by deploy user but readable by web server
	chown -R ${APP_USER}:${WEB_GROUP} public/build 2>/dev/null || true
	chmod -R 2775 public/build 2>/dev/null || true

	echo "Writable directories permissions set!"
}

# Set permissions for SSH key (required for git pull)
set_ssh_key_permissions() {
	SSH_KEY="/home/${APP_USER}/.ssh/id_rsa"
	if [ -f "$SSH_KEY" ]; then
		chown ${APP_USER}:${APP_USER} "$SSH_KEY" 2>/dev/null || true
		chmod 600 "$SSH_KEY" 2>/dev/null || true
		echo "SSH key permissions set: $SSH_KEY (600, ${APP_USER}:${APP_USER})"
	fi
}

# Ensure .git directory ownership is correct
set_git_directory_permissions() {
	if [ -d .git ]; then
		chown -R ${APP_USER}:${APP_USER} .git
		chown ${APP_USER}:${APP_USER} .
		echo ".git and repository root ownership set to ${APP_USER}:${APP_USER}"
	fi
}

# Set code source permissions based on FULL_PERMS mode
set_code_source_permissions_if_needed() {
	if [ -n "${FULL_PERMS}" ] && [ "${FULL_PERMS}" = "1" ]; then
		echo "Using FULL permissions mode (slower but more thorough)..."
		set_code_source_permissions
	else
		echo "Using FAST permissions mode (writable directories only)..."
	fi
}

# Main execution
create_directories
set_code_source_permissions_if_needed
set_writable_directories_permissions

# Restrict .env permissions (always, regardless of FULL_PERMS)
if [ -f ".env" ]; then
	chown ${APP_USER}:${WEB_GROUP} .env 2>/dev/null || true
	chmod 640 .env 2>/dev/null || true
	echo ".env permissions restricted (640, ${APP_USER}:${WEB_GROUP})"
fi

set_ssh_key_permissions
set_git_directory_permissions

echo "All folders and permissions are set!"
