Bright Game Panel File Manager
================


The plan for this is to extend the Bright Game Panel and add a per sever file manager, the only update to the core files
is the extra tab added to server.php.  Adding the tab code to any release so far should work.

This is far from finished I'll update this repo as I add new functionality.

Done

File lists returned from the respective server/boxes
File upload to the currently viewed folder.  (this was the deal breaker for me)
Recursive drill into folder structure. (and back)

Todo 

Delete Selected files/folders
Make Folder
Rename d=file/folder
Zip
Unzip


Copy the following file into your panel folder (not admin folder).


server.php
----------
This file is updated server details file with the extra file manager tab

server.php is taken from the Beta 8 release as its feels like a cleaner start point.


filemanagerajax.php
-------------------
Backend file for receiving the ajax actions from the server.php file.


filemanager.php
---------------
Optional standalone page used for the development, accepts serverid param.




Bright Game Panel itself is not my work but pulled from the following repo.

https://github.com/warhawk3407/bgpanel

