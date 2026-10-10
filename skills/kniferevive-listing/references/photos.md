# Recover the selected item's complete photo gallery

The failure to avoid is a single search/metadata thumbnail becoming the main
photo while the remaining listing photos are missed. The server copies the
supplied bytes; it cannot recover a larger image from a small URL. “Original”
means a source-served full photo, not an enlarged screenshot or AI recreation.

1. Open the selected Marketplace item through the host's authorized browser.
   Dismiss an optional login overlay only when the page itself permits it.
   Scope extraction to that item's photo controls; exclude recommendations,
   avatars and ads. Inventory every `View photo N`/thumbnail or visible gallery
   counter. Thumbnail *display* size does not prove the linked image is small.
2. Activate every photo control, or advance the gallery until it returns to the
   first verified photo/end. After each click, wait through the host's normal
   loaded-state observation. Verify the active image changed before recording
   it: DOM order can change, so `first img` may be an inactive thumbnail. Record
   gallery position, real photo identity when exposed, and the active photo's
   exact image URL, decoded natural width/height and available source variants.
   Include close-ups, defects, packaging and labels in the same source order.
3. Prefer an available full-screen/open-photo/download-original control. For
   the active photo inspect its actual `currentSrc`, `src`, `srcset` and picture
   sources through permitted DOM tools. Test the largest *observed* variant by
   opening/loading it with the host's supported tools and measuring its actual
   pixels. A width descriptor or URL `s960x960` is not measured resolution.
   Keep the largest uncropped, source-served version per photo; don't deduplicate
   different views merely because they look alike. Do not remove `stp`, change
   CDN paths, guess resolution parameters, strip signatures or construct hidden
   Facebook/GraphQL endpoints. Those are not observed original URLs.
4. Verify gallery counts and dimensions before POST `/prepare`. Aim for at least
   500 pixels on both axes and preferably 1500 when the source provides it.
   Preserve portrait/landscape aspect ratio. If the largest exposed photo is
   smaller on either axis, report the exact dimensions and that a higher original
   is unavailable. Continue useful collection, but don't silently prepare a
   thumbnail-only/incomplete gallery; obtain the user's choice to use the best
   available files or supply originals. Do not claim Google readiness or upscale
   a small photo to pass this check. Google announces 500 × 500 from January 31,
   2027 and recommends 1500 × 1500: [official image guidance](https://support.google.com/merchants/answer/6324350).
5. Submit all distinct collected CDN URLs via `image_urls`, main first, bounded
   by the live schema (currently ten total). Collect larger galleries completely
   and identify overflow positions; retain extras for native completion. Keep
   signed URLs private and fresh. If an image is accessible only in the browser,
   use its native download/save-media facility and preserve the actual image
   file for native ListLab upload. Never transfer browser cookies to the import
   server, proxy around a blocked resource, or upload a screenshot of the page.
6. After seller claim, check copied-image warnings and actual native main/gallery
   count. Report found / submitted / copied separately. A successful preparation
   is not evidence that photos were copied. For an existing KnifeRevive listing,
   update its photos in place through native ListLab; do not prepare/claim a new
   listing. Preserve inventory and other seller fields unless the user asks.

## Optional deterministic selection check

If the host has Python, save only the selected listing's observed photo data to
a private JSON file and run the bundled helper. It performs no browser access,
network requests, upload, preparation or publication. Without Python, apply
the same count/resolution checks using the host's available tools.

```json
{
  "gallery_complete": true,
  "expected_count": 2,
  "photos": [
    {"photo_id": "gallery-1", "candidates": [
      {"url": "https://scontent.example.fbcdn.net/photo-1.jpg?signature=kept", "width": 1600, "height": 1200, "kind": "viewer"}
    ]},
    {"photo_id": "gallery-2", "candidates": [
      {"url": "https://scontent.example.fbcdn.net/photo-2.jpg?signature=kept", "width": 1600, "height": 1200, "kind": "viewer"}
    ]}
  ]
}
```

Use real observed URLs and measured dimensions, never the example values.
`kind` is `viewer`, `original` (only when explicitly exposed as original), or
`thumbnail`. `photo_id` is an observed ID or stable gallery position; reuse it
only for alternate sizes of the same photo. `gallery_complete` requires actual
traversal; set false if access is incomplete. A missing photo needs its position
with an empty candidate list. If the UI gives no count, omit `expected_count`
and record completion only after verifying every control/end or wraparound.

```sh
python scripts/select_photos.py observed-gallery.json --output checked-gallery.json --max-images 10
```

The result includes chosen dimensions, overflow and blockers. Only `ready:true`
includes `image_urls` for preparation. `--allow-small` and `--allow-partial` may
be used only after the user accepts the reported limitation; the report retains
it. Keep report files private. The helper trusts host observations and does not
prove that a CDN URL still works or that the remote file is the camera original.
