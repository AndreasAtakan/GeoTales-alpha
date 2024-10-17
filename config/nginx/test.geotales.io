server {
	listen 80;
	listen [::]:80;

	root /var/www/html/geotales_test;

	# Add index.php to the list if you are using PHP
	index index.php index.html index.htm index.nginx-debian.html;

	server_name test.geotales.io;

	# SSL configuration
	listen 443 ssl;
	listen [::]:443 ssl;

	ssl_certificate /etc/letsencrypt/live/test.geotales.io/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/test.geotales.io/privkey.pem;

	include /etc/letsencrypt/options-ssl-nginx.conf;

	more_set_headers 'Access-Control-Allow-Origin: *';

	# Redirect non-https traffic to https
	if ($scheme != "https") {
		return 301 https://$host$request_uri;
	}

	location / { try_files $uri $uri/ =404; }

	# pass PHP scripts to FastCGI server
	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
	}

	# deny access to .htaccess files, if Apache's document root
	# concurs with nginx's one
	location ~ /\.ht { deny all; }
}
