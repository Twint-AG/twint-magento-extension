mount_dir=/data/html
app_dir=/var/www/html
while IFS= read -r folder
do
    mkdir -p $mount_dir/$folder
    mkdir -p $app_dir/$folder
    cp -af $app_dir/$folder/. $mount_dir/$folder
    rm -rf $app_dir/$folder
    ln -sf $mount_dir/$folder $app_dir/$folder
    chown -R www-data:www-data $app_dir/$folder
done < $app_dir/mount-folders.txt
