FROM solr:5.5-alpine

ENV SOLR_CORE_DEFAULT d8
ENV SOLR_CORE_COMPANIES d8_companies
ENV SOLR_CORE_CONTACTS d8_contacts

# Create the cores for our project.
RUN bin/solr start && \
    bin/solr create_core -c $SOLR_CORE_DEFAULT && \
    bin/solr create_core -c $SOLR_CORE_COMPANIES && \
    bin/solr create_core -c $SOLR_CORE_CONTACTS && \
    bin/solr stop

# Copy the Drupal configuration into the cores.
COPY solr-conf/* /opt/solr/server/solr/$SOLR_CORE_DEFAULT/conf/
COPY solr-conf/* /opt/solr/server/solr/$SOLR_CORE_COMPANIES/conf/
COPY solr-conf/* /opt/solr/server/solr/$SOLR_CORE_CONTACTS/conf/
