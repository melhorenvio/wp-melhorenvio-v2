FROM wordpress:latest

# Install dependencies
RUN apt update && apt install less npm -y
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install WP CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp \
    && echo 'alias wp="wp --allow-root"' >> ~/.bashrc

EXPOSE 80
