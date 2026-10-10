# Marketplace Imports and KnifeRevive Listing 1.0.1

New imported ListLab drafts prefill short description with the same sanitized
text as the long description. An edit to the long description on the private
import review updates both fields before draft creation. The native editor can
then edit either field independently; repeated imports preserve seller edits
and do not rewrite an existing draft. Existing products are not bulk updated.

The separate `kniferevive-listing` skill now explicitly extracts a stated maker
brand and, for knives, knife/blade type, edge style and blade length with units.
It maps facts to the live schema: brand is currently `product_brand`, blade
length `pa_blade-length`, and edge style `pa_edge-type`; knife type chooses the
actual category. Blade shape/profile stays in the description when no matching
attribute exists. Missing facts remain unknown, and blade length is not copied
into the package shipping-length field.

Read-only live knife schema verification confirms these fields are available.
The fenced native suite passed 77 assertions, covering description sanitization,
review changes, native matching descriptions, independently edited summary
preservation, and native knife brand/category/blade-length/edge-style storage.
PHP lint and skill validation are checked before packaging. No production
product/order or real Facebook photo was imported during implementation tests.

WordPress confirmed the in-place update on October 10, 2026, and an HTTP/2 live
schema read returned plugin version 1.0.1 and 21 categories. Actual Muse skill
installation/extraction remains unverified. Update the
existing Listing skill in place from the complete versioned GitHub folder:
https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.1/skills/kniferevive-listing
Keep its identity and one active copy; Concierge 0.6.3 is unchanged.
