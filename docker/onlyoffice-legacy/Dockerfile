FROM onlyoffice/documentserver:6.3.1.32
# Unused LuaJIT module cannot initialise under amd64 emulation on this Mac.
RUN rm -f /etc/nginx/modules-enabled/50-mod-http-lua.conf
