FROM onlyoffice/documentserver-de:6.3.1.32@sha256:23a260ffad316bf641907c56c9b81cbfe81612effec5c513f9add98e1a70b7dc AS deps
RUN apt-get update && apt-get install -y --no-install-recommends python3 python3-pycryptodome && rm -rf /var/lib/apt/lists/*
FROM deps AS patched
COPY patch.py /opt/hr-patch/patch.py
WORKDIR /opt/hr-patch
RUN python3 patch.py
COPY entrypoint.sh /opt/hr-patch/entrypoint.sh
RUN chmod +x /opt/hr-patch/entrypoint.sh
ENTRYPOINT ["/opt/hr-patch/entrypoint.sh"]
