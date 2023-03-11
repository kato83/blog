#!/bin/bash

PID=$$

echo $PID > /tmp/deployment.pid
echo "PROCESS ID: $PID"
/usr/bin/sleep 3

DEBOUNCE=$(cat /tmp/deployment.pid)
echo "RESULT: $PID, $DEBOUNCE"

if [ $PID = $DEBOUNCE ]; then
  echo 'S3 UPLOAD'
  /usr/local/bin/aws s3 sync --profile ${AWS_CLI_PROFILE} /root/deployment/ s3://${AWS_S3_BUCKET}/ --delete
  /usr/local/bin/aws cloudfront create-invalidation --distribution-id ${AWS_CF_ID} --paths "/*"
else
  echo 'SKIP S3 UPLOAD'
fi
