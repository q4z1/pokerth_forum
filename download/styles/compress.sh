#!/bin/bash
for dir in ./*/ 
do
    dir=${dir%*/}      # remove the trailing "/"
    zip -rq ${dir##*/}.zip ${dir##*/}
done
