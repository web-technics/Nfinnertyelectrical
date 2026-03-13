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
