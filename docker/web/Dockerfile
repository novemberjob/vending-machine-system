FROM nginx:1.27-alpine

COPY docker/web/vms.conf /etc/nginx/conf.d/default.conf
COPY public/ /var/www/html/public/

EXPOSE 80
