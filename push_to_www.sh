DST=/var/www/churchinfo
# For XAMPP on Mac
if [ "$(uname)" == "Darwin" ]; then
  DST=/Applications/XAMPP/htdocs/churchinfo
fi

cp Reports/*.php $DST/Reports/
cp *.php $DST/
# For mac, cannot use cp -R Include/. Otherwise, it copy contents not dir.
cp -R Include $DST/
