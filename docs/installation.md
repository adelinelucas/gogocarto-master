Installation and Production Instructions
========================================

Feel free to add some more information if you solve installation issues!

Installation with Docker
------------

With the Docker installation, you have all the required softwares installed in two containers (`gogocarto` and `mongo`).

* Add your local user to docker group : `/usr/sbin/usermod -aG docker MY_USER`

* Run `make docker-build` to build the container images.

* Run `make up` to launch the containers.

* Enter the `gogocarto` container with this command: `make shell` or `docker exec -it gogocarto bash` (you can execute this one anywhere)

* Run `make init` from within the `gogocarto` container. This will launch all the required commands to finish the installation.

* Go to `http://localhost:3008/project/initialize` to initialize the project.

### Local docker-compose

If you have a specific environment, need for custom env vars, want to avoid exposing ports, etc, you can create a copy of `docker-compose.yml` named `docker-compose.local.yml` (it will be gitignored).


Manual Install
------------

Main requirements are :

1. PHP 7.4
2. [Composer](https://getcomposer.org/download/)
3. [Nodejs](https://nodejs.org/en/download/)
4. [MongoDB](http://php.net/manual/fr/mongodb.installation.php)
5. Web Server (Apache, Nginx)

Please refer to the dockerfile to know all dependencies : [DockerFile](../docker/Dockerfile)

You can also check [the Debian Buster Installation Guide](./installation_debian.md)

### Cloning Repository

```shell
git clone https://gitlab.com/seballot/gogocarto.git
```

### Initialize the Project

Create an `.env.local` file containing:

```
MONGODB_URL=mongodb://localhost:27017
```

Execute the command:

```shell
make init
```

Now initialize your project with the following route:

`http://localhost/GoGoCarto/web/project/initialize`

Multi Instance Mode (Software As A Service)
--------------------------

Instead of having a single GoGoCarto instance, you can transform your site in a "farm" by turning env variable USE_AS_SAAS to true. Every instance will use a subdomain, i.e if your base url is carto-farm.org, a new instance will be located at mymap.carto-farm.org with it's own database "mymap"
Note : If your base url is already a subdomain, i.e. carto.farm.org, your root database name should equals your root url subdomain name : `DATABASE_NAME=carto`. 

For Development
--------------

Start watching for file changes (automatic recompilation):

```shell
make watch
```

For Production
--------------

### Cron Tabs

Here are the following cron tab you need to configure 

```shell
# for a Normal instance
@daily php GOGOCARTO_DIR/bin/console --env=prod app:elements:checkvote
@daily php GOGOCARTO_DIR/bin/console --env=prod app:elements:checkExternalSourceToUpdate
@daily php GOGOCARTO_DIR/bin/console --env=prod app:notify-moderation

@hourly php GOGOCARTO_DIR/bin/console --env=prod app:users:sendNewsletter
*/5 * * * * php GOGOCARTO_DIR/bin/console --env=prod app:webhooks:post
```

```shell
# for a SAAS instance
* * * * * php GOGOCARTO_DIR/bin/console --env=prod app:project:update
# If you have more than 1400 project, you should run it twice a minute :
* * * * * sleep 30 && php GOGOCARTO_DIR/bin/console --env=prod app:project:update
# Task ran for every projects that need it at once
@hourly php GOGOCARTO_DIR/bin/console --env=prod app:users:sendNewsletter
*/5 * * * * php GOGOCARTO_DIR/bin/console --env=prod app:webhooks:post

@daily php GOGOCARTO_DIR/bin/console --env=prod app:projects:check-for-deleting
# Next one is for custom domain, it works only with NGINX
0 * * * * cd GOGOCARTO_DIR &&bash bin/execute_custom_domain.sh
```

### Updating GoGoCarto

Each time you want to update GoGoCarto, run:

```shell
# With gogocarto user
make gogo-update
```

You can have a look to [the CHANGELOG](../CHANGELOG.md) to know what are the new features and breaking changes.
