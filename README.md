# Click transfert platform

## Install and Config

### config LAMP server
On ubuntu 16.04 [install lamp](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-16-04) and [composer](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-composer-on-ubuntu-16-04)

### install additional packages

```bash
sudo apt-get update & sudo apt-get -y upgrade
sudo apt-get -y install curl php-cli git php-mbstring git unzip zend-framework 
sudo apt-get -y install php7.0-mysql php7.0-curl php7.0-gd php7.0-intl php-pear php-imagick 
sudo apt-get -y install php7.0-imap php7.0-mcrypt php-memcache  
sudo apt-get -y install php7.0-pspell php7.0-recode php7.0-sqlite3 php7.0-tidy php7.0-xmlrpc 
sudo apt-get -y install php7.0-xsl php7.0-mbstring php-gettext
sudo a2enmod rewrite
```

### clone repository to /var/www

```bash
cd /var/www
sudo git clone https://github.com/Djang0/Swipe-Step.git
```
### add a virtualhost to appache config

```apacheconf
<VirtualHost *:80>
 ServerAdmin admin@domain.com
 DocumentRoot /var/www/Swipe-Step/public/
 ServerName click.domain.com
 <Directory /var/www/Swipe-Step/public/>
	DirectoryIndex index.php
	Options Indexes FollowSymLinks MultiViews
	AllowOverride All
	Order allow,deny
	allow from all
 </Directory>
 ErrorLog /var/log/apache2/click_error_log
 CustomLog /var/log/apache2/click_access_log common
</VirtualHost>

```
### Create mysql database

```sql
CREATE DATABASE db_name;
CREATE USER 'web_user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON db_name . * TO 'web_user'@'localhost';
FLUSH PRIVILEGES;
```
### Initialise database

```bash
cd /var/www/swipe-step/vendor/Swipe-Step/SQL
mysql -u web_user -ppassword db_name < DDL.sql
```
Then insert at least one record into "owners" table to give admin access through api.
### Ensure everything is up-to-date:
```bash
cd /var/www/Swipe-Step
sudo composer update
```
### Setup database connection
```bash
cd /var/www/Swipe-Step/src/
sudo mv local.settings.php.sample local.settings.php
# edit local.settings.php to configure database connection
sudo vim local.settings.php
```
### Test 
Going to http://click.domain.com/dump/
Should return approximatively this :

```json
{
    "Host": ["178.33.174.163"],
    "HTTP_CONNECTION": ["keep-alive"],
    "HTTP_UPGRADE_INSECURE_REQUESTS": ["1"],
    "HTTP_USER_AGENT": [
        "Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/57.0.2987.133 Safari\/537.36"
    ],
    "HTTP_ACCEPT": [
        "text\/html,application\/xhtml+xml,application\/xml;q=0.9,image\/webp,*\/*;q=0.8"
    ],
    "HTTP_ACCEPT_ENCODING": ["gzip, deflate, sdch"],
    "HTTP_ACCEPT_LANGUAGE": ["fr,en;q=0.8"],
    "HTTP_COOKIE": ["PHPSESSID=vaaqao71eprftcifangmucnd73"]
}
```
