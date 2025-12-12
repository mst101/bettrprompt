#!/bin/bash

# Script to install Caddy's local CA certificate to system trust store
# This fixes "Your connection is not private" warnings on https://app.localhost

set -e

echo "🔐 Installing Caddy Local CA Certificate..."
echo ""

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

# Detect OS and install certificate
if [ "$(uname)" == "Darwin" ]; then
    # macOS
    echo "🍎 Detected macOS - Installing to system keychain..."
    sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/caddy-local-ca.crt
    echo "✅ Certificate installed to macOS System Keychain"

elif [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    # Linux
    echo "🐧 Detected Linux - Installing to system trust store..."

    # Determine certificate directory based on distribution
    if [ -d "/usr/local/share/ca-certificates" ]; then
        # Debian/Ubuntu
        sudo cp /tmp/caddy-local-ca.crt /usr/local/share/ca-certificates/caddy-local-ca.crt
        sudo update-ca-certificates
        echo "✅ Certificate installed to /usr/local/share/ca-certificates/"
    elif [ -d "/etc/pki/ca-trust/source/anchors" ]; then
        # RHEL/CentOS/Fedora
        sudo cp /tmp/caddy-local-ca.crt /etc/pki/ca-trust/source/anchors/caddy-local-ca.crt
        sudo update-ca-trust
        echo "✅ Certificate installed to /etc/pki/ca-trust/source/anchors/"
    else
        echo "❌ Error: Unable to detect certificate directory for your Linux distribution."
        echo "   Please install /tmp/caddy-local-ca.crt manually to your system's trust store."
        exit 1
    fi

    # Also install for Chrome/Chromium (NSS database)
    if command -v certutil &> /dev/null; then
        echo ""
        echo "🌐 Installing certificate for Chrome/Chromium..."

        # Find Chrome/Chromium profile directories
        CHROME_DIRS=(
            "$HOME/.pki/nssdb"
            "$HOME/.mozilla/firefox/*.default*"
        )

        for dir in "${CHROME_DIRS[@]}"; do
            if [ -d "$dir" ] || compgen -G "$dir" > /dev/null; then
                for profile in $dir; do
                    if [ -d "$profile" ]; then
                        certutil -A -n "Caddy Local CA" -t "C,," -i /tmp/caddy-local-ca.crt -d sql:"$profile" 2>/dev/null || true
                        echo "   ✅ Installed to $profile"
                    fi
                done
            fi
        done
    else
        echo ""
        echo "ℹ️  Note: certutil not found. Chrome may still show warnings."
        echo "   Install libnss3-tools package to add certificate to Chrome's trust store:"
        echo "   sudo apt install libnss3-tools  # Debian/Ubuntu"
        echo "   sudo dnf install nss-tools      # Fedora"
    fi

else
    echo "❌ Error: Unsupported operating system."
    exit 1
fi

echo ""
echo "✨ Installation complete!"
echo ""
echo "⚠️  IMPORTANT: You must restart Chrome/Chromium for changes to take effect:"
echo "   1. Close ALL Chrome/Chromium windows"
echo "   2. Open Chrome again"
echo "   3. Visit https://app.localhost"
echo ""
echo "If you still see warnings after restarting Chrome:"
echo "   1. Visit chrome://settings/certificates"
echo "   2. Go to 'Authorities' tab"
echo "   3. Look for 'Caddy Local CA' - it should be listed"
echo "   4. If not, you may need to import /tmp/caddy-local-ca.crt manually"
echo ""
