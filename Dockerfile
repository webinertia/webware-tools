# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Webware tooling development environment image
#
# Builds a self-contained, interactive environment carrying PHP and Mago.
# Developers work inside this container through compose.yml, so the host
# machine (Windows, WSL, Linux, or macOS) never needs a native toolchain.
#
# All files in this repository are committed with LF line endings (see
# .gitattributes), which keeps the container and host line endings consistent.
# ---------------------------------------------------------------------------

# PHP version to base the image on. Keep in sync with `php-version` in
# mago.toml.
ARG PHP_VERSION=8.5.12

FROM php:${PHP_VERSION}-cli

# ---------------------------------------------------------------------------
# System packages
# ---------------------------------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        unzip \
    ; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# Mago (statically linked Rust binary).
#
# The version is NOT pinned in this file. It is read from the `version =` pin in
# this repository's mago.toml, so the tool inside the container always matches
# the pin the config itself enforces. That pin has exactly one home.
#
# The release asset is selected for the build target architecture so the same
# Dockerfile works on x86_64 and arm64 hosts.
# ---------------------------------------------------------------------------
COPY mago.toml /tmp/webware-tools-mago.toml
ARG TARGETARCH
RUN set -eux; \
    case "${TARGETARCH}" in \
        amd64) MAGO_TRIPLE="x86_64-unknown-linux-gnu" ;; \
        arm64) MAGO_TRIPLE="aarch64-unknown-linux-gnu" ;; \
        *) echo "unsupported TARGETARCH: ${TARGETARCH}" >&2; exit 1 ;; \
    esac; \
    MAGO_VERSION="$(sed -nE 's/^[[:space:]]*version[[:space:]]*=[[:space:]]*"([0-9]+\.[0-9]+\.[0-9]+)".*/\1/p' /tmp/webware-tools-mago.toml | head -n1)"; \
    if [ -z "${MAGO_VERSION}" ]; then \
        echo "no version pin in mago.toml" >&2; \
        exit 1; \
    fi; \
    curl -fsSL \
        "https://github.com/carthage-software/mago/releases/download/${MAGO_VERSION}/mago-${MAGO_VERSION}-${MAGO_TRIPLE}.tar.gz" \
        -o /tmp/mago.tar.gz; \
    tar -xzf /tmp/mago.tar.gz -C /tmp; \
    install -m 0755 "/tmp/mago-${MAGO_VERSION}-${MAGO_TRIPLE}/mago" /usr/local/bin/mago; \
    rm -rf /tmp/mago.tar.gz "/tmp/mago-${MAGO_VERSION}-${MAGO_TRIPLE}" /tmp/webware-tools-mago.toml; \
    mago --version

WORKDIR /app
