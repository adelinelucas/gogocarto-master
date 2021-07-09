#!/bin/bash

# Log into a file
exec 3>&1 1>>var/log/subdomains.log 2>&1

for file in var/file_queues/custom_domain_to_remove/*
do    
    if [ -e $file ] 
    then
        fileContent=`cat $file`
        CUSTOM_URL=($fileContent) # get first word
        echo $'\n\n\n'
        echo "REMOVING SUBDOMAIN for $file : $CUSTOM_URL"
        if [ -n "$CUSTOM_URL" ]; then
            echo "Remove previous certificates if exists"
            if [ -e /etc/letsencrypt/live/$CUSTOM_URL/cert.pem ]; then
                certbot revoke -n --cert-path /etc/letsencrypt/live/$CUSTOM_URL/cert.pem            
            fi 
            echo "Delete previous nginx conf"
            rm /etc/nginx/sites-available/$CUSTOM_URL /etc/nginx/sites-enabled/$CUSTOM_URL
            rm $file       
            echo "Reload Nginx" 
            /etc/init.d/nginx reload     
        fi
    fi
done

for file in var/file_queues/custom_domain_to_configure/*
do    
    if [ -e $file ] 
    then
        echo $'\n\n\n'
        if [[ $(find "$file" -mmin +300 -print) ]]; then
            echo "DELET OLD FILE : File $file is older than 5 hours, so deleting it"
            mv $file var/file_queues/custom_domain_to_remove/
        else
            arguments=`cat $file`
            echo "CONFIGURE NEW DOMAIN for $file : $arguments"

            if sh bin/configure_custom_domain.sh $arguments
            then
                echo "Successfully configured $file, the file queue can now be deleted"
                sleep 10 && /etc/init.d/nginx reload 
                rm $file
            else
                echo "Problem while configuring $file"
            fi    
        fi        
    fi
done
