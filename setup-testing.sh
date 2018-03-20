#!/usr/bin/env bash

rm -rf ./vendor/bunq/sdk_php
git clone git@gitlab.bunq.net:system/sdk_php.git ./vendor/bunq/sdk_php
composer install -d ./vendor/bunq/sdk_php
cat << EOL > ./vendor/bunq/sdk_php/src/Http/Certificate/api.bunq.com.pubkey.pem
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1jctYbkLgJEHvE9WYBK4
cLmSnfLoEfFlNskYyjUJrsAbjNbID+UCAeLA4cE/gA5IlGZ9u5Qn9cjuS+nakaJv
guc9hI1G7Z3X87bapZJL2daNRRrOosi2U2p154Dmoc0QdfiEXw3vpTVs3FfAc5rw
L8h11GTRLclbyQ/UHEv3JMDtQbmC44GkX0Pa2PD+hKCyqvVEPALnjZS4OuhZ/BEz
fkG/Gd2pyIzv5LKKvVfRa00HJXiDOxy84Mea+5SSwv/Kb5i/unMO4d1pLG3iSKGz
qTA2xHRbuZ1Aq+6VbxjxWBfcj/+eN1vNBlMeR+gfr+cGO6t3ZKlPUXdlKmE6uF36
CQIDAQAB
-----END PUBLIC KEY-----
EOL
sed -i -- 's|https://api.bunq.com/v1/|https://public.api.triage.bunq.net/v1/|g' ./vendor/bunq/sdk_php/src/Context/ApiContext.php
sed -i -- 's|https://api.credential.bunq.com/v1/|https://api.credential.triage.bunq.net/v1/|g' ./go-pro

