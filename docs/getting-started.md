# Getting started

## Setup

First of all, **git fork** our [project's Github][1]
or [download it directly][2].

Then you need an environnement to run the example site or your new project.
Download [WAMP][3], [XAMP][4] or make your **LAMP**. 
It does work with IIS too.

We suggest not to put the framework (*cmless* directory) in the project, 
so put it somewhere near but keep it in mind for the configuration.
You will also need to either copy the [/cmless/static][5] into your statics or (preferably)
do an alias with your virtual host (see below for example).

[1]: https://github.com/BorealHub/cmless
[2]: https://github.com/BorealHub/cmless/archive/master.zip
[3]: http://www.wampserver.com/
[4]: https://www.apachefriends.org/
[5]: https://github.com/BorealHub/cmless/tree/master/cmless/static

## URL rewriting

You need to install (if it's not already) [mod_rewrite][6] 
extension in order to make it work. 

You can either make a **.htaccess** on the project's root with the following rules 
or write them in the project's [Virtual Host][7]:

    DirectoryIndex index.php
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [L,QSA]
	
This is the Virtual Host example (assuming you set this ServerName):

    <VirtualHost *:80>
      ServerName example.cmless
      ServerAlias www.example.cmless
      DocumentRoot "/mnt/sites/exemple"
      DirectoryIndex index.php
	  RewriteEngine On
	  RewriteBase /
	  RewriteCond %{REQUEST_FILENAME} !-f
	  RewriteRule ^(.*)$ index.php/$1 [L,QSA]
      <Directory "/mnt/sites/exemple">  
        AllowOverride All  
        Allow from All  
      </Directory>
      Alias /static/cmless /mnt/cmless/static
    </VirtualHost>
	
IIS users will have to write a **web.config** file with something similar to this:

    <?xml version="1.0" encoding="UTF-8"?>
    <configuration>
      <system.webServer>
        <directoryBrowse enabled="false" />
        <rewrite>
          <rules>
            <rule name="Cmless rule" stopProcessing="true">
              <match url="." ignoreCase="false" />
              <conditions>
                <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
                <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" negate="true" />
              </conditions>
              <action type="Rewrite" url="index.php" appendQueryString="true" />
            </rule> 
          </rules>
        </rewrite>
      </system.webServer> 
    </configuration>
	
[6]: http://httpd.apache.org/docs/current/mod/mod_rewrite.html
[7]: http://httpd.apache.org/docs/2.2/vhosts/

## Database

Sadly, the current version of Cmless only support MySQL.

You have the *.sql* you need to run the example site in [/example/database][8].

[8]: https://github.com/BorealHub/cmless/tree/master/example/database

## Example

The project feature a full implementation of the framework in [/example/][9].
To make it work, import the database, follow the above instructions and modify
the configuration in [config.php][10], make sure the **'cmless_path'** and **'db'**
is set properly.

> Making a "setup on launch" for the basic DB structure and values would be great!

[9]: https://github.com/BorealHub/cmless/tree/master/example/
[10]: https://github.com/BorealHub/cmless/tree/master/example/config.php
