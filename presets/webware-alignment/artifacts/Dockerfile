# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Webware alignment tooling image
#
# Builds a self-contained PHP development environment carrying the Webware
# toolchain: PHP CLI, Composer, and Mago. Consumers build this image locally
# via `docker compose build` and run every tool (composer, phpunit, mago,
# infection, phpbench, bc-check) inside the container, so the host machine
# (Windows, WSL, Linux, or macOS) never needs a native PHP toolchain.
#
# All files in this repository are committed with LF line endings (see
# .gitattributes), which keeps the container and host line endings consistent.
# ---------------------------------------------------------------------------

# PHP version to base the image on (latest 8.4 patch release). Keep in sync
# with the package's supported PHP versions and `config.platform.php`.
ARG PHP_VERSION=8.4.24

# Composer version (copied from the official composer image at build time).
ARG COMPOSER_VERSION=2

# Named stage so Composer can be copied from it (--from does not support
# inline variable expansion).
FROM composer:${COMPOSER_VERSION} AS composer

FROM php:${PHP_VERSION}-cli

# Mago version to install. Keep in sync with the central `mago.toml`
# `version =` pin in webware/webware-tools.
ARG MAGO_VERSION=1.47.2

# ---------------------------------------------------------------------------
# System packages + PHP extensions
# ---------------------------------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libicu-dev \
        libzip-dev \
        unzip \
    ; \
    docker-php-ext-install -j"$(nproc)" \
        intl \
        pcntl \
        zip \
    ; \
    pecl install pcov \
    && docker-php-ext-enable pcov \
    ; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Mago (statically linked Rust binary). Select the correct release asset for
# the build target architecture so the same Dockerfile works on x86_64 and
# arm64 hosts.
# ---------------------------------------------------------------------------
ARG TARGETARCH
RUN set -eux; \
    case "${TARGETARCH}" in \
        amd64) MAGO_TRIPLE="x86_64-unknown-linux-gnu" ;; \
        arm64) MAGO_TRIPLE="aarch64-unknown-linux-gnu" ;; \
        *) echo "unsupported TARGETARCH: ${TARGETARCH}" >&2; exit 1 ;; \
    esac; \
    curl -fsSL \
        "https://github.com/carthage-software/mago/releases/download/${MAGO_VERSION}/mago-${MAGO_VERSION}-${MAGO_TRIPLE}.tar.gz" \
        -o /tmp/mago.tar.gz; \
    tar -xzf /tmp/mago.tar.gz -C /tmp; \
    install -m 0755 "/tmp/mago-${MAGO_VERSION}-${MAGO_TRIPLE}/mago" /usr/local/bin/mago; \
    rm -rf /tmp/mago.tar.gz "/tmp/mago-${MAGO_VERSION}-${MAGO_TRIPLE}"; \
    mago --version

WORKDIR /app
