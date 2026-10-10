"""Only the isolated stand: mock verification, virtual product and cash-on-delivery."""
import json, urllib.request, urllib.error, http.cookiejar
base='http://127.0.0.1:18990'
jar=http.cookiejar.CookieJar(); client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
nonce=None

def request(route, method='GET', body=None):
 global nonce
 headers={'Content-Type':'application/json'}
 if nonce:headers['Nonce']=nonce
 req=urllib.request.Request(base+'/?rest_route=/wc/store/v1/'+route,data=json.dumps(body).encode() if body is not None else None,headers=headers,method=method)
 try:res=client.open(req)
 except urllib.error.HTTPError as e:res=e
 nonce=res.headers.get('Nonce') or nonce
 return res.status,json.loads(res.read())
assert request('cart')[0]==200
added=request('cart/add-item','POST',{'id':17,'quantity':1}); assert added[0] in (200,201),added
address={'first_name':'Isolated','last_name':'Test','address_1':'123 Test Street','city':'San Francisco','state':'CA','postcode':'94103','country':'US','phone':'5551234567','email':'rejected-store@example.org'}
body={'billing_address':address,'shipping_address':address,'payment_method':'cod','payment_data':[]}
code,result=request('checkout','POST',body)
assert code==400 and result.get('code')=='zbounce_email_rejected',(code,result)
print('Store API: real POST rejected missing mailbox before payment')
address['email']='catchall-store@example.org'
code,result=request('checkout','POST',body)
assert code==200 and result.get('payment_result',{}).get('payment_status')=='success',(code,result)
print('Store API: real POST accepted risky mailbox under default policy; virtual test order, no real payment or email')
