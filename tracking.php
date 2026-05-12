<?php
require_once 'includes/auth.php';

$devicesWithLocation = $pdo->query(
    'SELECT dl.device_id, d.device_name, d.serial_number, d.imei, d.status,
            dl.latitude, dl.longitude, dl.accuracy, dl.battery_level, dl.created_at
     FROM device_locations dl
     INNER JOIN (
         SELECT device_id, MAX(created_at) AS latest
         FROM device_locations
         GROUP BY device_id
     ) latest_loc ON dl.device_id = latest_loc.device_id
                  AND dl.created_at = latest_loc.latest
     LEFT JOIN devices d ON dl.device_id = d.id
     ORDER BY d.device_name'
)->fetchAll();

$allDevices = $pdo->query('SELECT id, device_name, serial_number, imei, status FROM devices ORDER BY device_name')->fetchAll();

$pageTitle = 'Device Tracking';
require_once 'includes/header.php';
?>

<link rel="stylesheet" href="assests/css/leaflet.min.css">

<style>
  #tracking-map { height:460px; border-radius:10px; position:relative; z-index:0; }
  .map-wrap { overflow:visible !important; }
  .location-row:hover { background:#f8fafc; cursor:pointer; }
  .location-row.highlight-row { background:#eff6ff !important; outline:2px solid #4f8ef7; }
  .battery-bar { height:8px; border-radius:4px; background:#e2e8f0; overflow:hidden; }
  .battery-fill { height:100%; border-radius:4px; }
  #imeiSearchInput::placeholder { color:#94a3b8; }
  .search-no-result { display:none; }
  @keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.4; transform:scale(1.4); }
  }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h5 class="mb-1 fw-bold" style="color:#1e293b">
      <i class="bi bi-geo-alt-fill me-2" style="color:#1A2E4A"></i>Device Tracking
    </h5>
    <p class="text-muted mb-0" style="font-size:0.85rem">Live GPS locations reported by managed devices</p>
  </div>
  <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
  </button>
</div>

<!-- IMEI Search bar -->
<div class="data-card mb-4" style="padding:16px 20px">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <div style="flex:1;min-width:220px;position:relative">
      <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none"></i>
      <input id="imeiSearchInput" type="text" class="form-control" placeholder="Search by IMEI, serial number or device name…"
             style="padding-left:36px;border-radius:8px;border:1.5px solid #e2e8f0;font-size:0.88rem">
    </div>
    <button id="btnImeiSearch" class="btn btn-primary d-flex align-items-center gap-2" style="background:#1A2E4A;border:none;min-width:110px">
      <i class="bi bi-geo-alt-fill"></i> Track
    </button>
    <button id="btnClearSearch" class="btn btn-outline-secondary d-flex align-items-center gap-2" style="min-width:80px">
      <i class="bi bi-x-lg"></i> Clear
    </button>
  </div>
  <div id="searchResult" class="mt-2" style="font-size:0.83rem"></div>
</div>

<div class="data-card mb-4">
  <div class="card-header">
    <h6><i class="bi bi-map me-1"></i> Live Map</h6>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary" id="locatedCount"><?php echo count($devicesWithLocation); ?> device(s) located</span>
      <span id="liveIndicator" style="display:flex;align-items:center;gap:5px;font-size:0.78rem;color:#16a34a">
        <span id="liveDot" style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.5s infinite"></span>
        Live · <span id="refreshCountdown">30s</span>
      </span>
    </div>
  </div>
  <div class="p-3 map-wrap">
    <div id="tracking-map"></div>
    <p class="text-muted mt-2 mb-0" style="font-size:0.78rem">
      <i class="bi bi-info-circle me-1"></i>Shows the most recent GPS fix per device. Click a marker for details.
    </p>
  </div>
</div>

<div class="data-card">
  <div class="card-header">
    <h6><i class="bi bi-list-ul me-1"></i> Device Locations</h6>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr><th>Device</th><th>IMEI</th><th>Status</th><th>Latitude</th><th>Longitude</th><th>Accuracy</th><th>Battery</th><th>Last Update</th></tr>
      </thead>
      <tbody>
        <?php foreach ($allDevices as $dev):
          $loc = null;
          foreach ($devicesWithLocation as $l) {
            if ((int)$l['device_id'] === (int)$dev['id']) { $loc = $l; break; }
          }
        ?>
        <tr class="location-row"
            data-device-id="<?php echo (int)$dev['id']; ?>"
            data-name="<?php echo strtolower(htmlspecialchars($dev['device_name'])); ?>"
            data-serial="<?php echo strtolower(htmlspecialchars($dev['serial_number'])); ?>"
            data-imei="<?php echo strtolower(htmlspecialchars($dev['imei'] ?? '')); ?>"
            <?php if ($loc): ?>onclick="flyTo(<?php echo (float)$loc['latitude']; ?>,<?php echo (float)$loc['longitude']; ?>, this)"<?php endif; ?>>
          <td>
            <div class="fw-semibold" style="font-size:0.9rem"><?php echo htmlspecialchars($dev['device_name']); ?></div>
            <code style="font-size:0.75rem;color:#64748b"><?php echo htmlspecialchars($dev['serial_number']); ?></code>
          </td>
          <td><code style="font-size:0.76rem;color:#475569"><?php echo !empty($dev['imei']) ? htmlspecialchars($dev['imei']) : '<span class="text-muted">—</span>'; ?></code></td>
          <td>
            <span class="badge-status badge-<?php echo $dev['status']; ?>">
              <i class="bi bi-circle-fill" style="font-size:0.4rem"></i>
              <?php echo htmlspecialchars($dev['status']); ?>
            </span>
          </td>
          <?php if ($loc): ?>
          <td><?php echo number_format((float)$loc['latitude'], 6); ?></td>
          <td><?php echo number_format((float)$loc['longitude'], 6); ?></td>
          <td><?php echo $loc['accuracy'] !== null ? round((float)$loc['accuracy']).' m' : '—'; ?></td>
          <td>
            <?php if ($loc['battery_level'] !== null):
              $bat = (int)$loc['battery_level'];
              $bc  = $bat > 50 ? '#22c55e' : ($bat > 20 ? '#f59e0b' : '#ef4444');
            ?>
            <div class="d-flex align-items-center gap-2">
              <div class="battery-bar" style="width:60px">
                <div class="battery-fill" style="width:<?php echo $bat; ?>%;background:<?php echo $bc; ?>"></div>
              </div>
              <span style="font-size:0.8rem;color:#64748b"><?php echo $bat; ?>%</span>
            </div>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-muted" style="font-size:0.82rem"><?php echo htmlspecialchars($loc['created_at']); ?></td>
          <?php else: ?>
          <td colspan="5" class="text-muted" style="font-size:0.85rem"><i class="bi bi-geo-alt text-secondary me-1"></i>No location data yet</td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr id="noSearchResult" style="display:none">
          <td colspan="8" class="text-center text-muted py-3">
            <i class="bi bi-search me-2"></i>No device matched that IMEI / serial / name.
          </td>
        </tr>
        <?php if (empty($allDevices)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No devices registered.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="assests/js/leaflet.min.js"></script>
<script>
const deviceData = <?php echo json_encode(array_values($devicesWithLocation)); ?>;
let map = L.map('tracking-map').setView([9.082, 8.6753], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    maxZoom:19, attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

// liveMarkers[deviceId] = {marker, status, lat, lng, name, imei, serial}
const liveMarkers = {};
// markerList still used by search (kept for compat)
const markerList = [];

function mkIcon(status, highlighted){
  const c = highlighted ? '#1A2E4A' : (status==='online'?'#22c55e':'#94a3b8');
  const ring = highlighted ? '<circle cx="16" cy="16" r="11" fill="none" stroke="#4f8ef7" stroke-width="3"/>' : '';
  const s=`<svg xmlns="http://www.w3.org/2000/svg" width="32" height="40" viewBox="0 0 32 40"><path d="M16 0C7.163 0 0 7.163 0 16c0 11 16 24 16 24S32 27 32 16C32 7.163 24.837 0 16 0z" fill="${c}" stroke="#fff" stroke-width="2"/>${ring}<circle cx="16" cy="16" r="7" fill="#fff"/></svg>`;
  return L.divIcon({html:s,className:'',iconSize:[32,40],iconAnchor:[16,40],popupAnchor:[0,-40]});
}

function buildPopup(d){
  const lat=parseFloat(d.latitude), lng=parseFloat(d.longitude);
  return `<b>${d.device_name||'Unknown'}</b><hr style="margin:4px 0">
    <small>📡 IMEI: ${d.imei||'N/A'}</small><br>
    <small>📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}</small><br>
    <small>🎯 ${d.accuracy?Math.round(d.accuracy)+' m':'N/A'}</small><br>
    ${d.battery_level!=null?'<small>🔋 '+d.battery_level+'%</small><br>':''}
    <small>🕐 ${d.created_at}</small>`;
}

deviceData.forEach(function(d){
  const lat=parseFloat(d.latitude), lng=parseFloat(d.longitude);
  if(isNaN(lat)||isNaN(lng)) return;
  const m=L.marker([lat,lng],{icon:mkIcon(d.status,false)}).addTo(map).bindPopup(buildPopup(d));
  const entry = {marker:m, deviceId:parseInt(d.device_id),
                 imei:(d.imei||'').toLowerCase(), serial:(d.serial_number||'').toLowerCase(),
                 name:(d.device_name||'').toLowerCase(), lat, lng, status:d.status};
  liveMarkers[d.device_id] = entry;
  markerList.push(entry);
});
if(markerList.length>0) map.fitBounds(L.featureGroup(markerList.map(x=>x.marker)).getBounds().pad(0.2));

// Force re-render after page layout settles (fixes overflow:hidden sizing glitch)
setTimeout(function(){ map.invalidateSize(); }, 200);

function flyTo(lat,lng,rowEl){
  map.flyTo([lat,lng],15,{duration:1.2});
  if(rowEl){
    document.querySelectorAll('.location-row.highlight-row').forEach(r=>r.classList.remove('highlight-row'));
    rowEl.classList.add('highlight-row');
  }
}

// ── Live auto-refresh ─────────────────────────────────────────────────────────
let refreshSecs = 30;
const countdownEl = document.getElementById('refreshCountdown');
const locatedCountEl = document.getElementById('locatedCount');
const liveDotEl = document.getElementById('liveDot');

function tickCountdown(){
  refreshSecs--;
  if(refreshSecs <= 0){ refreshSecs = 30; refreshMap(); }
  if(countdownEl) countdownEl.textContent = refreshSecs + 's';
}
setInterval(tickCountdown, 1000);

function refreshMap(){
  if(liveDotEl) liveDotEl.style.background = '#f59e0b'; // amber while loading
  fetch('api/location.php')
    .then(r => r.json())
    .then(data => {
      if(!data.success || !data.devices) return;
      let located = 0;
      data.devices.forEach(function(d){
        const lat = parseFloat(d.latitude), lng = parseFloat(d.longitude);
        if(isNaN(lat)||isNaN(lng)) return;
        located++;
        const popup = buildPopup(d);
        const devId = parseInt(d.device_id);

        if(liveMarkers[devId]){
          // Move existing marker if position changed
          const prev = liveMarkers[devId];
          if(Math.abs(prev.lat - lat) > 0.000001 || Math.abs(prev.lng - lng) > 0.000001){
            prev.marker.setLatLng([lat, lng]);
            prev.lat = lat; prev.lng = lng;
          }
          prev.marker.setPopupContent(popup);
          prev.status = d.status;
          prev.marker.setIcon(mkIcon(d.status, false));
        } else {
          // Brand-new device appeared on map
          const m = L.marker([lat,lng],{icon:mkIcon(d.status,false)}).addTo(map).bindPopup(popup);
          const entry = {marker:m, deviceId:devId,
                         imei:(d.imei||'').toLowerCase(), serial:(d.serial_number||'').toLowerCase(),
                         name:(d.device_name||'').toLowerCase(), lat, lng, status:d.status};
          liveMarkers[devId] = entry;
          markerList.push(entry);
        }

        // Update table row in-place
        const row = document.querySelector('.location-row[data-device-id="'+devId+'"]');
        if(row){
          // Re-attach flyTo with updated coords
          row.onclick = function(){ flyTo(lat, lng, row); };
          const cells = row.querySelectorAll('td');
          // cells[3]=lat, [4]=lng, [5]=acc, [6]=battery, [7]=timestamp
          if(cells.length >= 8){
            cells[3].textContent = lat.toFixed(6);
            cells[4].textContent = lng.toFixed(6);
            cells[5].textContent = d.accuracy ? Math.round(d.accuracy)+' m' : '—';
            if(d.battery_level != null){
              const bat = parseInt(d.battery_level);
              const bc = bat>50?'#22c55e':(bat>20?'#f59e0b':'#ef4444');
              cells[6].innerHTML = `<div class="d-flex align-items-center gap-2">
                <div class="battery-bar" style="width:60px"><div class="battery-fill" style="width:${bat}%;background:${bc}"></div></div>
                <span style="font-size:0.8rem;color:#64748b">${bat}%</span></div>`;
            }
            cells[7].textContent = d.created_at;
          }
        }
      });
      if(locatedCountEl) locatedCountEl.textContent = located + ' device(s) located';
      if(liveDotEl) liveDotEl.style.background = '#22c55e'; // back to green
    })
    .catch(function(){ if(liveDotEl) liveDotEl.style.background = '#ef4444'; });
}

// ── IMEI / name / serial search ──────────────────────────────────────────────
function doSearch(){
  const q = document.getElementById('imeiSearchInput').value.trim().toLowerCase();
  const resultEl = document.getElementById('searchResult');
  const noResult = document.getElementById('noSearchResult');
  const rows = document.querySelectorAll('.location-row');

  if(!q){
    rows.forEach(r=>{ r.style.display=''; r.classList.remove('highlight-row'); });
    noResult.style.display='none';
    resultEl.innerHTML='';
    markerList.forEach(x=>x.marker.setIcon(mkIcon(x.status,false)));
    if(markerList.length>0) map.fitBounds(L.featureGroup(markerList.map(x=>x.marker)).getBounds().pad(0.2));
    return;
  }

  let found=[];
  rows.forEach(r=>{
    const matchName   = r.dataset.name   && r.dataset.name.includes(q);
    const matchSerial = r.dataset.serial && r.dataset.serial.includes(q);
    const matchImei   = r.dataset.imei   && r.dataset.imei.includes(q);
    if(matchName||matchSerial||matchImei){
      r.style.display='';
      r.classList.add('highlight-row');
      found.push(r);
    } else {
      r.style.display='none';
      r.classList.remove('highlight-row');
    }
  });

  markerList.forEach(x=>x.marker.setIcon(mkIcon(x.status,false)));

  if(found.length===0){
    noResult.style.display='';
    resultEl.innerHTML='<span style="color:#ef4444"><i class="bi bi-exclamation-circle me-1"></i>No device found for <strong>"'+q+'"</strong></span>';
    return;
  }
  noResult.style.display='none';
  resultEl.innerHTML='<span style="color:#16a34a"><i class="bi bi-check-circle me-1"></i>Found <strong>'+found.length+'</strong> device(s)</span>';

  const matchedMarkers=[];
  found.forEach(row=>{
    const imei=row.dataset.imei, serial=row.dataset.serial, name=row.dataset.name;
    markerList.forEach(x=>{
      if(x.imei===imei||x.serial===serial||x.name===name){
        x.marker.setIcon(mkIcon(x.status,true));
        x.marker.openPopup();
        matchedMarkers.push(x.marker);
      }
    });
  });

  if(matchedMarkers.length===1){
    const ll=matchedMarkers[0].getLatLng();
    map.flyTo(ll,15,{duration:1.2});
  } else if(matchedMarkers.length>1){
    map.fitBounds(L.featureGroup(matchedMarkers).getBounds().pad(0.3),{maxZoom:14});
  } else {
    resultEl.innerHTML+=' <span class="text-muted ms-2">(no GPS fix available)</span>';
  }
}

document.getElementById('btnImeiSearch').addEventListener('click', doSearch);
document.getElementById('imeiSearchInput').addEventListener('keydown', function(e){
  if(e.key==='Enter') doSearch();
});
document.getElementById('btnClearSearch').addEventListener('click', function(){
  document.getElementById('imeiSearchInput').value='';
  doSearch();
});
</script>
<?php require_once 'includes/footer.php'; ?>
