DST=/var/www/churchinfo
# For XAMPP on Mac
if [ "$(uname)" == "Darwin" ]; then
  DST=/Applications/XAMPP/htdocs/churchinfo
fi

cp Reports/*.php $DST/Reports/ 
cp *.php $DST/
cp -R Include/ $DST/ 
