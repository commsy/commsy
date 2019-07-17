FROM docker.elastic.co/elasticsearch/elasticsearch:5.6.16

#RUN /usr/share/elasticsearch/bin/elasticsearch-plugin remove x-pack

RUN /usr/share/elasticsearch/bin/elasticsearch-plugin install --batch mapper-attachments
#RUN /usr/share/elasticsearch/bin/elasticsearch-plugin install --batch ingest-attachment