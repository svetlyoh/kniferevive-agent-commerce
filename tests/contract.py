"""Validate actual sandbox API payloads against the published schemas."""
from pathlib import Path
import json
import sys
ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / '.runtime/python'))
from jsonschema import Draft202012Validator

contract = json.loads((ROOT / 'openapi/kniferevive-agent-v1.yaml').read_text())
samples = json.loads((ROOT / '.runtime/contract-samples.json').read_text())
samples.update(json.loads((ROOT / '.runtime/listing-contract-samples.json').read_text()))
samples.update(json.loads((ROOT / '.runtime/booking-contract-samples.json').read_text()))
for name, sample in samples.items():
    schema = {'$ref': f'#/components/schemas/{name}', 'components': contract['components']}
    Draft202012Validator.check_schema(schema)
    Draft202012Validator(schema).validate(sample)
    print(f'PASS: actual {name} payload matches OpenAPI')

quickstart = (ROOT / 'AI.md').read_text()
example = json.loads(quickstart.split('```json\n', 1)[1].split('```', 1)[0])
Draft202012Validator({'$ref': '#/components/schemas/QuoteInput', 'components': contract['components']}).validate(example)
assert (ROOT / 'AI.md').read_bytes() == (ROOT / 'wordpress/kniferevive-agent-commerce/assets/AI.md').read_bytes()
assert (ROOT / 'openapi/kniferevive-agent-v1.yaml').read_bytes() == (ROOT / 'wordpress/kniferevive-agent-commerce/assets/openapi.json').read_bytes()
print('PASS: AI.md example and packaged documentation match the contract')
