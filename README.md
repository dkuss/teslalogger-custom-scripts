# teslalogger-custom-scripts
Custom dashboards and other scripts for the Teslalogger application

## logfile2Telegram

### Installation
This tool parses the Tesla-Logger logfile for interesting 
events and sends information to a Telegram Bot webhook that
posts the information every 5 minutes to a Telegram chat channel.

To set up this script you need to:
- Setup a Telegram bot and put it into a channel that should receive this updates.
- Upload the `logfile2Telegram` folder with all it's contents to the `/var/www/html` folder on your Teslalogger.
- Installer composer and run `composer install` in the `logfile2Telegram` folder 
- Copy or rename the file `/config/config.php.sample` to `config.php` and include your bot and channel ids.
- Add the following cronjob to the crontab of the 'pi' user.
```
*/5 * * * * php -f /var/www/html/logfile2Telegram/index.php > /dev/null 2>&1
```

### Update
- Download the repo zip file.
- Upload the `logfile2Telegram` folder with all it's contents to the `/var/www/html` folder on your Teslalogger and overwrite all existing files.
- Done.

### Connection to the Teslalogger
By default you can connect to the teslalogger via SSH/SFTP by suing the following credentials:

Host: raspberry (or IP address)   
User: pi   
Password: teslalogger

You can use tools like WinSCP or FileZilla to make uploading files easier.
