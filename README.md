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
### Troubleshooting
#### Rebase from repository
```bash
sudo git fetch --all
sudo git reset --hard origin/master
sudo composer update
sudo service apache2 restart
```


### API

> /

```
No Authentication required.
Method : GET
(200) Nothing to see here !
'Content-type'='application/json'
```
> /dump/

```
No Authentication required.
Method : GET
(200) Dumps request for debug purpose.
'Content-type'='application/json'
```

> /getHooks/{name}/{id}

```
Authentication required. Returns all hooks data having an id > {id} param.
Method : GET
(200) => Ok
(503) => PDOException
'Content-type'='application/json'
```

> /getHooks/

```
Authentication required. Returns all hooks.
Method : GET
(200) => Ok
(503) => PDOException
'Content-type'='application/json'
```


> /getCodes/

```
Authentication required. Returns all target specifications owned by the authenticated user.
Method : GET
(200) => Ok
(503) => PDOException
'Content-type'='application/json'
```

> /getAllHits/

```
Authentication required. Returns all hits owned by the authenticated user and grouped by target.
Method : GET
(200) => Ok
(503) => PDOException
'Content-type'='application/json'
```

> /getTarget/{target_id}

```
Authentication required. Returns all hits for a given target owned by the authenticated user.
Parameter : targets.id (integer not null >0)
Method : GET
(200) => Ok
(503) => PDOException
'Content-type'='application/json'
```

> /addHook/{string_to_encode}

```
Authentication required. creates a Hook by generating MD5 of the string_to_encode parameter.
Method : GET
Parameter 1 : string_to_encode (String != '')
(200) => Ok
(503) => PDOException
(400) => Failure. name is not properly formated. | hook already exists.
'Content-type'='application/json'
```

> /addTarget/{code}/{url}

```
Authentication required. Adds a target owned by the authenticated user.
Method : GET
Parameter 1 : targets.code (String != '' and not null max 32 char)
Parameter 2 : targets.url (String != '' and not null must be urlencoded max 2083 char)
(200) => Ok
(503) => PDOException
(400) => Failure. code AND / OR url is not properly formated. | Code already exists
'Content-type'='application/json'
```

> /testCode/{code}

```
Authentication required. Test code availability. returns 'True' or 'False'
Method : GET
Parameter 1 : targets.code (String != '' and not null max 32 char)
(200) => Ok
(503) => PDOException
(400) => Failure. code AND / OR url is not properly formated.
'Content-type'='application/json'
```

> /to/{code}

```
No Authentication required. Do a redirection according to the provided code.
Keeps track of the HTTP_REFERER header if exists. Stores the hit (DateTime, IP and HTTP_REFERER)
HTTP_REFERER is transfered to the targeted url.
Method : GET
Parameter : targets.code (String != '' and not null)
 (404) Code note found or code invalid (custom 404 html page)
 (503) PDOException
 (301) transfert OK
 'Content-type'='text/html'
```

> /hook/{name}

```
No Authentication required. Does log the content of POST data to the hook_calls table.
Method : POST
 (503) PDOException
 (200) Ok !
 'Content-type'='application / json'
```

> /getHooksByTimeStamp/{name}/{timestamp}

```
Authentication required. Returns all hooks data having a timestamp > {timestamp} param.
Method : GET
(200) => Ok
(503) => PDOException
'Content-type'='application/json'
```
