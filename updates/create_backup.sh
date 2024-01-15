#!/bin/bash
# ls -1tr | head -n -10 | xargs -d '\n' rm -f --
# Add some entries for README.md and testing management pages
#
function doit() {
# when you use 'set -x' and run this from the web page you get the extra output in /var/log/apache2/error.log
#set -x
    backup_location=$1
    backup_type=$2
    backup_versions=$3
    #
    # Check if backup folder begins with a /
    #
    case $backup_location in
    /*)
        backup_folder=$backup_location
        ;;
    *)
        #
        # Assume backup folder is relative to application folder
        #
        backup_folder="$(pwd)/$backup_location"
        ;;
    esac
    #
    # Try to create folder when it does not exist
    #
    mkdir -p $backup_folder 2>/dev/null
    #
    # Check if folder is writable
    #
    if ! [ -w "$backup_folder" ]
    then
        echo "<br><br>Error : Folder '$backup_folder' is not writable"
    else
        source="$(pwd)"
        if [[ "$backup_type" == "tar" ]]
        then
            #
            # When you want to use tar
            #
            backup="$backup_folder/Backup_$(date -I'seconds' | cut -b 1-19 | sed 's/T/_/' | sed 's/:/-/g').tar"
            cmd="tar -cvf $backup --exclude=backups --exclude=$backup_folder  -C $source . "
            # This tar command does the following:
            #
            #    -cvf: Create a new archive and show the progress.
            #    $backup: Specify the name and path of the archive you are creating.
            #    --exclude=backups: Exclude the "backups" directory from the archive.
            #    -C $source: Change to the specified directory before performing the operation.
            #    .: Include the current directory (i.e., the contents of $source) in the archive.
        fi
        if [[ "$backup_type" == "zip" ]]
        then
            #
            # When you want to use zip I check if it is installed
            #
            which zip >/dev/null
            #
            if [[ $? != 0 ]]
            then
                #
                # zip is not installed so use tar anyway
                #
                echo "<br><h1>You do not have zip installed so I use tar anyway.</h1>"
                backup="$backup_folder/Backup__$(date -I'seconds' | cut -b 1-19 | sed 's/T/_/' | sed 's/:/-/g').tar"
                cmd="tar -cvf $backup --exclude=backups --exclude=$backup_folder  -C $source . "
            else
                #
                # zip is installed
                #
                backup="$backup_folder/Backup_$(date -I'seconds' | cut -b 1-19 | sed 's/T/_/' | sed 's/:/-/g').zip"
                cmd="zip -r $backup $source --exclude=$(pwd)/backups/* --exclude=$backup_folder/*"
                #
                #This zip command does the following:
                #
                #    -r: Recursively include directories.
                #    /var/www/html/updatesdev/backups/updates_2024-01-05_21-46-20.zip: Specifies the name and path of the zip file.
                #    /var/www/html/updatesdev: Specifies the source directory.
                #    --exclude=/some/folder/*: Excludes the "/some/folder" directory from the zip.
            fi
        fi

        $cmd > /dev/null 2>&1

        cd $backup_folder

        #
        # Cleanup old backups
        #

        ls -1tr *.tar *.zip 2>/dev/null | head -n -$backup_versions | xargs -d '\n' rm -f --

        echo "<html><head><title>Backup Results</title></head><body>"
        echo "<br><br>Backup of '$source' saved in '$backup'"

        #
        # List last 10 backups
        #
        
        echo "<br><br>Last backups (I show max 10 of $backup_versions) :<br>"

        ls -p -l --block-size=K *.zip *.tar 2>/dev/null | grep -v / | awk '{print $9, $5}' | sort | nl -w2 -n rz | sort -r | head -n 10 | sed 's/^/<br>/'| sed 's/	Backup/) Backup/'
        echo "<body></html>"

    fi

}

doit $1 $2 $3
