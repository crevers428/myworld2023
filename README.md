😎# WCA World Championship System

This uses a LEMP stack (Linux nginx mysql php).  

Configure nginx as per Symfony documentation:  
https://symfony.com/doc/current/setup/web_server_configuration#nginx

Requires:  
```
sudo apt-get -y install php7.2-curl php7.2-mbstring php7.2-zip php7.2-gd php7.2-mysql gcc make autoconf libc-dev pkg-config php7.2-dev libmcrypt-dev php-pear php7.2-fpm
sudo pecl install mcrypt-1.0.1
```

Configure /etc/php/7.2/fpm/php.ini and add:  
```
extension=mcrypt.so
```

Restart services:

```
sudo service php7.2-fpm restart
sudo service nginx restart
```

## Setup secrets

### WCA OAuth Applications

Login to the WCA site and navigate to manage your OAuth applications.  

Create a development and production application with scopes: public dob email  

Call back for dev should have a URL like: `http://localhost/en/identify`  

Copy the id and secret to `WorldChamps/Repo/__private_oauth_wca__.inc`

```
$wca_oauth_id = "";
$wca_oauth_secret = "";
```

### Create a wca database and wca_user in mysql

`sudo mysql -u root -p`  

```
CREATE DATABASE wca;
CREATE USER 'wca_user'@'localhost' IDENTIFIED BY 'FIXME';
GRANT ALL ON wca.* TO 'wca_user'@'localhost';
FLUSH PRIVILEGES;
CREATE DATABASE wca_dev;
CREATE USER 'wca_user_dev'@'localhost' IDENTIFIED BY 'FIXME';
GRANT ALL ON wca_dev.* TO 'wca_user_dev'@'localhost';
FLUSH PRIVILEGES;
```

Copy the user, pass and database name to `WorldChamps/Repo/__private__.inc`

```
$user = '';
$pass = '';
$DBname = '';
```

## Create a wc database and wc_user in mysql

`sudo mysql -u root -p`  


```
CREATE DATABASE wc;
CREATE USER 'wc_user'@'localhost' IDENTIFIED BY 'FIXME';
GRANT ALL ON wc.* TO 'wc_user'@'localhost';
FLUSH PRIVILEGES;
CREATE DATABASE wc_dev;
CREATE USER 'wc_user_dev'@'localhost' IDENTIFIED BY 'FIXME';
GRANT ALL ON wc_dev.* TO 'wc_user_dev'@'localhost';
FLUSH PRIVILEGES;
```

Copy the user, pass and database name to `WorldChamps/Repo/__private_wca__.inc`

```
$dsn = '';
$user = '';
$DBname = '';
```

### Setup Stripe secrets

Login to Stripe and get the publishable key and secret keys for Test and Live under the Developer section.  

Copy the publishable key and secret keys to `WorldChamps/Repo/__private_stripe__.inc`

```
$stripe_publishable_key = "";
$stripe_secret_key = "";
```

### Add salt to cookies!

Add salt to `App/Application.php` and `WorldChamps/WorldChampsApplication.php`  

## Load data

### Load WCA export

```
cd database
wget https://www.worldcubeassociation.org/results/misc/WCA_export.sql.zip
unzip WCA_export.sql.zip
mysql wca_dev -u wca_user_dev -p < WCA_export.sql
rm README.txt
rm WCA_export.sql.zip
```

## Configure application

Find dates and configure.  

Make sure the virtualhost server_name is localhost in the nginx config if you want the environment ton be detected as DEV.

## Folders

```
mkdir cache
chmod 777 cache
mkdir import
chmod 777 import
```

## nginx conf etc

Increase the timeout for admin scripts:
```
fastcgi_read_timeout 300;
include fastcgi_params;
```
