"""Exercise gallery selection failures that produced single small imports."""
import copy
import importlib.util
from pathlib import Path
import unittest

path = Path(__file__).resolve().parents[1] / 'skills/kniferevive-listing/scripts/select_photos.py'
spec = importlib.util.spec_from_file_location('select_photos', path)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


def photo(identity, width=1600, height=1200, kind='viewer'):
    return {'photo_id': identity, 'candidates': [{'url': f'https://scontent.test.fbcdn.net/{identity}.jpg?stp=signed&oh=unchanged', 'width': width, 'height': height, 'kind': kind}]}


class PhotoSelection(unittest.TestCase):
    def setUp(self):
        self.gallery = {'gallery_complete': True, 'expected_count': 2, 'photos': [photo('first'), photo('second')]}

    def test_full_gallery_source_order_and_signed_urls(self):
        result = module.select_photos(self.gallery)
        self.assertTrue(result['ready'])
        self.assertEqual(result['image_urls'], [p['candidates'][0]['url'] for p in self.gallery['photos']])

    def test_largest_variant_of_same_photo_not_two_gallery_images(self):
        large = photo('first', 2400, 1800)
        large['candidates'][0]['url'] += '&variant=larger'
        self.gallery['photos'].append(large)
        result = module.select_photos(self.gallery)
        self.assertEqual(result['selected'], 2)
        self.assertEqual(result['photos'][0]['width'], 2400)

    def test_count_mismatch_blocks_single_photo_import(self):
        self.gallery['photos'].pop()
        result = module.select_photos(self.gallery)
        self.assertFalse(result['ready'])
        self.assertNotIn('image_urls', result)

    def test_unverified_traversal_blocks(self):
        self.gallery['gallery_complete'] = False
        self.assertFalse(module.select_photos(self.gallery)['ready'])

    def test_portrait_source_small_width_is_disclosed(self):
        self.gallery['photos'][0] = photo('first', 443, 960)
        result = module.select_photos(self.gallery)
        self.assertFalse(result['ready'])
        self.assertEqual(result['issues'][0]['width'], 443)
        accepted = module.select_photos(self.gallery, allow_small=True)
        self.assertTrue(accepted['ready'])
        self.assertEqual(accepted['issues'], result['issues'])

    def test_thumbnail_fallback_is_not_claimed_original(self):
        self.gallery['photos'][0] = photo('first', kind='thumbnail')
        self.assertFalse(module.select_photos(self.gallery)['ready'])

    def test_missing_main_never_promotes_second(self):
        self.gallery['photos'][0]['candidates'] = []
        self.assertFalse(module.select_photos(self.gallery, allow_partial=True)['ready'])

    def test_missing_secondary_and_acceptance(self):
        self.gallery['photos'][1]['candidates'] = []
        self.assertFalse(module.select_photos(self.gallery)['ready'])
        accepted = module.select_photos(self.gallery, allow_partial=True)
        self.assertTrue(accepted['ready'])
        self.assertEqual(accepted['included'], 1)

    def test_untrusted_host_and_unknown_resolution_rejected(self):
        for replacement in ('https://fbcdn.net.attacker.invalid/img.jpg', 'https://user:pass@scontent.test.fbcdn.net/img.jpg', 'https://scontent.test.fbcdn.net:443/img.jpg'):
            gallery = copy.deepcopy(self.gallery)
            gallery['photos'][0]['candidates'][0]['url'] = replacement
            self.assertFalse(module.select_photos(gallery)['ready'])
        self.gallery['photos'][0]['candidates'][0]['width'] = None
        self.assertFalse(module.select_photos(self.gallery)['ready'])

    def test_overflow_is_retained_without_extra_import(self):
        self.gallery['photos'] = [photo(str(i)) for i in range(12)]
        self.gallery['expected_count'] = 12
        result = module.select_photos(self.gallery)
        self.assertTrue(result['ready'])
        self.assertEqual(len(result['image_urls']), 10)
        self.assertEqual(result['overflow_positions'], [11, 12])

    def test_duplicate_url_under_different_positions_needs_review(self):
        self.gallery['photos'][1]['candidates'][0]['url'] = self.gallery['photos'][0]['candidates'][0]['url']
        self.assertFalse(module.select_photos(self.gallery)['ready'])


if __name__ == '__main__':
    unittest.main()
