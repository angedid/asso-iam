#!/bin/bash



echo "freeing server ports started..."

nohup fuser -n tcp -k  8000 &&
nohup fuser -n tcp -k  8001 &&
nohup fuser -n tcp -k  8002 &&
nohup fuser -n tcp -k  8003 &&
#nohup fuser -n tcp -k  8010 &&
#nohup fuser -n tcp -k  8013 &&
#nohup fuser -n tcp -k  8016 &&
#nohup fuser -n tcp -k  8017 &&
#nohup fuser -n tcp -k  8020 &&

echo "freeing server ports done..."



#Start all servers
echo "starting to launch the servers ..."

nohup php artisan serve  --host 0.0.0.0 >/dev/null 2>&1 &

nohup php ../mobilebiller-services/artisan serve --port 8001  --host 0.0.0.0 >/dev/null 2>&1 &

nohup php ../mobilebiller-payments/artisan serve --port 8002  --host 0.0.0.0 >/dev/null 2>&1 &

nohup php ../mobilebiller-wallet/artisan serve --port 8003  --host 0.0.0.0 >/dev/null 2>&1 &


echo "launching the servers done..."
