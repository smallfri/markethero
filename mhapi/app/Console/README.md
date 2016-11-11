MailWizz - Email marketing application
========
    
INSTALL NOTES: (Video: https://www.youtube.com/watch?v=wg0xVSp8lm0)  
1.Upload the contents of the "latest" folder on your web hosting account.  
2.Point your browser to the install directory of mailwizz (i.e: http://www.mydomain.com/install)  
3.Follow the install wizzard  
4.After installing, make sure you remove the install folder from your host.  
5.Visit http://www.mydomain.com/backend to get started  
    
===============================================================  
    
UPDATE NOTES: (Video: https://www.youtube.com/watch?v=zkdBer0iQbo)  
Before starting the update process, make sure you backup your files and database, this is very important.  
You can also download the Backup Manager extension from CodeCanyon in order to ease the backup process:  
http://codecanyon.net/item/backup-manager-for-mailwizz-ema/8184361?ref=twisted1919&WT.ac=new_item&WT.z_author=twisted1919  
    
1.Make sure you are not running any campaign when updating (pause them if needed)  
2.Move your application offline from Backend -> Settings -> Common  
3.Upload the contents of "update" folder on your host, overriding the existing files  
4.Point your browser to http://www.yourdomain.com/backend/index.php/update  
5.Follow the update wizard  

===============================================================  
    
Please also read the CHANGELOG file as it highlights important changes you should be aware of.  
    
Additional update notes  
The update script will try to clear the contents of the following folders:  
/apps/common/runtime/cache/  
/backend/assets/cache/  
/customer/assets/cache/  
/frontend/assets/cache/  
    
If for some reason it fails, please make sure you delete the contents manually and make sure these folders remain writable by the web server.   
    
===============================================================  
As always, you can get more info from www.mailwizz.com website.  
If you get stuck, please use the website to contact me.  
Thanks.  
===============================================================    