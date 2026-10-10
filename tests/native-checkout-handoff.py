"""HTTP regression on the loopback-only, synthetic ui-router.php fixture."""
import http.cookiejar
import json
import re
import urllib.error
import urllib.parse
import urllib.request
from html import unescape
from html.parser import HTMLParser

base = 'http://localhost:11080'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
html = client.open(base + '/checkout/?krev_ui_cart=services').read().decode()
assert 'name="customer_name"' in html
def hidden(key):
    return unescape(re.search(r'name="' + key + r'" value="([^"]+)"', html)[1])
day = re.search(r'<option value="(20\d\d-\d\d-\d\d)"', html)[1]
post = {'csrf': hidden('csrf'), 'request_key': hidden('request_key'),
        'store_cart_hash': hidden('store_cart_hash'), 'action': 'submit',
        'mode': 'prepaid_pickup_delivery', 'postcode': '94565',
        'customer_name': 'Synthetic Routing Buyer', 'preferred_date': day,
        'email': 'buyer@example.invalid', 'phone': '', 'address_1': '1 Synthetic Street',
        'address_2': '', 'city': 'Pittsburg', 'billing_state': 'CA', 'billing_country': 'US',
        'contact_share': 'yes', 'accept': 'yes'}
post.update(re.findall(r'name="(quantities\[\d+\])"[^>]*value="(\d+)"', html))
def send(url, data):
    return client.open(urllib.request.Request(url, data=urllib.parse.urlencode(data).encode(),
                       headers={'Origin': base, 'Referer': base + '/checkout/'}))
response = send(base + '/checkout/', post)
payment = response.read().decode()
assert 'KnifeRevive secure payment' in payment
booking = urllib.parse.parse_qs(urllib.parse.urlparse(response.url).query)['booking'][0]
params = json.loads(re.search(r'var wc_checkout_params = (\{.*?\});', payment)[1])
for key in ('wc_ajax_url', 'checkout_url'):
    assert urllib.parse.parse_qs(urllib.parse.urlparse(params[key]).query)['krev_booking_checkout'] == [booking], key
assert '%%endpoint%%' in params['wc_ajax_url']

class Inputs(HTMLParser):
    def __init__(self):
        super().__init__()
        self.values = {}
    def handle_starttag(self, tag, attrs):
        attr = dict(attrs)
        if tag == 'input' and attr.get('name') and 'disabled' not in attr:
            if attr.get('type') not in ('radio', 'checkbox') or 'checked' in attr:
                self.values[attr['name']] = attr.get('value', '')
inputs = Inputs()
inputs.feed(payment)
pay = inputs.values
pay.update({'billing_country': 'US', 'billing_state': 'CA', 'payment_method': 'stripe',
            'wc_stripe_selected_upe_payment_type': 'cashapp',
            'shipping_method[0]': re.search(r'name="shipping_method\[0\]"[^>]*value="([^"]+)"', payment)[1]})
checkout_url = urllib.parse.urljoin(base, params['checkout_url'])
# The route tag cannot grant a second browser access to the private booking.
try:
    urllib.request.urlopen(urllib.request.Request(checkout_url, data=urllib.parse.urlencode(pay).encode()))
    raise AssertionError('An ungranted browser must not reach checkout')
except urllib.error.HTTPError as error:
    assert error.code == 403
# The fixture gateway throws before any payment; reaching it proves the native
# booking guard and original quote/order binding both accepted the final POST.
result = json.loads(send(checkout_url, pay).read().decode())
assert result['result'] == 'failure', result
assert 'Synthetic browser must not initiate payment.' in result['messages'], result
assert 'Choose your sharpening day' not in result['messages'], result
order_id = re.search(r'Original order (\d+)\.', result['messages'])[1]
resume_url = base + '/?' + urllib.parse.urlencode({'krev_agent': 'booking', 'booking': booking, 'payment': '1'})
resume = client.open(resume_url).read().decode()
assert 'Pick up where you left off' in resume and 'KnifeRevive order ' + order_id in resume
resume_inputs = Inputs()
resume_inputs.feed(resume)
native = send(resume_url, resume_inputs.values)
native_html = native.read().decode()
assert '/order-pay/' + order_id in native.url or urllib.parse.parse_qs(urllib.parse.urlparse(native.url).query).get('order-pay') == [order_id], native.url
assert urllib.parse.parse_qs(urllib.parse.urlparse(native.url).query)['krev_booking_checkout'] == [booking]
assert 'woocommerce-pay-nonce' in native_html, re.sub('<[^>]*>', ' ', native_html)[-1800:]
native_inputs = Inputs()
native_inputs.feed(native_html)
native_inputs.values.update({'payment_method': 'stripe', 'woocommerce_pay': '1'})
retry = send(native.url, native_inputs.values).read().decode()
assert 'Original order ' + order_id + '.' in retry, re.sub('<[^>]*>', ' ', retry)[-1800:]
print('PASS: tagged final checkout; unauthorized browser denied; named recipient reaches fenced processor; booking link reopens original order-pay; retry reaches same order ID. No external charge/email.')
