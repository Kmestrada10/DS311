#!/usr/bin/env python3
import sys
import requests
import json

# === Get latitude and longitude from command-line arguments ===
latitude = sys.argv[1]
longitude = sys.argv[2]

# Clean up inputs just in case
latitude = latitude.strip().replace('"', '')
longitude = longitude.strip().replace('"', '')

# === API Settings ===
base_url = "https://data.cityofchicago.org/resource/6zsd-86xi.json"
radius = 1200        # meters
limit = 10           # pull 10 crimes

# === Build Query ===
where_query = f"within_circle(location, {latitude}, {longitude}, {radius})"

params = {
    "$where": where_query,
    "$limit": limit,
    "$order": "date DESC"
}

# === Make Request ===
try:
    response = requests.get(base_url, params=params)

    if response.status_code == 200:
        crimes = response.json()
        print(json.dumps(crimes))
    else:
        print(json.dumps({"error": f"Failed to fetch: {response.status_code}"}))
except Exception as e:
    print(json.dumps({"error": str(e)}))
