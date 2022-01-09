#!/usr/bin/env bash

#boilerplate script http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail
IFS=$'\n\t'

#for the installation logs

log_file="inst_script.log"

#use default locale
export LC_ALL=C

#Update OS
DEBIAN_FRONTEND=noninteractive
export DEBIAN_FRONTEND
apt-get update -y && apt-get upgrade -yq -o Dpkg::Options::="--force-confdef" --allow-downgrades --allow-remove-essential --allow-change-held-packages --allow-change-held-packages --allow-unauthenticated;

#use default locale
export LC_ALL=C

#Install prerequisites to launch ansible playbook
apt-get install python3 python3-pip python3-setuptools python-setuptools python3-apt python3-venv  build-essential libssl-dev libffi-dev --force-yes -y

#Install ansible
pip3 install ansible

#Automatically apply ssh keys for ansible connections

export ANSIBLE_HOST_KEY_CHECKING=False

#Run ansible playbook on localhost
ansible-playbook -i hosts.yml main.yml