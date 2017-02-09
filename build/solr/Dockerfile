FROM solr:5.5-alpine

ENV SOLR_CORE_DEFAULT d8
ENV SOLR_CORE_COMPANIES d8_companies

# Create the cores our project.
RUN bin/solr start && \
    bin/solr create_core -c $SOLR_CORE_DEFAULT && \
    bin/solr create_core -c $SOLR_CORE_COMPANIES && \
    bin/solr stop

# Copy the Drupal module's configuration into the core.
COPY solr-conf/* /opt/solr/server/solr/$SOLR_CORE_DEFAULT/conf/
COPY solr-conf/* /opt/solr/server/solr/$SOLR_CORE_COMPANIES/conf/
