# teslalogger-custom-scripts
Custom dashboards and other scripts for the Teslalogger application

## logfile2Telegram
```
*/5 * * * * php -f /var/www/html/admin/logfile2Telegram.php > /dev/null 2>&1
```