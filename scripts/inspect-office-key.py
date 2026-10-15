import re, hashlib
from pathlib import Path
for f in ['DocService/docservice','FileConverter/converter']:
 data=Path('/var/www/onlyoffice/documentserver/server/'+f).read_bytes()
 keys=re.findall(rb'-----BEGIN PUBLIC KEY-----[\s\S]{0,1500}?-----END PUBLIC KEY-----',data)
 print(f,'PEM count',len(keys))
 for key in keys:
  print('length',len(key),'sha256',hashlib.sha256(key).hexdigest(),'escaped_newlines',b'\\n' in key,'actual_newlines',b'\n' in key)
  print(repr(key))
