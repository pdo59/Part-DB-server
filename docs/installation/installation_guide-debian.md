# Part-DB installation guide for Debian 11 (Bullseye)
This guide shows you how to install Part-DB directly on Debian 11 using apache2 and SQLite. This guide should work with recent Ubuntu and other Debian based distributions with little to no changes.
Depending on what you want to do, using the prebuilt docker images may be a better choice, as you dont need to install this much dependencies. See **TODO** for more information of the docker installation.

**Caution: This guide shows you how to install Part-DB for use in a trusted local network. If you want to use Part-DB on the internet, you HAVE TO setup a SSL certificate for your connection!**

## Install prerequisites
For the installation of Part-DB, we need some prerequisites. They can be installed by running the following command:
```bash
sudo apt install git curl zip ca-certificates software-properties-common apt-transport-https lsb-release nano wget
```

## Install PHP and apache2
Part-DB is written in [PHP](https://php.net) and therefore needs an PHP interpreter to run. Part-DB needs PHP 7.3 or higher, however it is recommended to use the most recent version of PHP for performance reasons and future compatibility.

As Debian 11 does not ship PHP 8.1 in it's default repositories, we have to add a repository for it. You can skip this step if your distribution is shipping a recent version of PHP or you want to use the built-in PHP version.
```bash
# Add sury repository for PHP 8.1
sudo curl -sSL https://packages.sury.org/php/README.txt | sudo bash -x

# Update package list
sudo apt update && sudo apt upgrade
```
Now you can install PHP 8.1 and required packages (change the 8.1 in the package version according to the version you want to use):
```bash
sudo apt install php8.1 libapache2-mod-php8.1 php8.1-opcache php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-bcmath php8.1-intl php8.1-zip php8.1-xsl php8.1-sqlite3 php8.1-mysql
```
The apache2 webserver should be already installed with this command and configured basically.

## Install composer
Part-DB uses [composer](https://getcomposer.org/) to install required PHP libraries. As the versions shipped in the repositories is pretty old we install it manually:
```bash
# Download composer installer script
wget -O /tmp/composer-setup.php https://getcomposer.org/installer
# Install composer globally
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
# Make composer executable
chmod +x /usr/local/bin/composer
```

## Install yarn and nodejs
To build the frontend (the user interface) Part-DB uses [yarn](https://yarnpkg.com/). As it dependens on nodejs and the shipped versions are pretty old, we install new versions from offical nodejs repository:
```bash
# Add recent node repository (nodejs 18 is supported until 2025)
curl -sL https://deb.nodesource.com/setup_18.x | sudo -E bash -
# Install nodejs
sudo apt install nodejs
```

We can install yarn with the following commands:
```bash
# Add yarn repository
curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | sudo tee /usr/share/keyrings/yarnkey.gpg >/dev/null
echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
# Install yarn
sudo apt update && sudo apt install yarn
```

## Create a folder for Part-DB and download it
We now have all prerequisites installed and can start to install Part-DB. We will create a folder for Part-DB in a webfolder of apache2 and download it to this folder. The downloading is done via git, which allows you to update easily later.
```bash
# Download Part-DB into the new folder /var/www/partdb
git clone https://github.com/Part-DB/Part-DB-symfony.git /var/www/partdb
```

Change ownership of the files to the apache user:
```bash
chown -R www-data:www-data /var/www/partdb
```

For the next steps we should be in the Part-DB folder, so move into it:
```bash
cd /var/www/partdb
```

## Create configuration for Part-DB
The basic configuration of Part-DB is done by a `.env.local` file in the main directory. Create on by from the default configuration:
```bash
cp .env .env.local
```

In your `.env.local` you can configure Part-DB according to your wishes. A full list of configuration options can be found **TODO**.
Other configuration options like the default language or default currency can be found in `config/parameters.yaml`.

Please check that the `partdb.default_currency` value in `config/parameters.yaml` matches your mainly used currency, as this can not be changed after creating price informations.

## Install dependencies for Part-DB and build frontend
Part-DB depends on several other libraries and components. Install them by running the following commands:
```bash
# Install composer dependencies (please note the sudo command, to run it under the web server user)
sudo -u www-data composer install --no-dev -o

# Install yarn dependencies
sudo yarn install
# Build frontend
sudo yarn build
```

### Check if everything is installed
To check if everything is installed, run the following command:
```bash
sudo -u www-data php bin/console partdb:check-requirements
```
The most things should be green, and no red ones. Yellow messages means optional dependencies which are not important but can improve performance and functionality.

## Create a database for Part-DB
Part-DB by default uses a file based sqlite database to store the data. Use the following command to create the database. The database will normally created at `/var/www/partdb/var/app.db`.
```bash
sudo -u www-data php bin/console doctrine:migrations:migrate
```
The command will warn you about schema changes and potential data loss. Continue with typing `yes`.

The command will output several lines of informations. Somewhere should be a a yellow background message like `The initial password for the "admin" user is: f502481134`. Write down this password as you will need it later for inital login.

## Configure apache2 to show Part-DB
Part-DB is now configured, but we have to say apache2 to serve Part-DB as web application. This is done by creating a new apache site:
```bash
sudo nano /etc/apache2/sites-available/partdb.conf
```
and add the following content (change ServerName and ServerAlias to your needs):
```
<VirtualHost *:80>
    ServerName partdb.lan
    ServerAlias www.partdb.lan

    DocumentRoot /var/www/partdb/public
    <Directory /var/www/partdb/public>
        AllowOverride All
        Order Allow,Deny
        Allow from All
    </Directory>

    ErrorLog /var/log/apache2/partdb_error.log
    CustomLog /var/log/apache2/partdb_access.log combined
</VirtualHost>
```
Activate the new site by:
```bash
sudo ln -s /etc/apache2/sites-available/partdb.conf /etc/apache2/sites-enabled/partdb.conf
```

Configure apache to show pretty URL pathes for Part-DB (`/label/dialog` instead of `/index.php/label/dialog`):
```bash
sudo a2enmod rewrite
```

If you want to access Part-DB via the IP-Address of the server, instead of the domain name, you have to remove the apache2 default configuration with:
```bash
sudo rm /etc/apache2/sites-enabled/000-default.conf
```

Restart the apache2 webserver with:
```bash
sudo service apache2 restart
```

and Part-DB should now be available under `http://YourServerIP` (or `http://partdb.lan` if you configured DNS in your network to point on the server).

## Login to Part-DB
Navigate to the Part-DB web interface and login via the user icon in the top right corner. You can login using the username `admin` and the password you have written down earlier.

## Update Part-DB
If you want to update your existing Part-DB installation, you just have to run the following commands:
```bash
# Move into Part-DB folder
cd /var/www/partdb
# Pull latest Part-DB version from GitHub
git pull
# Apply correct permission
chown -R www-data:www-data .
# Install new composer dependencies
sudo -u www-data composer install --no-dev -o
# Install yarn dependencies and build new frontend
sudo yarn install
sudo yarn build

# Check if all your configurations in .env.local and /var/www/partdb/config/parameters.yaml are correct.
sudo nano config/parameters.yaml

# Apply new database schemas (you should do a backup of your database file /var/www/partdb/var/app.db before)
sudo -u www-data php bin/console doctrine:migrations:migrate

# Clear Part-DB cache
sudo u www-data php bin/console cache:clear
```