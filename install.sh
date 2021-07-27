#!/bin/bash

set -e; 

echo "CURRENT PATH" 

pwd; 

echo "CURRENT USER" 

whoami; 

echo "COMPOSER UPDATE" 

rm -fr vendor 

composer install 

cp -v MySqlPlatform.php vendor/doctrine/dbal/lib/Doctrine/DBAL/Platforms/MySqlPlatform.php; 

if [ -d "vendor" ] 
then
    echo "Ok vendor directory is there continuing... " 
else
    echo "Error: Directory vendor does not exists."
    exit -1
fi
