# Nfinnerty Electrical Website

## SECURITY_NOTES

Date: 2026-03-13

- Added server-level block for `xmlrpc.php` in `.htaccess`.
- Added WordPress hardening constants in `wp-config.php`:
  - `DISALLOW_FILE_EDIT`
  - `DISALLOW_FILE_MODS`
  - `FORCE_SSL_ADMIN`
- Removed malformed/insecure `ALLOW UNFILTERED UPLOADS` line from `wp-config.php`.

### Deployment note

`wp-config.php` is intentionally excluded from Git and must be deployed via FTP when changed.

### Recovery Steps

1. Clone this repository backup and check out the latest stable commit.
2. Upload tracked site files from this repo to the server via FTP.
3. Upload `wp-config.php` separately via FTP (it is not stored in Git).
4. Verify `.htaccess` includes the `xmlrpc.php` block.
5. Log in to wp-admin and clear any plugin/theme cache.

### Monthly Maintenance Checklist

1. Update WordPress core to latest stable version.
2. Update active theme (Divi) and all plugins.
3. Review Wordfence/hosting logs for unusual login or file activity.
4. Verify backup integrity by downloading and opening the latest backup archive.
5. Confirm GitHub backup is up to date (`git status`, `git push`).
6. Test homepage, contact form, and key service pages after updates.
