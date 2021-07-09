# How to restore a project 

Clean database if still exists
```
mongo
use XXX
db.dropDatabase()
exit
```

Restore DB
```
mongorestore -d=XXX /var/backups/mongobackups/DATE/XXX
```

Also you can check on `var/log/projects` the log who tell which person have deleted the project (if nobody have, seems a serious bug!!)