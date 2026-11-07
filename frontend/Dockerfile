FROM nginx:1.27-alpine

# Dirección interna del backend al que se reenvían /api, /admin y las fotos
ENV BACKEND_URL=http://backend

# La imagen de nginx reemplaza las variables de las plantillas al arrancar
COPY nginx.conf.template /etc/nginx/templates/default.conf.template
COPY public/ /usr/share/nginx/html/

EXPOSE 80
