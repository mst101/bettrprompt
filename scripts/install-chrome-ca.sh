#!/bin/bash

# Script to install Caddy's CA certificate specifically for Chrome/Chromium
# This fixes "Your connection is not private" warnings on https://app.localhost

set -e

echo "🔐 Installing Caddy CA Certificate for Chrome/Chromium..."
echo ""

# Check if certutil is installed
if ! command -v certutil &> /dev/null; then
    echo "❌ Error: certutil not found."
    echo ""
    echo "Please install libnss3-tools first:"
    echo "   sudo apt install libnss3-tools"
    echo ""
    exit 1
fi

# Check if Sail is running
if ! ./vendor/bin/sail ps | grep -q caddy; then
    echo "❌ Error: Caddy container is not running."
    echo "   Please start the application first with: ./vendor/bin/sail up -d"
    exit 1
fi

# Export the CA certificate from Caddy container
echo "📦 Extracting CA certificate from Caddy container..."
./vendor/bin/sail exec caddy cat /data/caddy/pki/authorities/local/root.crt > /tmp/caddy-local-ca.crt

if [ ! -s /tmp/caddy-local-ca.crt ]; then
    echo "❌ Error: Failed to extract CA certificate from Caddy."
    exit 1
fi

echo "✅ CA certificate extracted to /tmp/caddy-local-ca.crt"
echo ""

# Get certificate details
CERT_NAME=$(openssl x509 -noout -subject -in /tmp/caddy-local-ca.crt | sed 's/subject=CN = //')
echo "📜 Certificate: $CERT_NAME"
echo ""

# Install to Chrome/Chromium NSS databases
echo "🌐 Installing certificate for Chrome/Chromium..."

# Find all NSS database directories
NSS_DBS=(
    "$HOME/.pki/nssdb"
    "$HOME/.pki/chromium/nssdb"
    "$HOME/snap/chromium/current/.pki/nssdb"
)

INSTALLED=0

for db_path in "${NSS_DBS[@]}"; do
    if [ -d "$db_path" ]; then
        echo "   Installing to $db_path..."

        # Remove existing certificate if present
        certutil -D -n "$CERT_NAME" -d sql:"$db_path" 2>/dev/null || true

        # Add the certificate
        if certutil -A -n "$CERT_NAME" -t "C,," -i /tmp/caddy-local-ca.crt -d sql:"$db_path" 2>/dev/null; then
            echo "   ✅ Installed to $db_path"
            INSTALLED=$((INSTALLED + 1))
        else
            echo "   ⚠️  Failed to install to $db_path"
        fi
    fi
done

if [ $INSTALLED -eq 0 ]; then
    echo ""
    echo "❌ Error: No Chrome/Chromium NSS databases found."
    echo ""
    echo "Searched in:"
    for db_path in "${NSS_DBS[@]}"; do
        echo "   - $db_path"
    done
    echo ""
    echo "Make sure Chrome/Chromium has been run at least once."
    exit 1
fi

echo ""
echo "✨ Installation complete! Installed to $INSTALLED database(s)."
echo ""
echo "⚠️  IMPORTANT: You MUST restart Chrome/Chromium for changes to take effect:"
echo ""
echo "   1. Completely close ALL Chrome/Chromium windows"
echo "      (Check with: ps aux | grep chrome)"
echo ""
echo "   2. Kill any remaining processes:"
echo "      pkill -f chrome"
echo "      pkill -f chromium"
echo ""
echo "   3. Wait 2-3 seconds, then open Chrome again"
echo ""
echo "   4. Visit https://app.localhost"
echo ""
echo "To verify the certificate is installed, run:"
echo "   certutil -L -d sql:\$HOME/.pki/nssdb | grep Caddy"
echo ""
