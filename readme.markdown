# No Agenda Player

## Deployment

Temporary command prefix: `export $(cat .environment | xargs) && php bin/console`

```
<VirtualHost *:80>
    ServerName noagenda.codedmonkey.com

    DocumentRoot /var/www/noagenda.codedmonkey.com/public
    <Directory /var/www/noagenda.codedmonkey.com/public>
        AllowOverride None
        Order Allow,Deny
        Allow from All

        <IfModule mod_rewrite.c>
            Options -MultiViews
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>

    # uncomment the following lines if you install assets as symlinks
    # or run into problems when compiling LESS/Sass/CoffeeScript assets
    # <Directory /var/www/noagenda.codedmonkey.com>
    #     Options FollowSymlinks
    # </Directory>

    # optionally disable the RewriteEngine for the asset directories
    # which will allow apache to simply reply with a 404 when files are
    # not found instead of passing the request into the full symfony stack
    <Directory /var/www/noagenda.codedmonkey.com/public/bundles>
        <IfModule mod_rewrite.c>
            RewriteEngine Off
        </IfModule>
    </Directory>
    
    ErrorLog /var/log/apache2/noagenda_error.log
    CustomLog /var/log/apache2/noagenda_access.log combined

    SetEnv APP_ENV=prod
    SetEnv APP_SECRET=86e65e43da3975441a202235908a0a52
    SetEnv MAILER_URL=null://localhost
    SetEnv DATABASE_URL=mysql://codedmonkey:password@127.0.0.1:3306/noagenda
</VirtualHost>
```

## CRON

Example CRON jobs for the 27th of May:
```
*/15 *  27  5   *     cd /var/www/noagenda.codedmonkey.com && export $(cat .environment | xargs) && php bin/console app:crawl-audio
0 17 27  5   *     cd /var/www/noagenda.codedmonkey.com && export $(cat .environment | xargs) && php bin/console app:crawl-chat
```
