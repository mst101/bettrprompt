# Google OAuth Authentication Setup

This guide walks you through setting up Google OAuth authentication for AI Buddy, allowing users to sign in with their Google accounts.

## Prerequisites

- A Google account
- Access to the [Google Cloud Console](https://console.cloud.google.com/)
- Laravel Socialite package installed (covered in implementation steps)

## Google Cloud Console Setup

### 1. Create a New Project (or Select Existing)

1. Navigate to [Google Cloud Console](https://console.cloud.google.com/)
2. Click the project dropdown at the top of the page
3. Click **"New Project"**
4. Enter project details:
   - **Project name**: "AI Buddy" (or your preferred name)
   - **Organisation**: Leave as default or select your organisation
5. Click **"Create"**

### 2. Enable Google+ API

1. In the Google Cloud Console, ensure your project is selected
2. Navigate to **"APIs & Services" > "Library"** from the left menu
3. Search for **"Google+ API"**
4. Click on it and press **"Enable"**

**Note**: Google+ API is required for Socialite to fetch user profile information.

### 3. Configure OAuth Consent Screen

1. Navigate to **"APIs & Services" > "OAuth consent screen"**
2. Select **"External"** user type (unless you have a Google Workspace account)
3. Click **"Create"**
4. Fill in the required fields:

   **App information**:
   - **App name**: AI Buddy
   - **User support email**: Your email address
   - **App logo**: (Optional) Upload your application logo

   **App domain**:
   - **Application home page**: `https://yourdomain.com` (or `http://localhost` for local development)
   - **Application privacy policy link**: `https://yourdomain.com/privacy` (create this page)
   - **Application terms of service link**: `https://yourdomain.com/terms` (create this page)

   **Authorised domains**:
   - Add your production domain: `yourdomain.com`
   - For local development: Leave empty or add `localhost`

   **Developer contact information**:
   - Add your email address

5. Click **"Save and Continue"**

6. **Scopes**: Click "Add or Remove Scopes"
   - Add the following scopes:
     - `.../auth/userinfo.email`
     - `.../auth/userinfo.profile`
   - Click **"Update"**
   - Click **"Save and Continue"**

7. **Test users** (only for development):
   - Add your email address and any other test user emails
   - Click **"Save and Continue"**

8. Review the summary and click **"Back to Dashboard"**

### 4. Create OAuth 2.0 Credentials

1. Navigate to **"APIs & Services" > "Credentials"**
2. Click **"Create Credentials"** at the top
3. Select **"OAuth client ID"**
4. Configure the OAuth client:

   **Application type**: Web application

   **Name**: AI Buddy Web Client (or your preferred name)

   **Authorised JavaScript origins**:
   - For local development: `http://localhost`
   - For local Sail: `http://app.localhost`
   - For production: `https://yourdomain.com`

   **Authorised redirect URIs**:
   - For local development: `http://localhost/auth/google/callback`
   - For local Sail: `http://app.localhost/auth/google/callback`
   - For production: `https://yourdomain.com/auth/google/callback`

5. Click **"Create"**

6. **Save your credentials**: A modal will appear showing:
   - **Client ID**: Copy this (looks like: `123456789-abc123.apps.googleusercontent.com`)
   - **Client Secret**: Copy this (looks like: `GOCSPX-abc123xyz`)

7. Click **"OK"**

**Important**: Keep these credentials secure and never commit them to version control!

### 5. Publishing Your App (Production)

For development and testing, your app will remain in "Testing" mode. To make it available to all users:

1. Navigate to **"OAuth consent screen"**
2. Click **"Publish App"**
3. Review the information and click **"Confirm"**

**Note**: For "External" apps, you may need to go through Google's verification process if you request sensitive or restricted scopes. The basic profile and email scopes used here typically don't require verification.

## Laravel Application Configuration

### 1. Add Credentials to `.env`

Add the following to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret-here
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

**Important**: Also update `.env.example` with placeholders:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### 2. Update `config/services.php`

The Google OAuth configuration should already be added:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

## Testing the Integration

### Local Development

1. Ensure your `.env` has the correct credentials
2. Make sure you've added `http://localhost/auth/google/callback` (or `http://app.localhost/auth/google/callback` for Sail) to Google Console's authorised redirect URIs
3. Start your development server: `composer dev`
4. Navigate to your application
5. Click "Log in with Google" on the login modal
6. You should be redirected to Google's consent screen
7. After authorising, you should be redirected back and logged in

### Production Deployment

1. Update your `.env` on the production server with production credentials
2. Ensure your Google Console has the production domain added to:
   - Authorised JavaScript origins: `https://yourdomain.com`
   - Authorised redirect URIs: `https://yourdomain.com/auth/google/callback`
3. Clear config cache: `php artisan config:clear`
4. Test the login flow in production

## Troubleshooting

### "Error 400: redirect_uri_mismatch"

- Double-check that the redirect URI in Google Console exactly matches your application's URL
- Ensure you're using the correct protocol (`http` vs `https`)
- For Sail, make sure you're using `app.localhost` not `localhost`
- Check for trailing slashes (they should not be present)

### "Error 401: invalid_client"

- Verify your `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`
- Run `php artisan config:clear` to clear cached configuration
- Ensure the credentials match the ones from Google Console

### "Access blocked: This app's request is invalid"

- Make sure the OAuth consent screen is properly configured
- Verify that the scopes requested match those configured in Google Console
- For testing, ensure your email is added to test users list

### User Gets "This app isn't verified" Warning

- This is normal for apps in development/testing mode
- Click "Advanced" → "Go to [App Name] (unsafe)" to proceed
- For production apps with external users, complete Google's verification process

### Database Errors on Login

- Ensure migrations have been run: `php artisan migrate`
- Check that `google_id` column exists in `users` table
- Verify that `email` is nullable or that your OAuth controller creates users with required fields

## Security Considerations

1. **Never commit credentials**: Keep `.env` in `.gitignore`
2. **Use HTTPS in production**: Google OAuth requires HTTPS for production apps
3. **Validate email addresses**: Ensure email verification logic handles OAuth users appropriately
4. **Rate limiting**: Consider rate limiting OAuth callback routes to prevent abuse
5. **Session security**: Use secure session configuration in production

## Additional Resources

- [Laravel Socialite Documentation](https://laravel.com/docs/11.x/socialite)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)
