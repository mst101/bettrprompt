# MaxMind GeoIP Setup Guide

This guide walks you through setting up MaxMind GeoLite2 database for IP geolocation functionality.

## Prerequisites

- MaxMind account (free GeoLite2 signup at https://www.maxmind.com/en/geolite2/signup)
- Linux/Unix command line access or Windows with curl installed
- PHP with cURL extension (usually enabled by default)

## Step 1: Create MaxMind Account & Get Credentials

1. **Sign up** at https://www.maxmind.com/en/geolite2/signup (free)
2. **Verify your email** and log in
3. **Get your Account ID**:
   - Log in to MaxMind
   - Go to "Account" menu
   - Note your Account ID (12-digit number)
4. **Generate a License Key**:
   - In MaxMind account, go to "License Keys" under "Account"
   - Click "Generate new license key"
   - Select "GeoLite2" as the service type
   - Click "Confirm"
   - **Copy the license key** (you'll only see it once!)

## Step 2: Configure Environment Variables

Add these to your `.env` file:

```env
# MaxMind GeoIP Configuration
GEOIP_ENABLED=true
MAXMIND_ACCOUNT_ID=your_12_digit_account_id
MAXMIND_LICENSE_KEY=your_license_key_here
```

Replace `your_12_digit_account_id` and `your_license_key_here` with your actual credentials.

### Example

```env
GEOIP_ENABLED=true
MAXMIND_ACCOUNT_ID=123456789012
MAXMIND_LICENSE_KEY=abcdefghijklmnop1234567890
```

## Step 3: Create Directory for Database

The database will be stored in `storage/app/geoip/`. Create this directory:

```bash
mkdir -p storage/app/geoip
chmod 755 storage/app/geoip
```

## Step 4: Download Initial Database

### Option A: Using Laravel Command (Recommended)

```bash
./vendor/bin/sail artisan geoip:update
```

Or if not using Sail:

```bash
php artisan geoip:update
```

This command will:
- Verify your MaxMind credentials
- Download the GeoLite2-City database
- Extract it to `storage/app/geoip/`
- Set proper file permissions

**Expected output:**
```
🌍 MaxMind GeoIP Database Update

📥 Downloading GeoLite2 City database...
📦 Extracting database...
✓ Database downloaded and extracted successfully
  Location: /path/to/storage/app/geoip/GeoLite2-City.mmdb
  Size: 85.34 MB
```

### Option B: Manual Download

If the command fails, you can download manually:

1. **Get your download URL**:
   ```
   https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=YOUR_LICENSE_KEY&suffix=tar.gz
   ```

2. **Download and extract**:
   ```bash
   cd storage/app/geoip
   curl -o GeoLite2-City.tar.gz "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=YOUR_LICENSE_KEY&suffix=tar.gz"
   tar -xzf GeoLite2-City.tar.gz
   mv GeoLite2-City_*/GeoLite2-City.mmdb .
   rm -rf GeoLite2-City_* GeoLite2-City.tar.gz
   ```

3. **Verify**:
   ```bash
   ls -lh storage/app/geoip/GeoLite2-City.mmdb
   ```

## Step 5: Automatic Database Updates

The database is automatically updated **every Monday at 2:00 AM** (server timezone).

### How It Works

- **Scheduler**: Laravel task scheduler runs the `geoip:update` command
- **Frequency**: Weekly, every Monday at 02:00 UTC (or your server timezone)
- **Overlap Prevention**: The `withoutOverlapping()` prevents multiple simultaneous downloads
- **Logging**: Success/failure logged to `storage/logs/laravel.log`

### Make Sure Scheduler is Running

**On production/staging** (cPanel, Plesk, etc.):

Add a cron job that runs Laravel scheduler every minute:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

**On Laravel Sail**:

The scheduler runs automatically with the `composer dev` command.

### Manual Trigger (Optional)

You can manually update the database anytime:

```bash
# Normal update (only if older than 7 days)
./vendor/bin/sail artisan geoip:update

# Force re-download
./vendor/bin/sail artisan geoip:update --force
```

## Monitoring Updates

### Check Last Update

```bash
ls -l storage/app/geoip/GeoLite2-City.mmdb
```

### View Logs

```bash
tail -f storage/logs/laravel.log | grep GeoIP
```

Or in your application:

```php
// In Laravel Tinker or a route
\Illuminate\Support\Facades\Log::info('Current GeoIP database size: ' .
    filesize(storage_path('app/geoip/GeoLite2-City.mmdb')));
```

## Troubleshooting

### Database Download Fails

**Error: "HTTP Error 401"**
- Your MaxMind credentials are incorrect
- Verify `MAXMIND_ACCOUNT_ID` and `MAXMIND_LICENSE_KEY` in `.env`

**Error: "Failed to extract database"**
- PHP might not have PharData support
- Ensure `tar` command is available: `which tar`
- Try manual extraction method above

**Error: "Database file not found after extraction"**
- The file structure from MaxMind changed
- Try manual extraction to see what's happening
- Check permissions on `storage/app/geoip/`

### Permissions Issues

If you get permission errors:

```bash
# Make directory writable
chmod 755 storage/app/geoip

# Make database readable
chmod 644 storage/app/geoip/GeoLite2-City.mmdb

# If using web server user (www-data, nginx, etc):
chown -R www-data:www-data storage/app/geoip
chmod 755 storage/app/geoip
chmod 644 storage/app/geoip/GeoLite2-City.mmdb
```

### Scheduler Not Running

Check if scheduler is running:

```bash
# Check cron jobs
crontab -l

# Should show:
# * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

If not running on production, add it to your hosting control panel's cron job section.

### Test Geolocation Manually

```php
// In Laravel Tinker
php artisan tinker

$service = new \App\Services\GeolocationService();
$location = $service->lookupIp('8.8.8.8');
dd($location);
```

Should show:
```
LocationData {
  +countryCode: "US"
  +countryName: "United States"
  +region: "California"
  +city: "Mountain View"
  +timezone: "America/Los_Angeles"
  +currencyCode: "USD"
  +languageCode: "en"
  ...
}
```

## Database Size & Performance

- **Database size**: ~85 MB
- **Memory usage**: ~30 MB when loaded
- **Lookup time**: ~1-2 ms per IP (with caching)
- **Cache**: 30-day TTL (configurable in `config/geoip.php`)

## Security Notes

- **License key**: Keep your `MAXMIND_LICENSE_KEY` secret (it's in `.env` which should be in `.gitignore`)
- **Database**: The MMDB file itself is not sensitive
- **IP lookups**: Private IPs (127.0.0.1, 192.168.x.x, etc.) are automatically skipped
- **Coordinates**: Automatically anonymised to ~1km accuracy for privacy

## MaxMind Account Features

### Free Tier (GeoLite2)
- ✅ 7 days of update history
- ✅ Automatic weekly updates
- ✅ Accurate to city level
- ✅ No credit card required
- ✅ ~99.5% accuracy

### Paid Tiers (GeoIP2)
If you need higher accuracy or more frequent updates:
- City+: ~99.8% accuracy
- Precision: ~99.9% accuracy
- Monthly billing available

## Next Steps

1. ✅ Add MaxMind credentials to `.env`
2. ✅ Create `storage/app/geoip/` directory
3. ✅ Run `php artisan geoip:update`
4. ✅ Verify cron job is running: `crontab -l`
5. ✅ Test with: `php artisan tinker` → `$service = new \App\Services\GeolocationService(); $service->lookupIp('8.8.8.8')`

## Support

- MaxMind Help: https://support.maxmind.com/hc/en-us
- GeoLite2 License Agreement: https://www.maxmind.com/en/service/geolite2-eula
- PHP GeoIP2 Library: https://github.com/maxmind/GeoIP2-php

## Environment Variables Reference

Add to your `.env.example` (don't commit `.env`):

```env
# MaxMind GeoIP Configuration
GEOIP_ENABLED=true
MAXMIND_ACCOUNT_ID=
MAXMIND_LICENSE_KEY=
```

---

**Last Updated**: 2025-12-05
**Database Format**: MMDB (MaxMind DB)
**Update Frequency**: Weekly (Mondays at 02:00 AM)
