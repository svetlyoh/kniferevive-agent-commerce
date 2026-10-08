"""Build the local coverage index from the unmodified Census relationship file.

Run with a downloaded tab20_zcta520_county20_natl.txt path. No runtime lookup,
credentials, address transmission or ZIP-prefix inference is used.
"""
import csv
import hashlib
import json
import sys
from pathlib import Path

root = Path(__file__).resolve().parents[1]
source = Path(sys.argv[1])
bay = {'06001': 'Alameda', '06013': 'Contra Costa', '06041': 'Marin',
       '06055': 'Napa', '06075': 'San Francisco', '06081': 'San Mateo',
       '06085': 'Santa Clara', '06095': 'Solano', '06097': 'Sonoma'}
relations = {}
with source.open(encoding='utf-8-sig', newline='') as handle:
    for row in csv.DictReader(handle, delimiter='|'):
        code, county = row['GEOID_ZCTA5_20'], row['GEOID_COUNTY_20']
        if len(code) == 5 and county and int(row['AREALAND_PART'] or 0) > 0:
            relations.setdefault(code, set()).add(county)
index = {
    'source_url': 'https://www2.census.gov/geo/docs/maps-data/data/rel2020/zcta520/tab20_zcta520_county20_natl.txt',
    'source_sha256': hashlib.sha256(source.read_bytes()).hexdigest(),
    'geography_vintage': '2020 Census ZCTA / county',
    'retrieved_on': '2026-10-08',
    'bay_counties': bay,
    'pickup_counties': ['06013', '06085'],
    'bay_postal_codes': {code: sorted(counties) for code, counties in sorted(relations.items()) if counties & bay.keys()},
    'outside_postal_codes': sorted(code for code, counties in relations.items() if not counties & bay.keys()),
}
target = root / 'wordpress/kniferevive-agent-commerce/assets/booking-coverage.json'
target.write_text(json.dumps(index, separators=(',', ':')) + '\n', encoding='utf-8')
print(f'Built {len(index["bay_postal_codes"])} Bay Area ZCTAs; unknown/post-office ZIPs require review.')
