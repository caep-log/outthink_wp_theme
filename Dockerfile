FROM wordpress:php8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite