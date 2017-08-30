FROM node:latest

# Get dependencies
RUN apt-get update && apt-get install -y \
        gzip \
        git \
        curl \
        python \
        libssl-dev \
        pkg-config \
        build-essential

# Grap the latest version
RUN cd /opt && git clone https://github.com/ether/etherpad-lite.git etherpad

# Configuration
ADD conf/settings.json /opt/etherpad/settings.json

WORKDIR /opt/etherpad
ENTRYPOINT ["bin/run.sh", "--root"]