#!/usr/bin/env python3
import sys
import requests
import json
from datetime import datetime, timedelta

# === Get latitude and longitude from command-line arguments ===
latitude = sys.argv[1]
longitude = sys.argv[2]

latitude = latitude.strip().replace('"', '')
longitude = longitude.strip().replace('"', '')

# === Settings ===
base_url = "https://data.cityofchicago.org/resource/6zsd-86xi.json"
radius = 1200  # meters (0.75 miles)
limit = 50     # pull up to 50 crimes

# === Calculate date 21 days ago ===
start_date = (datetime.now() - timedelta(days=21)).strftime('%Y-%m-%dT00:00:00')

# === Build Query ===
where_query = f"within_circle(location, {latitude}, {longitude}, {radius}) AND date > '{start_date}'"

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
