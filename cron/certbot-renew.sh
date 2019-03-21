
#!/bin/sh
PATH=/usr/local/bin:/usr/bin:/usr/local/sbin:/usr/sbin:/home/cam/.composer/vendor/bin:/home/cam/.local/bin:/home/cam/bin
echo "$(date): Certificate Renewal:"
echo
certbot renew
