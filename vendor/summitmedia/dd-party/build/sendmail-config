#!/usr/bin/env bash

line=$(head -n 1 /etc/hosts)
line2=$(echo $line | awk '{print $2}')
line3=$(hostname)

add="$line $line2.localdomain $line3"

if ! grep -Fx "$add" /etc/hosts >/dev/null 2>/dev/null; then
    echo "Updating" /etc/hosts

    echo "$add" >> /etc/hosts
fi
