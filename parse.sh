#!/bin/bash
FILES=/var/www/EtherRS/*.php
for f in $FILES
do
  php -l "$f"
done