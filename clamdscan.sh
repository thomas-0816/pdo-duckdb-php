#!/bin/sh

set -eu

echo "DetectPUA yes" >> /etc/clamav/clamd.conf
echo "LogClean yes" >> /etc/clamav/clamd.conf
echo "AlertBrokenExecutables yes" >> /etc/clamav/clamd.conf
echo "AlertBrokenMedia yes" >> /etc/clamav/clamd.conf
echo "AlertEncrypted yes" >> /etc/clamav/clamd.conf
echo "AlertEncryptedArchive yes" >> /etc/clamav/clamd.conf
echo "AlertEncryptedDoc yes" >> /etc/clamav/clamd.conf
echo "AlertExceedsMax yes" >> /etc/clamav/clamd.conf
echo "ExcludePath ^/app/.git/" >> /etc/clamav/clamd.conf

freshclam

clamd -F &

clamdscan --multiscan --wait /app
