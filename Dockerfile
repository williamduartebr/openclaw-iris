FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    curl \
    git \
    make \
    g++ \
    cmake \
    python3 \
    ca-certificates \
    gnupg \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && ln -sf /usr/bin/python3 /usr/local/bin/python \
    && npm install -g openclaw \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /root

EXPOSE 18789

CMD ["openclaw", "gateway", "run", "--bind", "lan"]
