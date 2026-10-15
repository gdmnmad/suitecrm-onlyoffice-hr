#!/usr/bin/env python3
import http.cookiejar,json,os,urllib.request,urllib.parse,pathlib
config={}
p=pathlib.Path(__file__).resolve().parents[1]/'.env'
if p.is_file():config=dict(line.split('=',1) for line in p.read_text().splitlines() if '=' in line and not line.startswith('#'))
base=os.environ.get('HR_CHECK_URL','http://localhost:8080');login=os.environ.get('HR_CHECK_USER',config.get('CRM_ADMIN_USER','admin'));password=os.environ.get('HR_CHECK_PASSWORD',config.get('CRM_ADMIN_PASSWORD',''))
if not password:raise SystemExit('Set HR_CHECK_PASSWORD')
jar=http.cookiejar.CookieJar();client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
def csrf():return next((urllib.parse.unquote(c.value) for c in jar if c.name=='XSRF-TOKEN'),'')
def post(path,data=None):
 request=urllib.request.Request(base+path,data=json.dumps(data or {}).encode(),headers={'Content-Type':'application/json','X-XSRF-TOKEN':csrf()},method='POST')
 return client.open(request,timeout=20)
client.open(base+'/',timeout=20).read()
result=json.load(post('/login',{'username':login,'password':password}));assert result.get('login_success'), 'Login failed'
page=client.open(base+'/legacy/index.php?entryPoint=HrAudit',timeout=20).read().decode();assert 'История изменений' in page,'Authenticated page inaccessible';assert 'data-hr-logout' in page,'Logout button missing'
print('PASS login and visible logout button')
post('/logout').read()
page=client.open(base+'/legacy/index.php?entryPoint=HrAudit',timeout=20).read().decode();assert 'История изменений' not in page,'Protected data accessible after logout'
print('PASS logout invalidates server session and blocks protected page')
