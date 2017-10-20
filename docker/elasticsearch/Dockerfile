FROM docker.elastic.co/elasticsearch/elasticsearch:5.4.0

#RUN /usr/share/elasticsearch/bin/elasticsearch-plugin remove x-pack

RUN /usr/share/elasticsearch/bin/elasticsearch-plugin install --batch mapper-attachments
#RUN /usr/share/elasticsearch/bin/elasticsearch-plugin install --batch ingest-attachment