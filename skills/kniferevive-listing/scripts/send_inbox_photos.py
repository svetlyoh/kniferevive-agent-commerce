"""Deliver browser-downloaded original files to missing inbox slots; key stays in env."""
import argparse
import base64
import json
import os
from pathlib import Path
import re
import urllib.error
import urllib.request

MAX_BYTES = 5 * 1024 * 1024


class NoRedirects(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def deliver(inbox_id, files, key, opener=None):
    if type(inbox_id) is not int or inbox_id < 1 or not isinstance(key, str) or not re.fullmatch(r'[1-9]\d{0,9}\.[a-f0-9]{64}', key):
        raise ValueError('A valid inbox ID and private KREV_INBOX_KEY are required.')
    if not isinstance(files, list) or not 1 <= len(files) <= 10:
        raise ValueError('Provide one to ten missing photo slots with original local file paths.')
    staged = []
    used = set()
    for entry in files:
        index = entry.get('photo_index') if isinstance(entry, dict) else None
        path = entry.get('path') if isinstance(entry, dict) else None
        if type(index) is not int or not 1 <= index <= 10 or index in used or not isinstance(path, str) or not Path(path).is_absolute():
            raise ValueError('Use distinct photo_index values 1–10 and absolute original file paths.')
        used.add(index)
        if not Path(path).is_file() or not 0 < Path(path).stat().st_size <= MAX_BYTES:
            raise ValueError('Each original file must exist and be no larger than 5 MB.')
        blob = Path(path).read_bytes()
        if not 0 < len(blob) <= MAX_BYTES:
            raise ValueError('An original file changed or exceeds the size limit.')
        staged.append((index, blob))
    opener = opener or urllib.request.build_opener(NoRedirects())
    result = None
    for index, blob in staged:
        request = urllib.request.Request(
            f'https://kniferevive.com/wp-json/kniferevive-listlab-import/v1/inbox/{inbox_id}/photos',
            data=json.dumps({'inbox_key': key, 'photo_index': index, 'image_base64': base64.b64encode(blob).decode('ascii')}).encode(),
            headers={'Content-Type': 'application/json', 'User-Agent': 'KnifeRevive-Listing/1.0.11'}, method='POST')
        with opener.open(request, timeout=45) as response:
            result = json.loads(response.read(65536))
        if not isinstance(result, dict) or result.get('state') != 'queued' or result.get('inbox_id') != inbox_id or result.get('item_state') != 'queued' or not isinstance(result.get('photo_delivery'), dict):
            raise ValueError('Unexpected receipt; inspect the original item before sending more.')
    photos = result['photo_delivery']
    return {'inbox_id': inbox_id, 'item_state': result['item_state'], 'photo_delivery':
            {name: photos.get(name) for name in ('submitted', 'preserved', 'missing', 'ready')}}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--inbox-id', type=int, required=True)
    parser.add_argument('--files', type=Path, required=True, help='Private JSON list of photo_index and absolute original path')
    args = parser.parse_args()
    try:
        files = json.loads(args.files.read_text(encoding='utf-8'))
        result = deliver(args.inbox_id, files, os.environ.get('KREV_INBOX_KEY'))
    except urllib.error.HTTPError as error:
        print(json.dumps({'delivered': False, 'http_status': error.code, 'action': 'Inspect the original inbox item; no replacement was created.'}))
        return 1
    except (ValueError, OSError, urllib.error.URLError):
        print(json.dumps({'delivered': False, 'action': 'Verify the private connection, original files and server receipt; do not create another import.'}))
        return 1
    print(json.dumps(result))
    return 0 if result['photo_delivery'].get('ready') is True else 2


if __name__ == '__main__':
    raise SystemExit(main())
