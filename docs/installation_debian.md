# Install GogoCarto on Debian 10 Buster from scratch

## Server initial setup

We are assuming the machine will be named **gogocarto**, and the related domain **gogocarto.fr**, your ip v4 **100.101.102.103** and ip v6 **acab:acab:acab:acab::2**

DNS config : if you plan to use GogoCarto for multiples maps on subdomains as SAAS, don't forget to create a wildcard `*` A and AAAA entries so that every subdomains points to the right server

Use Debian 10 Buster minimal iso for server install
login as root and update system

```bash
apt update && apt upgrade -y 
```

Change hostname for gogocarto
`hostnamectl set-hostname gogocarto`

Edit /etc/hosts file  and change hostname to gogocarto :

`vi /etc/hosts`
```bash
# nameserver config
# IPv4
127.0.0.1 localhost.localdomain localhost
100.101.102.103  gogocarto gogocarto.fr
#
# IPv6
::1     ip6-localhost ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters
ff02::3 ip6-allhosts
acab:acab:acab:acab::2  gogocarto gogocarto.fr
```

## Better security

Create admin user in sudo group and add your public key in /home/admin/.ssh/authorized_keys

```bash
apt install sudo
adduser admin
usermod -aG sudo admin
sudo -u admin vi /home/admin/.ssh/authorized_keys #add your key
```

Create gogocarto user (without special permissions)

```bash
adduser gogocarto
```

Allow gogocarto to stop/start cron (when deploying) : Add following to the end of /etc/sudoers
```bash
Cmnd_Alias CRON_CMDS = /usr/bin/systemctl start cron, /usr/bin/systemctl stop c$
gogocarto ALL=(ALL) NOPASSWD: CRON_CMDS
```

SSH config we change port, allow only key and forbid root acces
`vi /etc/ssh/sshd_config`

```bash
Port 1999 # change port for something uncommon
PermitRootLogin no # no login as root
PermitEmptyPasswords no # empty password for ssh acces, bad idea..
AllowUsers admin gogocarto # only users admin and gogocarto shall pass
MaxAuthTries 3 # only 3 tries
ClientAliveInterval 60 #check activity every 60 seconds (1 minute)
ClientAliveCountMax 5 # after 5 minutes without activity, kick out
Protocol 2 # only more secure protocol
IgnoreRhosts yes # ignore old unsecure ways to connect
LogLevel INFO # log a lot of infos
PasswordAuthentication no
PubkeyAuthentication yes
ChallengeResponseAuthentication no
UsePAM no
```
Restart sshd
`service sshd restart`

TODO fail2ban firewall

## Install GogoCarto
As admin user
```bash
sudo apt-get update && sudo apt-get install -y --no-install-recommends \
    bzip2 \
    cron \
    htop \
    g++ \
    gettext \
    git \
    gnupg \
    imagemagick \
    libfreetype6 \
    libgd3 \
    libmcrypt4 \
    libmemcached11 \
    libmemcachedutil2 \
    libsodium23 \
    libtidy5deb1 \
    libxml2 \
    libxslt1.1 \
    libzip4 \
    nano \
    openssl \
    unzip \
    wget \
    lftp \
    libbz2-dev \
    libc-client-dev \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libgd-dev \
    libicu-dev \
    libkrb5-dev \
    libmagickcore-dev \
    libmagickwand-dev \
    libonig-dev \
    libmcrypt-dev \
    libmemcached-dev \
    libtidy-dev \
    libxml2-dev \
    libxslt-dev \
    libz-dev \
    libzip-dev \
    python3-pip \
    nginx \
    php \
    php-fpm \
    php-common \
    php-gmp \
    php-curl \
    php-intl \
    php-mbstring \
    php-xmlrpc \
    php-gd \
    php-pear \
    php-bcmath \
    php-imagick \
    imagemagick \
    php-soap \
    php-ldap \
    php-imap \
    php-tidy \
    php-bz2 \
    php-dba \
    php-exif \
    php-gettext \
    php-xml \
    php-simplexml \
    php-xsl \
    php-cli \
    php-zip \
    php-dev \
    dirmngr \
    gnupg \
    apt-transport-https \
    software-properties-common \
    ca-certificates \
    curl \
    build-essential \
```
## Mongodb
```bash
curl -fsSL https://www.mongodb.org/static/pgp/server-4.2.asc | sudo apt-key add -`
sudo add-apt-repository 'deb https://repo.mongodb.org/apt/debian buster/mongodb-org/4.2 main'
sudo apt update
sudo apt install mongodb-org -y
sudo systemctl enable mongod --now
pecl channel-update pecl.php.net
pecl install mongodb 
```

Add to `/etc/php/7.3/cli/php.ini` **AND** `/etc/php/7.3/fpm/php.ini`
extension=mongodb.so
Also adjust the following options if you plan to request large imports
`default_socket_timeout`, `max_execution_time`

Restart php service
`service php7.3-fpm restart`


## Certbot (snap version)
```
sudo apt install snapd -y
sudo snap install core; sudo snap refresh core
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot
sudo snap set certbot trust-plugin-with-root=ok
```

## acme.sh pour les certificats wildcard

```bash
wget -O -  https://get.acme.sh | sh -s email=contact@colibris-outilslibres.org
export GANDI_LIVEDNS_KEY="fdmlfsdklmfdkmqsdfk"
acme.sh --issue --dns dns_gandi_livedns -d gogocarto.fr -d *.gogocarto.fr
```


## Composer

```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

## Node & Npm & Yarn
```
curl -sL https://deb.nodesource.com/setup_10.x  | sudo bash -
sudo apt-get install -y nodejs
curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt-get update && sudo apt-get install yarn
sudo npm install -g gulp
```

## Cloning Repo
```
cd /var/www
sudo mkdir gogocarto
sudo chown gogocarto:gogocarto gogocarto/
sudo -u gogocarto git clone https://gitlab.com/seballot/gogocarto.git gogocarto/
```

## Nginx
```bash
#/etc/nginx/sites-avaible/gogocarto.fr
server {
  listen 80;
  listen [::]:80;
  server_name gogocarto.fr;
  access_log /var/log/nginx/gogocarto.fr.access.log;
  error_log /var/log/nginx/gogocarto.fr.error.log;

  location /.well-known/acme-challenge/ {
    default_type "text/plain";
    root /var/www/certbot;
  }

  location / {
    return 301 https://www.$host$request_uri;
  }
}

server {
  listen 80;
  listen [::]:80;
  server_name *.gogocarto.fr;
  access_log /var/log/nginx/gogocarto.fr.access.log;
  error_log /var/log/nginx/gogocarto.fr.error.log;

  location /.well-known/acme-challenge/ {
    default_type "text/plain";
    root /var/www/certbot;
  }

  location / { return 301 https://$host$request_uri; }
}

server {
  listen 443 ssl http2;
  listen [::]:443 ssl http2;
  server_name *.gogocarto.fr;

  root /var/www/gogocarto/web;

  # For example with certbot (you need a certificate to run https)
  ssl_certificate      /root/.acme.sh/gogocarto.fr/fullchain.cer;
  ssl_certificate_key  /root/.acme.sh/gogocarto.fr/gogocarto.fr.key;

  # Security hardening (as of 11/02/2018)
  ssl_protocols TLSv1.2; # TLSv1.3, TLSv1.2 if nginx >= 1.13.0
  ssl_prefer_server_ciphers on;
  ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256';
  # ssl_ecdh_curve secp384r1; # Requires nginx >= 1.1.0, not compatible with import-videos script
  ssl_session_timeout  10m;
  ssl_session_cache shared:SSL:10m;
  ssl_session_tickets off; # Requires nginx >= 1.5.9
  ssl_stapling on; # Requires nginx >= 1.3.7
  ssl_stapling_verify on; # Requires nginx => 1.3.7

  # Configure with your resolvers
  # resolver \$DNS-IP-1 \$DNS-IP-2 valid=300s;
  # resolver_timeout 5s;

  # Enable compression for JS/CSS/HTML bundle, for improved client load times.
  # It might be nice to compress JSON, but leaving that out to protect against potential
  # compression+encryption information leak attacks like BREACH.
  gzip on;
  gzip_types text/css application/javascript;
  gzip_vary on;

  add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";

  access_log /var/log/nginx/gogocarto.fr.access.log;
  error_log /var/log/nginx/gogocarto.fr.error.log;

  location ^~ '/.well-known/acme-challenge' {
    default_type "text/plain";
    root /var/www/certbot;
  }

  # cache.appcache, your document html and data
  location ~* \.(?:manifest|appcache|html?|xml)$ {
    add_header Cache-Control "max-age=0";
  }

  # Feed
  location ~* \.(?:rss|atom)$ {
    add_header Cache-Control "max-age=3600";
  }

  # Media: images, icons, video, audio, HTC
  location ~* \.(?:jpg|jpeg|gif|png|ico|cur|gz|svg|mp4|ogg|ogv|webm|htc)$ {
    access_log off;
    add_header "Access-Control-Allow-Origin"  *;
    add_header Cache-Control "max-age=2592000";
  }

  # Media: svgz files are already compressed.
  location ~* \.svgz$ {
    access_log off;
    gzip off;
    add_header Cache-Control "max-age=2592000";
  }

  # CSS and Javascript
  location ~* \.(?:css|js)$ {
    add_header Cache-Control "max-age=31536000";
    access_log off;
  }

  # WebFonts
  location ~* \.(?:ttf|ttc|otf|eot|woff|woff2)$ {
    add_header Cache-Control "max-age=2592000";
    add_header "Access-Control-Allow-Origin"  *;
    access_log off;
  }

  # strip app.php/ prefix if it is present
  rewrite ^/index\.php/?(.*)$ /$1 permanent;

  location / {
    index index.php;
    try_files $uri @rewriteapp;
  }

  location @rewriteapp {
    rewrite ^(.*)$ /index.php/$1 last;
  }

  # pass the PHP scripts to FastCGI server from upstream phpfcgi
  location ~ ^/(app|app_dev|index|config)\.php(/|$) {
    fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
    fastcgi_split_path_info ^(.+\.php)(/.*)$;
    include fastcgi_params;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param  HTTPS on;
    fastcgi_read_timeout 300;
  }
}
```

`ln -nsf /etc/nginx/sites-available/gogocarto.fr /etc/nginx/sites-enabled/gogocarto.fr`

```bash
#/etc/nginx/nginx.conf 
user gogocarto
```

`service nginx reload`


## PHP conf

```bash
#/etc/php/7.3/fpm/pool.d/www.conf
user = gogocarto
group = gogocarto
listen.owner = gogocarto
listen.group = gogocarto
pm.max_children = 400
pm.start_servers = 100
pm.min_spare_servers = 100
pm.max_spare_servers = 300
```
```bash
#/etc/php/7.3/cli/php.ini 
max_execution_time = 6000
memory_limit = -1
```
```bash
#/etc/php/7.3/fpm/php.ini 
max_ecution_time = 200
memory_limit = 512M
```

`service php7.3-fpm restart`

`sudo chown gogocarto:gogocarto -R /var/lib/php/sessions/`

## Install GoGoCarto

Follow instructions of the [Installation Guide](./installation.md)

## Backups (Optional)
vi /root/backup.sh

```bash
#!/bin/bash
HOST=${HOST:-backup.host}
USER=${USER:-user}
PASSWORD=${PASSWORD:-pass}
KEEPBACKUPDAYS=${KEEPBACKUPDAYS:-10}
DIRECTORYMONGO=${DIRECTORYMONGO:-/var/backups/mongobackups}
DIRECTORYFILES=${DIRECTORYFILES:-/var/backups/gogocarto-uploads}
GOGOCARTODIRECTORY=${GOGOCARTODIRECTORY:-/var/www/gogocarto}

if [ ! -d "$DIRECTORYMONGO" ]; then
  mkdir -p $DIRECTORYMONGO
fi

if [ ! -d "$DIRECTORYFILES" ]; then
  mkdir -p $DIRECTORYFILES
fi


# backup mongodb
mongodump --out $DIRECTORYMONGO/`date +"%Y-%m-%d"`

# backup gogocarto files
cd $GOGOCARTODIRECTORY/web/uploads
tar -zcvf $DIRECTORYFILES/`date +"%Y-%m-%d"`.tar.gz .

# clean older backups
find $DIRECTORYFILES/ -mtime +$KEEPBACKUPDAYS -exec rm -rf {} \;
find $DIRECTORYMONGO/ -mtime +$KEEPBACKUPDAYS -exec rm -rf {} \;

# compress and remplace existing on ftp
cd $DIRECTORYFILES
tar -zcvf /tmp/gogocarto-uploads.tar.gz .
cd $DIRECTORYMONGO
tar -zcvf /tmp/mongobackups.tar.gz .
cd /tmp
lftp -u $USER,$PASSWORD $HOST -e "rm -r gogocarto;mkdir -p gogocarto;cd gogocarto;put mongobackups.tar.gz; put gogocarto-uploads.tar.gz; exit"

# remove local one
rm mongobackups.tar.gz gogocarto-uploads.tar.gz
```

`chmod +x /root/backup.sh`

## Monitoring the server with InfluxDB / Telegraf / Grafana (Optional)

For monitoring your server

### InfluxDB
```bash
wget -qO- https://repos.influxdata.com/influxdb.key | sudo apt-key add -
echo "deb https://repos.influxdata.com/debian buster stable" | sudo tee /etc/apt/sources.list.d/influxdb.list
sudo apt update
sudo apt install -y influxdb
sudo systemctl enable --now influxdb
```

`sudo vim /etc/influxdb/influxdb.conf`
```
[http]
 auth-enabled = true
```

`curl -XPOST "http://localhost:8086/query" --data-urlencode "q=CREATE USER gogocarto WITH PASSWORD 'strongpassword' WITH ALL PRIVILEGES"`

### Telegraf
`sudo apt -y install telegraf`
`sudo vi /etc/telegraf/telegraf.conf`
```
# Global tags can be specified here in key="value" format.
[global_tags]
# dc = "us-east-1" # will tag all metrics with dc=us-east-1
# rack = "1a"
## Environment variables can be used as tags, and throughout the config file
# user = "$USER"

# Configuration for telegraf agent
[agent]
interval = "10s"
round_interval = true
metric_batch_size = 1000
metric_buffer_limit = 10000
collection_jitter = "0s"
flush_interval = "10s"
flush_jitter = "0s"
precision = ""
debug = false
quiet = false
hostname = ""
omit_hostname = false

### OUTPUT

# Configuration for influxdb server to send metrics to
[[outputs.influxdb]]
urls = ["http://localhost:8086"]
database = "telegraf_metrics"

## Retention policy to write to. Empty string writes to the default rp.
retention_policy = ""
## Write consistency (clusters only), can be: "any", "one", "quorum", "all"
write_consistency = "any"

## Write timeout (for the InfluxDB client), formatted as a string.
## If not provided, will default to 5s. 0s means no timeout (not recommended).
timeout = "5s"
username = "gogocarto"
password = "strongpassword"
## Set the user agent for HTTP POSTs (can be useful for log differentiation)
# user_agent = "telegraf"
## Set UDP payload size, defaults to InfluxDB UDP Client default (512 bytes)
# udp_payload = 512

# Read metrics about cpu usage
[[inputs.cpu]]
## Whether to report per-cpu stats or not
percpu = true
## Whether to report total system cpu stats or not
totalcpu = true
## Comment this line if you want the raw CPU time metrics
fielddrop = ["time_*"]

# Read metrics about disk usage by mount point
[[inputs.disk]]
## By default, telegraf gather stats for all mountpoints.
## Setting mountpoints will restrict the stats to the specified mountpoints.
# mount_points = ["/"]

## Ignore some mountpoints by filesystem type. For example (dev)tmpfs (usually
## present on /run, /var/run, /dev/shm or /dev).
ignore_fs = ["tmpfs", "devtmpfs"]

# Read metrics about disk IO by device
[[inputs.diskio]]
## By default, telegraf will gather stats for all devices including
## disk partitions.
## Setting devices will restrict the stats to the specified devices.
# devices = ["sda", "sdb"]
## Uncomment the following line if you need disk serial numbers.
# skip_serial_number = false

# Get kernel statistics from /proc/stat
[[inputs.kernel]]
# no configuration

# Read metrics about memory usage
[[inputs.mem]]
# no configuration

# Get the number of processes and group them by status
[[inputs.processes]]
# no configuration

# Read metrics about swap memory usage
[[inputs.swap]]
# no configuration

# Read metrics about system load & uptime
[[inputs.system]]
# no configuration

# Read metrics about network interface usage
[[inputs.net]]
# collect data only about specific interfaces
# interfaces = ["eth0"]

[[inputs.netstat]]
# no configuration

[[inputs.interrupts]]
# no configuration

[[inputs.linux_sysctl_fs]]
# no configuration
```


### Grafana
cf. https://grafana.com/docs/grafana/latest/installation/debian/

Add nginx proxy for grafana
`vi /etc/nginx/sites-availables`
```
server {
  listen 80;
  listen [::]:80;
  server_name monitor.fraternite-covid19.fr;
  access_log /var/log/nginx/gogocarto.fr.access.log;
  error_log /var/log/nginx/gogocarto.fr.error.log;

  location /.well-known/acme-challenge/ {
    default_type "text/plain";
    root /var/www/certbot;
  }

  location / {
    return 301 https://$host$request_uri;
 }
}

server {
  listen 443 ssl http2;
  listen [::]:443 ssl http2;
  server_name monitor.fraternite-covid19.fr;

  root /var/www/gogocarto/web;

  # For example with certbot (you need a certificate to run https)
  ssl_certificate      /etc/letsencrypt/live/fraternite-covid19.fr/fullchain.pem;
  ssl_certificate_key  /etc/letsencrypt/live/fraternite-covid19.fr/privkey.pem;

  # Security hardening (as of 11/02/2018)
  ssl_protocols TLSv1.2; # TLSv1.3, TLSv1.2 if nginx >= 1.13.0
  ssl_prefer_server_ciphers on;
  ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256';
  # ssl_ecdh_curve secp384r1; # Requires nginx >= 1.1.0, not compatible with import-videos script
  ssl_session_timeout  10m;
  ssl_session_cache shared:SSL:10m;
  ssl_session_tickets off; # Requires nginx >= 1.5.9
  ssl_stapling on; # Requires nginx >= 1.3.7
  ssl_stapling_verify on; # Requires nginx => 1.3.7

  # Configure with your resolvers
  # resolver \$DNS-IP-1 \$DNS-IP-2 valid=300s;
  # resolver_timeout 5s;

  # Enable compression for JS/CSS/HTML bundle, for improved client load times.
  # It might be nice to compress JSON, but leaving that out to protect against potential
  # compression+encryption information leak attacks like BREACH.
  gzip on;
  gzip_types text/css application/javascript;
  gzip_vary on;

  add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";

  access_log /var/log/nginx/gogocarto.fr.access.log;
  error_log /var/log/nginx/gogocarto.fr.error.log;

  location ^~ '/.well-known/acme-challenge' {
    default_type "text/plain";
    root /var/www/certbot;
  }

  location / {
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_pass http://localhost:3000;
 }

}
```

Dans l'interface admin de grafana, roue crantée, ajouter la source influxdb 
avec host="http://localhost:8086" username = "gogocarto" password = "strongpassword" db="telegraf_metrics"