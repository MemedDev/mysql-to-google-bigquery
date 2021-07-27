#!/bin/bash

rm -fr vendor 

composer install 

if [ -d "vendor" ] 
then
    echo "Ok vendor directory is there continuing... " 
else
    echo "Error: Directory vendor does not exists."
    exit -1
fi
