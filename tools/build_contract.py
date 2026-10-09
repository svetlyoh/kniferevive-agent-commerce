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
S["BookingInput"] = obj({"items": S["QuoteInput"]["properties"]["items"], "mode": {"enum": ["pay_later_dropoff", "prepaid_dropoff", "prepaid_pickup"]}, "preferred_date": {"type": "string", "format": "date"}, "postal_code": postal, "return_mode": {"enum": ["customer_collection", "courier_delivery"]}, "customer": obj({"name": string, "email": {"type": "string", "format": "email"}, "phone": string}, ["name", "email"]), "pickup_address": ref("Address"), "notes": {"type": "string", "maxLength": 500}}, ["items", "mode", "preferred_date"])
S["Booking"] = obj({"booking_id": opaque, "booking_state": {"enum": ["draft", "awaiting_payment", "requested", "confirmed", "cancelled"]}, "payment_state": string, "mode": string, "items": array(obj({"product_id": integer, "title": string, "quantity": integer, "unit_price_minor": integer}, ["product_id", "title", "quantity", "unit_price_minor"])), "preferred_window": obj({"date": string, "start_at": string, "end_at": string, "timezone": string}, ["date", "start_at", "end_at", "timezone"]), "return_mode": string, "location": string, "currency": string, "service_subtotal_minor": integer, "merchant_trip_fee_minor": integer, "estimated_subtotal_minor": integer, "total_minor": {"type": "null"}, "estimate_only": {"const": True}, "prepayment_enabled": {"type": "boolean"}, "policy_url": {"type": ["string", "null"]}, "review_url": string, "status_url": string, "appointment_confirmed": {"type": "boolean"}, "refund_state": {"const": "not_issued"}, "expires_at": string}, ["booking_id", "booking_state", "payment_state", "mode", "items", "preferred_window", "estimated_subtotal_minor", "total_minor", "estimate_only", "review_url", "status_url", "appointment_confirmed", "refund_state"])
S["BookingCoverage"] = obj({"postal_code": {"type": "string", "pattern": "^([0-9]{5})?$"}, "coverage_state": {"enum": ["not_required", "eligible", "bay_area_dropoff_only", "outside_bay_area", "address_review_required"]}, "service_available": {"type": "boolean"}, "pickup_eligible": {"type": "boolean"}, "prepayment_eligible": {"type": "boolean"}, "address_review_required": {"type": "boolean"}, "counties": array(string), "message": string, "source_url": string, "geography_vintage": string}, ["postal_code", "coverage_state", "service_available", "pickup_eligible", "prepayment_eligible", "address_review_required", "message"])
S["Booking"]["properties"].update({"coverage": ref("BookingCoverage"), "direct_wallet_enabled": {"const": False}, "booking_access_token": string})
refund_state = {"enum": ["not_issued", "full_gateway_accepted", "partial_gateway_accepted", "manual_review_required"]}
S["RefundSummary"] = obj({"refund_state": refund_state, "refund_recorded_minor": integer, "refund_gateway_accepted_minor": integer,
    "refunds": array(obj({"reference": string, "amount_minor": integer, "state": {"enum": ["gateway_accepted", "manual_record_only"]}}, ["reference", "amount_minor", "state"])),
    "refund_arrival_verified": {"const": False}, "refund_destination": {"type": ["string", "null"]}},
    ["refund_state", "refund_recorded_minor", "refund_gateway_accepted_minor", "refunds", "refund_arrival_verified", "refund_destination"])
S["Booking"]["properties"].update({"refund_state": refund_state, "refund_summary": ref("RefundSummary")})
# Portable draft creation accepts service choices only; contact sharing is a protected human POST.
for pii_field in ("customer", "pickup_address"):
    S["BookingInput"]["properties"].pop(pii_field)
S["BookingEvent"] = obj({
    "schema_version": {"const": "1"}, "event_id": {"type": "string", "pattern": "^[a-f0-9]{64}$"},
    "type": {"enum": ["booking.awaiting_payment", "booking.request_received", "woocommerce.order_created", "booking.confirmed", "booking.cancelled", "payment.verified", "refund.review_required", "woocommerce.order_state", "woocommerce.order_cancelled", "cancellation.payment_review_required", "refund.gateway_accepted", "refund.manual_review_required", "refund.record_removed", "payment.capacity_review_required"]},
    "booking_reference": opaque, "correlation_id": {"type": ["string", "null"]}, "order_reference": {"type": ["string", "null"]},
    "booking_state": S["Booking"]["properties"]["booking_state"], "order_state": {"type": ["string", "null"]},
    "payment_state": string, "refund_state": refund_state, "merchant_confirmation_required": {"type": "boolean"}, "occurred_at": {"type": "string", "format": "date-time"}
}, ["schema_version", "event_id", "type", "booking_reference", "order_reference", "booking_state", "order_state", "payment_state", "merchant_confirmation_required", "occurred_at"])
S["Booking"]["properties"].update({
    "order_reference": {"type": ["string", "null"]}, "order_state": {"type": ["string", "null"]}, "order_bridge_state": string,
    "events": array(ref("BookingEvent")),
    "address_authorization": {"enum": ["unknown", "needs_user", "granted_for_order", "expired", "revoked", "merchant_review_required"]},
    "payment_authorization": {"enum": ["unknown", "needs_user", "authorized_for_quote", "expired", "revoked", "unsupported"]},
    "delegated_card_authorization": {"const": "unsupported"}, "host_wallet_authorization": {"const": "unknown"}
})
S["WalletInvoice"] = obj({"booking_id": opaque, "state": {"enum": ["settled", "awaiting-payment"]}, "payable": {"type": "boolean"}, "bolt11": {"type": ["string", "null"]}, "amount_sat": integer, "amount_msat": integer, "payment_hash": string, "network": {"const": "bc"}, "fiat_minor": integer, "currency": {"const": "USD"}, "expires_at": integer, "wallet_authorization_required": {"const": True}, "status_url": string}, ["booking_id", "state", "payable", "bolt11"])

nullable = {"type": ["string", "null"]}
nullable_minor = {"type": ["integer", "null"], "minimum": 0}
S["ListingReturnPolicy"] = obj({"source": {"const": "native_product_return_policy"}, "name": string, "label": string, "description": string, "type": string, "days": integer, "fee_terms": string}, ["source", "name", "label", "description", "type", "days", "fee_terms"])
S["Listing"] = obj({
    "product_id": integer, "title": string, "type": string, "canonical_url": string,
    "categories": array(string), "seller": obj({"id": integer, "display_name": string}, ["id", "display_name"]),
    "condition": nullable, "condition_verification": {"const": "seller_claim"}, "currency": string,
    "unit_price_minor": nullable_minor, "price_status": string, "stock_status": string,
    "available_quantity": {"type": ["integer", "null"]}, "inventory_reserved": {"const": False},
    "checkout_eligibility": {"enum": ["handoff_only", "handoff_disabled", "unavailable", "unsupported_variation", "requires_selection", "seller_disabled", "needs_manual_review"]},
    "direct_payment_enabled": {"const": False},
    "variations": array(obj({"variation_id": integer, "attributes": {"type": "object", "additionalProperties": string}, "in_stock": {"type": "boolean"}, "checkout_eligibility": {"const": "unsupported_variation"}}, ["variation_id", "attributes", "in_stock", "checkout_eligibility"])),
    "fulfillment_type": {"enum": ["service", "shipping", "virtual"]}, "fulfillment_note": nullable,
    "policy_url": nullable, "return_policy_url": nullable, "return_policy": {"anyOf": [ref("ListingReturnPolicy"), {"type": "null"}]}, "updated_at": nullable
}, ["product_id", "canonical_url", "seller", "checkout_eligibility", "direct_payment_enabled", "inventory_reserved"])
S["Listings"] = obj({"schema_version": string, "items": array(ref("Listing")), "page": integer, "per_page": integer, "total": integer, "pages": integer, "fetched_at": string}, ["schema_version", "items", "page", "per_page", "total", "pages", "fetched_at"])
S["ListingInput"] = obj({"items": S["QuoteInput"]["properties"]["items"], "coupons": {"type": "array", "items": string, "maxItems": 5}, "source": {"type": "string", "maxLength": 80}, "booking_id": opaque}, ["items"])
S["ListingQuoteInput"] = obj({"billing": ref("Address"), "shipping": ref("Address"), "email": {"type": "string", "format": "email"}, "payment_method": string, "shipping_methods": {"type": "array", "items": string, "maxItems": 10}})
S["ListingQuote"] = obj({
    "currency": {"const": "USD"},
    "items": array(obj({"product_id": integer, "quantity": integer, "listing": ref("Listing"), "subtotal_minor": integer, "total_minor": integer, "tax_minor": integer}, ["product_id", "quantity", "listing"])),
    "total_minor": nullable_minor, "estimate_only": {"type": "boolean"}, "reason": nullable,
    "shipping_rates": array(obj({"package_index": integer, "selected": nullable, "options": array(obj({"id": string, "label": string, "cost_minor": integer, "tax_minor": integer}, ["id", "label", "cost_minor", "tax_minor"]))}, ["package_index", "selected", "options"])),
    "fees": array(obj({"name": string, "total_minor": integer, "tax_minor": integer}, ["name", "total_minor", "tax_minor"])),
    "tax_minor": nullable_minor, "shipping_minor": nullable_minor, "discount_minor": nullable_minor,
    "payment_methods": array(string), "payment_method": string, "policy_url": string, "policy_version": string,
    "return_policy_url": string, "quote_hash": {"type": "string", "pattern": "^[a-f0-9]{64}$"}
}, ["currency", "items", "total_minor", "estimate_only", "reason", "shipping_rates", "fees", "tax_minor", "shipping_minor", "discount_minor", "payment_methods", "policy_url", "return_policy_url"])
S["ListingStatus"] = obj({
    "intent_id": opaque, "handoff_state": {"enum": ["review", "preparing_cart", "cart_ready", "order_linked"]},
    "payment_state": {"enum": ["not_started", "pending", "paid", "refund_recorded", "cancelled", "paid_cancelled_review_required", "needs_review"]},
    "payment_verification": {"enum": ["none", "native_gateway_order_event"]}, "fulfillment_state": string,
    "woocommerce_status": nullable, "scheduling_state": {"enum": ["not_booked", "not_applicable"]},
    "inventory_reserved_at_quote": {"const": False}, "direct_payment_enabled": {"const": False},
    "refund_summary": ref("RefundSummary"), "next_action": string, "status_url": string
}, ["intent_id", "handoff_state", "payment_state", "payment_verification", "fulfillment_state", "woocommerce_status", "scheduling_state", "inventory_reserved_at_quote", "direct_payment_enabled", "next_action", "status_url"])
S["ListingIntent"] = obj({**S["ListingStatus"]["properties"], "expires_at": string, "quote_expires_at": nullable, "review_url": string, "quote": {"anyOf": [ref("ListingQuote"), {"type": "null"}]}}, [*S["ListingStatus"]["required"], "expires_at", "quote_expires_at", "review_url", "quote"])
def route(path, method, operation, output=None, body=None, private=False, idem=False, query=()):
    op = {"operationId": operation, "responses": {"200": {"description": "Successful response", "content": {"application/json": {"schema": ref(output) if output else {"type": "object"}}}}, "default": {"description": "Structured error", "content": {"application/json": {"schema": ref("Error")}}}}, "security": [{"ShopperSession": []}] if private else []}
    params = []
    if "{id}" in path: params.append({"name": "id", "in": "path", "required": True, "schema": opaque})
    if "{product_id}" in path: params.append({"name": "product_id", "in": "path", "required": True, "schema": {"type": "integer", "minimum": 1}})
    for name, schema, required in query: params.append({"name": name, "in": "query", "required": required, "schema": schema})
    if idem: params.append({"name": "Idempotency-Key", "in": "header", "required": True, "schema": {"type": "string", "pattern": "^[A-Za-z0-9:_-]{12,128}$"}})
    if params: op["parameters"] = params
    if body is not None: op["requestBody"] = {"required": True, "content": {"application/json": {"schema": ref(body) if body else obj({})}}}
    paths.setdefault(path, {})[method.lower()] = op
route("/capabilities", "GET", "capabilities", "Capabilities")
route("/booking-options", "GET", "bookingOptions")
route("/booking-availability", "GET", "bookingAvailability")
route("/booking-coverage", "GET", "bookingCoverage", "BookingCoverage", query=[("postal_code", postal, True)])
route("/bookings", "POST", "bookingCreate", "Booking", "BookingInput", True, True)
route("/bookings/{id}", "GET", "bookingGet", "Booking", private=True)
route("/bookings/{id}/checkout", "POST", "bookingCheckout", "ListingIntent", "", True)
route("/bookings/{id}/cancel", "POST", "bookingCancel", "Booking", "", True)
route("/bookings/{id}/wallet-invoice", "GET", "bookingWalletInvoice", "WalletInvoice", private=True)
route("/bookings/{id}/attach", "POST", "bookingAttach", body="", private=True)
for booking_path in ["/bookings/{id}", "/bookings/{id}/checkout", "/bookings/{id}/cancel", "/bookings/{id}/wallet-invoice", "/bookings/{id}/attach"]:
    for operation in paths[booking_path].values():
        operation['security'] = [{'BookingAccess': []}, {'ShopperSession': []}]
paths['/bookings/{id}/attach']['post']['security'] = [{'BookingAccess': []}]
route("/catalog", "GET", "catalog", "Catalog", query=[("category", {"enum": ["technology", "sharpening"]}, False), ("search", string, False), ("page", {"type": "integer", "minimum": 1, "maximum": 100}, False), ("per_page", {"type": "integer", "minimum": 1, "maximum": 20}, False)])
route("/service-area", "GET", "area", query=[("postal_code", postal, True)])
route("/listings", "GET", "listings", "Listings", query=[("search", string, False), ("category", string, False), ("seller", {"type": "integer", "minimum": 1}, False), ("page", {"type": "integer", "minimum": 1, "maximum": 100}, False), ("per_page", {"type": "integer", "minimum": 1, "maximum": 100}, False)])
route("/listings/{product_id}", "GET", "listing", "Listing")
route("/listing-checkouts", "POST", "listingCreate", "ListingIntent", "ListingInput", True, True)
route("/listing-checkouts/{id}", "GET", "listingGet", "ListingIntent", private=True)
route("/listing-checkouts/{id}/quote", "POST", "listingQuote", "ListingIntent", "ListingQuoteInput", True, True)
route("/listing-checkouts/{id}/status", "GET", "listingStatus", "ListingStatus", private=True)
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
contract = {"openapi": "3.1.0", "info": {"title": "KnifeRevive Agent Commerce", "version": "1.5.0", "description": "Verify deployed capabilities before use. Booking requests and payment are independent. Draft contact fields are unsupported; use protected human review. Native refund acceptance does not prove bank arrival."}, "servers": [{"url": "https://kniferevive.com/wp-json/kniferevive-agent/v1"}], "paths": paths, "components": {"securitySchemes": {"ShopperSession": {"type": "apiKey", "in": "header", "name": "X-Krev-Agent-Session"}, "BookingAccess": {"type": "apiKey", "in": "header", "name": "X-Krev-Booking"}}, "schemas": S}}
payload = json.dumps(contract, indent=2) + "\n"
for file in [ROOT / "openapi/kniferevive-agent-v1.yaml", ROOT / "wordpress/kniferevive-agent-commerce/assets/openapi.json"]:
    file.parent.mkdir(parents=True, exist_ok=True)
    file.write_text(payload, encoding="utf-8", newline="\n")
(ROOT / "wordpress/kniferevive-agent-commerce/assets/AI.md").write_bytes((ROOT / "AI.md").read_bytes())
print(f"Generated {len(paths)} API paths and packaged AI.md.")
