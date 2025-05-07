<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/unified_login.php");
    exit;
}
require_once("../config/db_connect.php");

$pageTitle = "Crime";
$sidebarTitle = "Crime";
$user_id = $_SESSION['user_id'];

// Get all user locations
$stmt = $db->prepare("SELECT location_id, custom_label, street_address, city, state, zip_code, latitude, longitude, label_type FROM ds_user_locations WHERE user_id = ?");
$stmt->execute([$user_id]);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<style>
/* Crime by Hour Styling */
.crime-hour-container {
    position: relative;
    height: 200px;
}

/* Crime Type Distribution Styling */
.crime-type-container {
    position: relative;
    height: 200px;
}
.chart-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
    margin-top: 10px;
}
.legend-item {
    display: flex;
    align-items: center;
    font-size: 12px;
}
.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 5px;
    display: inline-block;
}

/* Top Crimes Styling */
.top-crimes-list {
    margin-top: 15px;
}
.top-crime-item {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}
.top-crime-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
}
.top-crime-text {
    flex: 1;
}
.top-crime-percent {
    font-weight: bold;
}

/* Enhanced Map Styling */
.crime-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transform: translate(-12px, -12px);
}
.crime-marker i {
    font-size: 12px;
    color: white;
}
.severity-1 { background: #3498db; width: 20px; height: 20px; }
.severity-2 { background: #f39c12; width: 24px; height: 24px; }
.severity-3 { background: #e74c3c; width: 28px; height: 28px; }
.map-legend {
    padding: 10px;
    text-align: center;
    background: rgba(0,0,0,0.7);
    border-radius: 4px;
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}
.map-legend span {
    margin: 0 8px;
}
.map-expand-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1000;
    background: rgba(0,0,0,0.7);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 5px 10px;
    cursor: pointer;
}
.map-fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    height: 100vh !important;
    width: 100vw !important;
    z-index: 9999;
}

/* User Location Marker */
.user-location-marker {
    background: #4CAF50;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

/* Compact Alerts Styling */
.compact-alerts {
    display: grid;
    gap: 10px;
    margin-top: 15px;
}
.compact-alert {
    display: flex;
    align-items: center;
    padding: 12px;
    background: rgba(142,142,147,0.1);
    border-radius: 8px;
}
.compact-alert i {
    margin-right: 12px;
    font-size: 1.2em;
}
.compact-alert small {
    display: block;
    color: #8e8e93;
    font-size: 0.8em;
}

/* Card Layout Improvements */
.card-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}
.card {
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    background: white;
}
.wide-card {
    grid-column: 1 / -1;
    position: relative;
}
</style>

<div class="main-content">
    <div class="user-bar">
        <span>Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="dashboard-header">
        <h3><i class="fas fa-shield-alt"></i> Crime Dashboard</h3>
        <p>Crime statistics for your saved locations</p>
    </div>

    <?php if ($locations): ?>
        <div class="location-pills mb-4">
            <?php foreach ($locations as $index => $loc): ?>
                <div class="location-pill <?= $index === 0 ? 'active' : '' ?>"
                     data-lat="<?= htmlspecialchars($loc['latitude']) ?>"
                     data-lon="<?= htmlspecialchars($loc['longitude']) ?>"
                     data-label="<?= htmlspecialchars($loc['custom_label'] ?: 'No Label') ?>"
                     data-address="<?= htmlspecialchars("{$loc['street_address']}, {$loc['city']}, {$loc['state']} {$loc['zip_code']}") ?>">
                    <i class="fas <?= 
                        $loc['label_type'] === 'Home' ? 'fa-home' : 
                        ($loc['label_type'] === 'Work' ? 'fa-briefcase' : 
                        ($loc['label_type'] === 'School' ? 'fa-school' : 'fa-map-marker-alt')) ?>"></i>
                    <?= htmlspecialchars($loc['custom_label'] ?: 'No Label') ?>
                </div>
            <?php endforeach; ?>
            <div class="location-pill" id="add-location">
                <i class="fas fa-plus"></i> Add Location
            </div>
        </div>

        <div class="card-container">
            <!-- Crime by Hour Card -->
            <div class="card">
                <h4><i class="fas fa-clock"></i> Crime by Hour</h4>
                <div class="crime-hour-container">
                    <canvas id="crimeHourChart"></canvas>
                </div>
            </div>
            
            <!-- Top 3 Crimes Card -->
            <div class="card">
                <h4><i class="fas fa-exclamation-triangle"></i> Top Crimes</h4>
                <div id="topCrimesList" class="top-crimes-list">
                    <div class="top-crime-item">
                        <div class="top-crime-icon bg-primary">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="top-crime-text">Theft</div>
                        <div class="top-crime-percent">45%</div>
                    </div>
                    <div class="top-crime-item">
                        <div class="top-crime-icon bg-warning">
                            <i class="fas fa-hand-rock"></i>
                        </div>
                        <div class="top-crime-text">Battery</div>
                        <div class="top-crime-percent">30%</div>
                    </div>
                    <div class="top-crime-item">
                        <div class="top-crime-icon bg-danger">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="top-crime-text">Burglary</div>
                        <div class="top-crime-percent">15%</div>
                    </div>
                </div>
            </div>
            
            <!-- Crime Type Distribution Card -->
            <div class="card">
                <h4><i class="fas fa-chart-pie"></i> Crime Types</h4>
                <div class="crime-type-container">
                    <canvas id="crimeTypeChart"></canvas>
                </div>
                <div class="chart-legend" id="crimeTypeLegend"></div>
            </div>
        </div>

        <!-- Enhanced Map Card -->
        <div class="card wide-card">
            <h4><i class="fas fa-map-marked-alt"></i> Threat Map</h4>
            <button id="expandMapBtn" class="map-expand-btn">
                <i class="fas fa-expand"></i> Expand
            </button>
            <div id="crimeMap" class="crime-heatmap" style="height: 500px;"></div>
            <div class="map-legend">
                <span><i class="fas fa-square text-danger"></i> Violent</span>
                <span><i class="fas fa-square text-warning"></i> Property</span>
                <span><i class="fas fa-square text-primary"></i> Theft</span>
            </div>
        </div>

        <!-- Recent Alerts Section -->
        <div class="card wide-card">
            <h4><i class="fas fa-bell"></i> Recent Alerts</h4>
            <div id="recentAlerts" class="compact-alerts">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-map-marked-alt"></i>
            <h4>No Locations Saved</h4>
            <p>Add locations to view crime statistics</p>
            <button class="btn btn-primary" id="add-first-location">
                <i class="fas fa-plus"></i> Add Location
            </button>
        </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const map = L.map('crimeMap').setView([41.8781, -87.6298], 11);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', { 
        attribution: '© ChicagoSafety',
        maxZoom: 18
    }).addTo(map);

    let markers = [];
    let heatmapLayer = null;
    let centerMarker = null;
    let centerCircle = null;
    let crimeHourChart = null;
    let crimeTypeChart = null;
    let isMapExpanded = false;

    // Helper functions
   <!-- In your JavaScript section, update the getCrimeColor function to this: -->
function getCrimeColor(type, opacity = 1) {
    const colors = {
        'MOTOR VEHICLE THEFT': `rgba(255, 152, 0, ${opacity})`,  // Orange
        'CRIMINAL DAMAGE': `rgba(121, 85, 72, ${opacity})`,      // Brown
        'THEFT': `rgba(66, 133, 244, ${opacity})`,               // Blue
        'CRIMINAL TRESPASS': `rgba(156, 39, 176, ${opacity})`,   // Purple
        'ASSAULT': `rgba(244, 67, 54, ${opacity})`,              // Red
        'BURGLARY': `rgba(142, 36, 170, ${opacity})`,            // Deep Purple
        'BATTERY': `rgba(255, 193, 7, ${opacity})`,              // Amber
        'DECEPTIVE PRACTICE': `rgba(0, 150, 136, ${opacity})`,   // Teal
        'ROBBERY': `rgba(233, 30, 99, ${opacity})`,              // Pink
        'default': `rgba(158, 158, 158, ${opacity})`             // Gray
    };
    
    // Check for partial matches (e.g. "THEFT" in "MOTOR VEHICLE THEFT")
    for (const key in colors) {
        if (type.includes(key)) {
            return colors[key];
        }
    }
    return colors.default;
}

<!-- And update the getCrimeIcon function to this: -->
function getCrimeIcon(type) {
    const icons = {
        'MOTOR VEHICLE THEFT': 'fa-car',
        'CRIMINAL DAMAGE': 'fa-hammer',
        'THEFT': 'fa-wallet',
        'CRIMINAL TRESPASS': 'fa-sign',
        'ASSAULT': 'fa-user-injured',
        'BURGLARY': 'fa-warehouse',
        'BATTERY': 'fa-hand-rock',
        'DECEPTIVE PRACTICE': 'fa-hand-holding-usd',
        'ROBBERY': 'fa-mask',
        'default': 'fa-exclamation-triangle'
    };
    
    // Check for partial matches
    for (const key in icons) {
        if (type.includes(key)) {
            return icons[key];
        }
    }
    return icons.default;
}

    function getCrimeSeverity(type) {
        return ['ASSAULT', 'BATTERY'].includes(type.split(' ')[0]) ? 3 : 
               ['THEFT', 'BURGLARY'].includes(type.split(' ')[0]) ? 2 : 1;
    }

    function renderCrimeHourChart(data) {
        const hours = Array.from({length: 24}, (_, i) => i);
        
        // Group crimes by hour and type
        const violentCrimes = hours.map(hour => 
            data.filter(d => {
                const crimeHour = new Date(d.date).getHours();
                const isViolent = ['ASSAULT', 'BATTERY'].includes(d.primary_type.split(' ')[0]);
                return crimeHour === hour && isViolent;
            }).length
        );
        
        const propertyCrimes = hours.map(hour => 
            data.filter(d => {
                const crimeHour = new Date(d.date).getHours();
                const isProperty = ['THEFT', 'BURGLARY'].includes(d.primary_type.split(' ')[0]);
                return crimeHour === hour && isProperty;
            }).length
        );

        const ctx = document.getElementById('crimeHourChart').getContext('2d');
        if (crimeHourChart) crimeHourChart.destroy();
        
        crimeHourChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM'],
                datasets: [
                    {
                        label: 'Violent Crimes',
                        data: violentCrimes,
                        backgroundColor: '#ff5252'
                    },
                    {
                        label: 'Property Crimes',
                        data: propertyCrimes,
                        backgroundColor: '#4285f4'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    function renderCrimeTypeChart(data) {
        // Group crimes by type
        const crimeCounts = {};
        data.forEach(c => {
            const type = c.primary_type || 'Other';
            crimeCounts[type] = (crimeCounts[type] || 0) + 1;
        });
        
        // Prepare data for chart
        const labels = Object.keys(crimeCounts);
        const counts = Object.values(crimeCounts);
        const backgroundColors = labels.map(type => 
            type.includes('ASSAULT') ? '#ff5252' :
            type.includes('BATTERY') ? '#ff9e0f' :
            type.includes('THEFT') ? '#4285f4' :
            type.includes('BURGLARY') ? '#8f3f97' :
            '#a0a0a0'
        );

        // Render chart
        const ctx = document.getElementById('crimeTypeChart').getContext('2d');
        if (crimeTypeChart) crimeTypeChart.destroy();
        
        crimeTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: backgroundColors,
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${ctx.raw} (${Math.round(ctx.raw/data.length*100)}%)`
                        }
                    }
                }
            }
        });

        // Create custom legend
        const legend = document.getElementById('crimeTypeLegend');
        legend.innerHTML = labels.map((label, i) => `
            <div class="legend-item">
                <span class="legend-color" style="background:${backgroundColors[i]}"></span>
                <span class="legend-label">${label}</span>
            </div>
        `).join('');
    }

    function updateTopCrimesList(data) {
        const crimeCounts = {};
        data.forEach(c => {
            crimeCounts[c.primary_type] = (crimeCounts[c.primary_type] || 0) + 1;
        });
        
        // Sort by count and take top 3
        const topCrimes = Object.entries(crimeCounts)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 3);
        
        const totalCrimes = data.length;
        const topCrimesList = document.getElementById('topCrimesList');
        topCrimesList.innerHTML = '';
        
        topCrimes.forEach(([crimeType, count]) => {
            const percent = Math.round((count / totalCrimes) * 100);
            const icon = getCrimeIcon(crimeType);
            const color = crimeType.includes('ASSAULT') || crimeType.includes('BATTERY') ? 'bg-danger' : 
                         crimeType.includes('THEFT') ? 'bg-primary' : 'bg-warning';
            
            topCrimesList.innerHTML += `
                <div class="top-crime-item">
                    <div class="top-crime-icon ${color}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="top-crime-text">${crimeType}</div>
                    <div class="top-crime-percent">${percent}%</div>
                </div>
            `;
        });
    }

    function addCrimeToMap(map, crime) {
        const severity = getCrimeSeverity(crime.primary_type);
        const icon = L.divIcon({
            className: `crime-marker severity-${severity}`,
            html: `<i class="fas ${getCrimeIcon(crime.primary_type)}"></i>`,
            iconSize: [24, 24]
        });
        
        return L.marker([crime.latitude, crime.longitude], { icon })
            .bindPopup(`
                <div class="crime-popup">
                    <h5>${crime.primary_type}</h5>
                    <p>${crime.description}</p>
                    <div class="crime-meta">
                        <span>${new Date(crime.date).toLocaleString()}</span>
                        <span>${crime.block}</span>
                    </div>
                </div>
            `);
    }

    function updateRecentAlerts(data) {
        const alertContainer = document.getElementById('recentAlerts');
        alertContainer.innerHTML = '';
        
        // Sort by date (newest first) and take top 3
        const recentCrimes = [...data]
            .sort((a, b) => new Date(b.date) - new Date(a.date))
            .slice(0, 3);
        
        recentCrimes.forEach(crime => {
            const icon = getCrimeIcon(crime.primary_type);
            const color = crime.primary_type.includes('ASSAULT') || crime.primary_type.includes('BATTERY') ? 'text-danger' : 
                         crime.primary_type.includes('THEFT') ? 'text-primary' : 'text-warning';
            
            // Calculate time ago
            const crimeDate = new Date(crime.date);
            const now = new Date();
            const diffDays = Math.floor((now - crimeDate) / (1000 * 60 * 60 * 24));
            const timeAgo = diffDays === 0 ? 'Today' : 
                            diffDays === 1 ? 'Yesterday' : 
                            `${diffDays} days ago`;
            
            alertContainer.innerHTML += `
                <div class="compact-alert">
                    <i class="fas ${icon} ${color}"></i>
                    <div>
                        <strong>${crime.primary_type}</strong>
                        <small>${crime.block} • ${timeAgo}</small>
                    </div>
                </div>
            `;
        });
    }

    function toggleMapSize() {
        const mapContainer = document.getElementById('crimeMap');
        const expandBtn = document.getElementById('expandMapBtn');
        
        if (isMapExpanded) {
            mapContainer.classList.remove('map-fullscreen');
            expandBtn.innerHTML = '<i class="fas fa-expand"></i> Expand';
        } else {
            mapContainer.classList.add('map-fullscreen');
            expandBtn.innerHTML = '<i class="fas fa-compress"></i> Minimize';
            map.invalidateSize(); // Refresh map after resize
        }
        
        isMapExpanded = !isMapExpanded;
    }

    function loadCrimeData(lat, lon) {
        fetch(`fetch_crime_data.php?lat=${lat}&lon=${lon}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }

                // Render Crime by Hour chart
                renderCrimeHourChart(data);
                
                // Render Crime Type Distribution chart
                renderCrimeTypeChart(data);
                
                // Update Top 3 Crimes list
                updateTopCrimesList(data);
                
                // Update Recent Alerts
                updateRecentAlerts(data);
                
                // Clear existing markers
                markers.forEach(m => map.removeLayer(m));
                markers = [];
                
                // Add new markers (limit to 100 for performance)
                data.slice(0, 100).forEach(c => {
                    if (c.latitude && c.longitude) {
                        const marker = addCrimeToMap(map, c);
                        marker.addTo(map);
                        markers.push(marker);
                    }
                });
                
                // Update heatmap
                updateHeatmap(data);
            })
            .catch(error => console.error('Error loading crime data:', error));
    }

    function updateHeatmap(data) {
        const heatData = data.map(crime => [crime.latitude, crime.longitude, 0.5]);
        
        if (heatmapLayer) {
            map.removeLayer(heatmapLayer);
        }
        
        heatmapLayer = L.heatLayer(heatData, {
            radius: 25,
            blur: 15,
            maxZoom: 17,
            gradient: {0.4: 'blue', 0.6: 'lime', 1: 'red'}
        }).addTo(map);
    }

    // Location pill click handler
    document.querySelectorAll('.location-pill:not(#add-location)').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.location-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            const lat = this.dataset.lat;
            const lon = this.dataset.lon;
            const labelType = this.querySelector('i').className.split(' ')[1];

            if (centerMarker) map.removeLayer(centerMarker);
            if (centerCircle) map.removeLayer(centerCircle);

            // Create custom icon based on location type
            let iconClass;
            switch(labelType) {
                case 'fa-home': iconClass = 'fa-home'; break;
                case 'fa-briefcase': iconClass = 'fa-briefcase'; break;
                case 'fa-school': iconClass = 'fa-school'; break;
                default: iconClass = 'fa-map-marker-alt';
            }

            centerMarker = L.marker([lat, lon], {
                icon: L.divIcon({
                    className: 'user-location-marker',
                    html: `<i class="fas ${iconClass}"></i>`,
                    iconSize: [36, 36]
                })
            }).addTo(map);
            
            centerCircle = L.circle([lat, lon], { 
                radius: 1200, 
                color: '#2196F3', 
                fillOpacity: 0.1 
            }).addTo(map);

            map.setView([lat, lon], 14);
            loadCrimeData(lat, lon);
        });
    });

    // Map expand button
    document.getElementById('expandMapBtn').addEventListener('click', toggleMapSize);

    // Load data for first location
    const firstLocation = document.querySelector('.location-pill:not(#add-location)');
    if (firstLocation) {
        firstLocation.click(); // Trigger click to load data
    }
});
</script>

<?php include '../includes/footer.php'; ?>