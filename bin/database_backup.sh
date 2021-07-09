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