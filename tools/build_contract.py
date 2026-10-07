"""Generate the JSON OpenAPI document (also valid YAML) and plugin quick-start copy."""
from pathlib import Path
import json

ROOT = Path(__file__).resolve().parents[1]
S = {}
def obj(properties, required=(), extra=False):
    return {"type": "object", "properties": properties, "required": list(required), "additionalProperties": extra}
def ref(name): return {"$ref": f"#/components/schemas/{name}"}
def array(items): return {"type": "array", "items": items}
string = {"type": "string"}
integer = {"type": "integer", "minimum": 0}
opaque = {"type": "string", "pattern": "^[a-f0-9]{32}$"}
postal = {"type": "string", "pattern": "^[0-9]{5}$"}
S["Address"] = obj({k: string for k in ["address_1", "address_2", "city", "state", "postcode", "country"]}, ["address_1", "city", "state", "postcode", "country"])
S["Leg"] = obj({"kind": {"enum": ["customer_dropoff", "customer_collection", "courier_pickup", "courier_delivery"]}, "slot_id": string, "address": ref("Address")}, ["kind"])
S["Customer"] = obj({"name": string, "email": {"type": "string", "format": "email"}, "phone": string, "billing": ref("Address")}, ["name", "email", "billing"])
S["QuoteInput"] = obj({
    "items": {"type": "array", "minItems": 1, "maxItems": 10, "items": obj({"product_id": {"type": "integer", "minimum": 1}, "quantity": {"type": "integer", "minimum": 1, "maximum": 30}}, ["product_id", "quantity"])},
    "postal_code": postal, "intake": ref("Leg"), "return": ref("Leg"), "rail": {"enum": ["stripe_checkout", "lightning"]},
    "booking_mode": {"enum": ["scheduled", "pending_scheduling"]}, "customer": ref("Customer"), "requires_assessment": {"type": "boolean"}, "source": string
}, ["items", "postal_code", "intake", "return", "rail", "booking_mode"])
S["CheckoutInput"] = obj({"quote_id": opaque, "quote_hash": {"type": "string", "pattern": "^[a-f0-9]{64}$"}, "consent_id": opaque}, ["quote_id", "quote_hash", "consent_id"])
S["ChangeInput"] = obj({"action": {"enum": ["cancel", "reschedule"]}, "reason": {"type": "string", "maxLength": 300}}, ["action", "reason"])
S["Session"] = obj({"session_id": opaque, "session_token": string, "expires_at": string}, ["session_id", "session_token", "expires_at"])
S["Quote"] = obj({
    "quote_id": opaque, "expires_at": string, "quote_version": integer, "quote_hash": string, "currency": {"const": "USD"},
    "items": array(obj({"product_id": integer, "name": string, "quantity": integer, "subtotal_minor": integer, "total_minor": integer}, ["product_id", "name", "quantity", "subtotal_minor", "total_minor"])),
    "fees": array(obj({"name": string, "total_minor": integer}, ["name", "total_minor"])),
    "tax_minor": integer, "discount_minor": integer, "total_minor": integer, "rail": string, "booking_mode": string,
    "intake": ref("Leg"), "return": ref("Leg"), "location": string, "policy_url": string, "policy_version": string,
    "requires_customer": {"type": "boolean"}, "requires_manual_assessment": {"type": "boolean"},
    "return_policy_url": {"type": ["string", "null"]}, "review_url": string,
    "consent_id": {"type": ["string", "null"]}, "attempt_id": {"type": ["string", "null"]}
}, ["quote_id", "quote_hash", "total_minor", "review_url", "intake", "return", "rail", "booking_mode"])
S["Status"] = obj({
    "attempt_id": opaque, "order_reference": opaque,
    "payment_state": {"enum": ["creating", "unknown", "pending", "paid", "expired", "review_required", "refunded"]},
    "booking_state": {"enum": ["held", "confirmed", "pending_scheduling", "expired", "cancelled"]},
    "rail": string, "currency": {"const": "USD"}, "total_minor": integer, "hold_expires_at": string,
    "checkout_url": {"type": ["string", "null"]}, "invoice": {"type": ["object", "null"], "additionalProperties": True},
    "next_action": string, "intake": ref("Leg"), "return": ref("Leg"), "location": string,
    "fulfillment_stage": {"type": ["string", "null"]}, "woocommerce_status": {"type": ["string", "null"]},
    "environment": {"enum": ["live", "test"]}, "status_url": string
}, ["attempt_id", "payment_state", "booking_state", "rail", "total_minor", "environment"])
S["Error"] = obj({"code": string, "message": string, "data": obj({"status": integer, "request_id": string, "retryable": {"type": "boolean"}, "next_action": string}, [], True)}, ["code", "message", "data"])
S["CatalogProduct"] = obj({
    "product_id": integer, "variant_id": {"type": ["integer", "null"]}, "category": {"enum": ["technology", "sharpening"]}, "title": string,
    "canonical_url": string, "condition": {"type": ["string", "null"]},
    "attributes": {"type": "object", "additionalProperties": obj({"values": array(string), "verification": {"const": "seller_claim"}}, ["values", "verification"])},
    "currency": string, "unit_price_minor": {"type": ["integer", "null"], "minimum": 0}, "price_status": string,
    "availability_status": string, "available_quantity": {"type": ["integer", "null"]}, "purchasable": {"type": "boolean"},
    "fulfillment_options": array(string), "shipping_estimate_status": string, "fee_summary": obj({"status": string}, ["status"]),
    "return_policy_url": {"type": ["string", "null"]}, "policy_version": {"type": ["string", "null"]}, "updated_at": {"type": ["string", "null"]},
    "service_definition": {"type": ["string", "null"]}, "seller_id": integer, "checkout_mode": string
}, ["product_id", "title", "canonical_url", "unit_price_minor", "availability_status", "shipping_estimate_status", "policy_version"])
S["Catalog"] = obj({"schema_version": string, "items": array(ref("CatalogProduct")), "page": integer, "per_page": integer, "total": integer, "pages": integer, "fetched_at": string, "cache_ttl_seconds": integer}, ["items", "page", "total"])
S["Capabilities"] = {"type": "object", "required": ["schema_version", "merchant", "payment_rails", "sharpening", "technology"], "properties": {"schema_version": string, "merchant": string, "payment_rails": array(string), "sharpening": {"type": "object"}, "technology": {"type": "object"}}, "additionalProperties": True}
paths = {}
def route(path, method, operation, output=None, body=None, private=False, idem=False, query=()):
    op = {"operationId": operation, "responses": {"200": {"description": "Successful response", "content": {"application/json": {"schema": ref(output) if output else {"type": "object"}}}}, "default": {"description": "Structured error", "content": {"application/json": {"schema": ref("Error")}}}}, "security": [{"ShopperSession": []}] if private else []}
    params = []
    if "{id}" in path: params.append({"name": "id", "in": "path", "required": True, "schema": opaque})
    for name, schema, required in query: params.append({"name": name, "in": "query", "required": required, "schema": schema})
    if idem: params.append({"name": "Idempotency-Key", "in": "header", "required": True, "schema": {"type": "string", "pattern": "^[A-Za-z0-9:_-]{12,128}$"}})
    if params: op["parameters"] = params
    if body is not None: op["requestBody"] = {"required": True, "content": {"application/json": {"schema": ref(body) if body else obj({})}}}
    paths.setdefault(path, {})[method.lower()] = op
route("/capabilities", "GET", "capabilities", "Capabilities")
route("/catalog", "GET", "catalog", "Catalog", query=[("category", {"enum": ["technology", "sharpening"]}, False), ("search", string, False), ("page", {"type": "integer", "minimum": 1, "maximum": 100}, False), ("per_page", {"type": "integer", "minimum": 1, "maximum": 20}, False)])
route("/service-area", "GET", "area", query=[("postal_code", postal, True)])
route("/availability", "GET", "availability", query=[("postal_code", postal, True), ("kind", {"enum": ["customer_dropoff", "customer_collection", "courier_pickup", "courier_delivery"]}, False)])
route("/openapi", "GET", "openapi")
route("/sessions", "POST", "session", "Session", "", idem=True)
route("/sessions/attach", "POST", "attach", body="", private=True)
route("/quotes", "POST", "quote", "Quote", "QuoteInput", True, True)
route("/quotes/{id}", "GET", "getQuote", "Quote", private=True)
route("/checkout-attempts", "POST", "attempt", "Status", "CheckoutInput", True, True)
route("/checkout-attempts/{id}", "GET", "statusAttempt", "Status", private=True)
route("/orders/{id}", "GET", "statusOrder", "Status", private=True)
route("/orders/{id}/change-requests", "POST", "change", body="ChangeInput", private=True, idem=True)
route("/stripe/webhook", "POST", "webhook")
paths["/stripe/webhook"]["post"]["description"] = "Stripe-Signature on the unmodified raw request body is mandatory. This is a processor callback, not a shopper mutation."
contract = {"openapi": "3.1.0", "info": {"title": "KnifeRevive Agent Commerce", "version": "1.0.0", "description": "Local release candidate. Verify deployed capabilities before use."}, "servers": [{"url": "https://kniferevive.com/wp-json/kniferevive-agent/v1"}], "paths": paths, "components": {"securitySchemes": {"ShopperSession": {"type": "apiKey", "in": "header", "name": "X-Krev-Agent-Session"}}, "schemas": S}}
payload = json.dumps(contract, indent=2) + "\n"
for file in [ROOT / "openapi/kniferevive-agent-v1.yaml", ROOT / "wordpress/kniferevive-agent-commerce/assets/openapi.json"]:
    file.parent.mkdir(parents=True, exist_ok=True)
    file.write_text(payload, encoding="utf-8")
(ROOT / "wordpress/kniferevive-agent-commerce/assets/AI.md").write_bytes((ROOT / "AI.md").read_bytes())
print(f"Generated {len(paths)} API paths and packaged AI.md.")
