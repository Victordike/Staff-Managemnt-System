cd c:\xampp\htdocs\StaffManagerPro  
c:\xampp\php\php.exe -r \"^$file='admin_dashboard.php';^$c=file_get_contents(^$file);^$c=str_replace('from-orange-500 to-red-600','!from-orange-500 !to-red-600',^$c);^$c=str_replace('from-red-500 to-rose-600','!from-red-500 !to-rose-600',^$c);file_put_contents(^$file,^$c);\"  
