FROM httpd:alpine

RUN apk add --no-cache curl

# Create a non-root user (UID 1001) with primary group root (GID 0).
# The bind-mounted project directory appears as drwxr-x--- root:root inside
# Docker, so the worker processes need GID 0 to traverse it.
RUN adduser -D -u 1001 -G root app

RUN sed -i \
    -e 's|#LoadModule rewrite_module|LoadModule rewrite_module|' \
    -e 's|#LoadModule proxy_module|LoadModule proxy_module|' \
    -e 's|#LoadModule proxy_fcgi_module|LoadModule proxy_fcgi_module|' \
    -e 's|#Include conf/extra/httpd-vhosts.conf|Include conf/extra/httpd-vhosts.conf|' \
    -e 's|User www-data|User app|' \
    -e 's|Group www-data|Group root|' \
    /usr/local/apache2/conf/httpd.conf

COPY vhost.conf /usr/local/apache2/conf/extra/httpd-vhosts.conf
