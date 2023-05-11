FROM onlyoffice/documentserver

RUN mkdir -p /app/onlyoffice/DocumentServer/data/certs && \
    openssl req -x509 -nodes -days 365 -subj "/C=CA/ST=QC/O=Company Inc/CN=localhost" \
    	-newkey rsa:2048 -keyout /app/onlyoffice/DocumentServer/data/certs/onlyoffice.key \
    	-out /app/onlyoffice/DocumentServer/data/certs/onlyoffice.crt && \
    chmod 400 /app/onlyoffice/DocumentServer/data/certs/onlyoffice.key
