# Category-dependent ListLab item conditions

ListLab 1.8.6 uses Product Attributes 0.3.10's central condition policy. Marketplace Imports 1.0.7 and listing skill 1.0.7 use the same scoped schema. These are merchant condition vocabularies, not additional Google condition enums.

The selected Google subcategory takes priority. Without a subcategory policy, a compatible selected metacategory supplies the policy; otherwise the category supplies it. Managed Google children all receive a policy. Category, subcategory and metacategory changes fetch a fresh schema, retain a still-valid selection, and clear an incompatible selection without silently defaulting it. The condition field cannot create custom values. Server validation enforces this on drafts, publication and import preparation before product mutation.

| Profile | Available conditions |
| --- | --- |
| Tech and knives | New, Used (Pre-owned on Tech), Like New, Gently Used, Moderately Used, Well Used, Heavily Used / Fair, Open Box, Refurbished, For Parts / Repair |
| Art and trading cards | New, Used, Like New, Gently Used, Moderately Used, Well Used, Heavily Used / Fair |
| Coins and currency | New, Used, Circulated, Uncirculated |
| Home & Garden, phone accessories and other physical goods | New, Used, Like New, Gently Used, Moderately Used, Well Used, Heavily Used / Fair, Open Box |
| Spices and selected garden/cleaning consumables | New; explicit confirmation required, no inferred New default |
| Sharpening services | No physical-item condition field |

Subtype overrides narrow cases, screen protectors, phone charms, cushions, stands, covers, stylus nibs and SIM/prepaid cards to the physical profile. Headphones and powered devices retain Tech conditions even when browsing Cell Phone Accessories. Home & Garden appliances use the Tech profile; plants, seeds, fertilizers, potting soil, plant food, pesticides, herbicides, cleaning solutions and laundry detergent use the consumables profile. Collectibles coin/currency children use coins and trading-card children use cards. Other Collectibles use physical conditions. Pokemon Cards uses cards when no more specific subtype is selected.

Tech initially defaults to the existing native Used term, displayed as Pre-owned. A category change that invalidates a previously selected condition leaves the field blank for the seller. Skill extraction defaults Gently Used for knives or Used for other goods only when that option is allowed. Explicit source facts take precedence; unsupported or unknown conditions stay blank for completion.

Google feed condition remains `new`, `used` or evidence-qualified `refurbished`. Circulated and Uncirculated both map to `used`; numismatic grade alone does not establish a new product. Existing refurbishment checks still require restoration, like-new operation and warranty evidence. Price, stock, Google category selection, gallery and parcel defaults are unchanged.

## Verification

The fenced native suite passed 175 assertions across taxonomy, metacategories, specifications and condition policies. Sixteen full ListLab JavaScript/DOM assertions verify refresh, priority, default selection, valid retention and incompatible clearing. The Marketplace Imports regression passed 111 assertions with the actual Product Attributes plugin loaded, including rejected wrong-category conditions and metacategory-aware public schemas. Its processor fixture selects Desktop Computers rather than submitting processor to the generic Tech profile. PHP and JavaScript syntax checks passed.

All three existing plugins were replaced in place through native WordPress uploads, each showing Plugin updated successfully. Eleven live public schemas passed across Tech, coins, art, spices, Home & Garden, Collectibles, Pokemon, Cell Phone Accessories, cases, headphones and Desktop Computers / AI-Tech. The live Dell editor confirms ten Tech condition choices, no circulated/custom conditions, and the existing Refurbished selection retained. No product was saved during live QA. Evidence is in the owner's `KnifeRevive_Documentation/diagnostics/metacategories-2026-10-11` folder, including `condition-live-schemas.json`, native/import logs and `condition-tech-listlab-live.png`. The dedicated test database on loopback port 11029 was shut down after verification. The theme and Merchant Sync were not deployed in this update.

The listing skill is updated in place in the GitHub repository at immutable tag `kniferevive-listing-v1.0.7`. Muse must update its existing installation and reload its catalog; this release does not itself install or reload Muse.
