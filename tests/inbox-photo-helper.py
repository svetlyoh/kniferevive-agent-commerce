"""No-network checks of original-file delivery and redacted receipts."""
import importlib.util
import io
import json
from pathlib import Path
import tempfile
import unittest
import sys

sys.dont_write_bytecode = True

spec = importlib.util.spec_from_file_location('helper', Path(__file__).resolve().parents[1] / 'skills/kniferevive-listing/scripts/send_inbox_photos.py')
helper = importlib.util.module_from_spec(spec)
spec.loader.exec_module(helper)


class PhotoDelivery(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.file = Path(self.temp.name) / 'original.png'
        self.file.write_bytes(b'original source bytes')
        self.key = '7.' + 'a' * 64

    def tearDown(self):
        self.temp.cleanup()

    def test_all_originals_ordered_and_key_not_in_receipt(self):
        calls = []
        class Opener:
            def open(inner, request, timeout):
                body = json.loads(request.data); calls.append((request.full_url, body))
                count = len(calls)
                return io.BytesIO(json.dumps({'state': 'queued', 'item_state': 'queued', 'inbox_id': 12,
                    'photo_delivery': {'submitted': 2, 'preserved': count, 'missing': 2 - count, 'ready': count == 2}}).encode())
        result = helper.deliver(12, [{'photo_index': i, 'path': str(self.file)} for i in (1, 2)], self.key, Opener())
        self.assertTrue(result['photo_delivery']['ready'])
        self.assertEqual([c[1]['photo_index'] for c in calls], [1, 2])
        self.assertTrue(all(c[0] == 'https://kniferevive.com/wp-json/kniferevive-listlab-import/v1/inbox/12/photos' for c in calls))
        self.assertEqual(calls[0][1]['inbox_key'], self.key)
        self.assertNotIn(self.key, json.dumps(result))

    def test_invalid_key_and_index_stop_before_network(self):
        for key, index in [('missing', 1), (self.key, 11), (self.key, True)]:
            with self.assertRaises(ValueError):
                helper.deliver(1, [{'photo_index': index, 'path': str(self.file)}], key)

    def test_duplicate_slot_and_oversize_stop_before_network(self):
        entry = {'photo_index': 1, 'path': str(self.file)}
        with self.assertRaises(ValueError):
            helper.deliver(1, [entry, entry], self.key)
        self.file.write_bytes(b'x' * (helper.MAX_BYTES + 1))
        with self.assertRaises(ValueError):
            helper.deliver(1, [entry], self.key)

    def test_draft_receipt_stops_more_uploads(self):
        class Opener:
            def open(inner, request, timeout):
                return io.BytesIO(json.dumps({'state': 'queued', 'item_state': 'draft', 'inbox_id': 1, 'photo_delivery': {}}).encode())
        with self.assertRaises(ValueError):
            helper.deliver(1, [{'photo_index': 1, 'path': str(self.file)}], self.key, Opener())

    def test_redirects_never_forward_key(self):
        self.assertIsNone(helper.NoRedirects().redirect_request(None, None, 307, '', {}, 'https://elsewhere.invalid/'))


if __name__ == '__main__':
    unittest.main()
