/******************************************************************************
 * Provides functionality to fetch and display Venue Marks on a google Map.
 * A map idle event triggers a process to show a limited number of Markers
 * on a Google Map. Meta Markers for Country are shown preferentially, or
 * replaced by Admin1 markers when available. Likewise, meta Markers are shown
 * for Admin1 or replaced by Locality Markers. And likewise, meta Markers are
 * shown for Locality or replaced by actual Venue Markers. The raw Marks
 * (data underlying Markers) are fetched from the server on demand. A click
 * on a Marker will show a Map InfoWindow with further actions.
 *****************************************************************************/
const QWIK_MARKS = new Map();
const QWIK_REGION = new Map();                  // regionKey:Set(subKeys)
const SEARCH_MARKERS = [];
const VENUE_ICON = "https://www.qwikgame.org/img/qwik.pin.21x35.png";
const REGION_ICON = "https://www.qwikgame.org/img/qwik.cluster.24x24.png";
const PLACE_ICON = "https://www.qwikgame.org/img/qwik.place.24x24.png";
const MAP_OPTIONS = {
  zoom: 10,
  mapTypeID: 'ROADMAP',
  mapTypeControl: false,
  streetViewControl: false,
  zoomControl: true
};

var qwikMap;
var qwikInfowindow;
    

function venuesMap() {
    const MAP_ELEMENT = document.getElementById('map');
    const LAT = parseFloat(document.getElementById('lat').value);
    const LNG = parseFloat(document.getElementById('lng').value);
    const CENTER = (!isNaN(LAT) && !isNaN(LNG)) ? {lat: LAT, lng: LNG} : MSqC;
    qwikMap = new google.maps.Map(MAP_ELEMENT, MAP_OPTIONS);
    qwikInfowindow = new google.maps.InfoWindow({content: "<div></div>"});
    const MAP = qwikMap;
    const INFOWINDOW = qwikInfowindow;
    MAP.setCenter(CENTER);
    
    const GAME_SELECT = document.getElementById('game');
    const GAME = GAME_SELECT.value;
    GAME_SELECT.addEventListener('change', resetMap);

    // setup Places search box in map
    const INPUT = document.getElementById("map-search");
    const SEARCHBOX = new google.maps.places.SearchBox(INPUT);
    MAP.controls[google.maps.ControlPosition.TOP_LEFT].push(INPUT);
    MAP.addListener("bounds_changed", () => {
        SEARCHBOX.setBounds(MAP.getBounds());
    });
    SEARCHBOX.addListener("places_changed", () => {
        searchChangeHandler(SEARCHBOX.getPlaces());
    });
    
    MAP.addListener('idle', () => {
        mapIdleHandler(GAME)
    });
    MAP.addListener('click', (event) => {
        clickHandler(event)
    });    
    MAP.addListener('bounds_changed', () => {
        fetchVisible(GAME);
    });    
    MAP.addListener('zoom_changed', () => {
        INFOWINDOW.close();
    });
}


// https://stackoverflow.com/questions/4793604/how-to-insert-an-element-after-another-element-in-javascript-without-using-a-lib
function showMapBelowForm(element){
  if(element){
    const MAP_ELEMENT = document.getElementById("map");
    const FORM = element.closest("form");
    FORM.parentElement.insertBefore(MAP_ELEMENT, FORM.nextSibling);
    MAP_ELEMENT.style.display = "block";
    return MAP_ELEMENT;
  }
}


function mapIdleHandler(game){
  const MAP = qwikMap;
  const CENTER = MAP.getCenter();
  const LAT = Number((CENTER.lat()).toFixed(3));
  const LNG = Number((CENTER.lng()).toFixed(3));
  const AVOIDABLE = getAvoidable();
  fetchMarks(game, LAT, LNG, null, AVOIDABLE);
  showMarks(game);
//  preFetch(AVOIDABLE, game);
}


function searchChangeHandler(places){
  const MAP = qwikMap;
  if (places.length == 0) { return; }
  
  SEARCH_MARKERS.forEach(marker => {
    marker.setMap(null);
  });
  SEARCH_MARKERS.length = 0;
  
  // For each place, get the icon, name and location.
  const BOUNDS = gBounds();
  places.forEach(place => {
    if (!place.geometry) { return; }
    
    const MARKER = new google.maps.Marker({
      map: MAP,
      icon: PLACE_ICON,
      position: place.geometry.location
    });
    
    SEARCH_MARKERS.push(MARKER);
        
    google.maps.event.addListener(MARKER, 'click', () => {
      clickSearchMarker(place);
    });    

    if (place.geometry.viewport) { // Only geocodes have viewport.
      BOUNDS.union(place.geometry.viewport);
    } else {
      BOUNDS.extend(place.geometry.location);
    }
  });
  MAP.fitBounds(BOUNDS);
}


function clickHandler(event){
  if (event.placeId) {
    clickPOI(event.placeId, event.latLng);
    event.stop();
  }
}


function resetMap(){
    QWIK_MARKS.clear();
    let venueSelect = document.getElementById('venue-select');
    let options = venueSelect.querySelectorAll(':not([id])');
    for(let option of options){
        option.parentNode.removeChild(option);
    }
    document.getElementById('venue-prompt').selected=true;
    venuesMap();
}


function clickRegionMarker(mark){
  const MAP = qwikMap;
  const INFOWINDOW = qwikInfowindow;
  MAP.panTo(mark.marker.getPosition());
  const TEMPLATE = document.getElementById("infowindow-region");
  const FRAG = TEMPLATE.content.cloneNode(true);

  const SPAN_NAME = FRAG.getElementById("map-mark-region-name");
  SPAN_NAME.textContent = mark.name;

  const SPAN_NUM = FRAG.getElementById("map-mark-region-venues");
  SPAN_NUM.textContent = mark.num + '';       // + '' is a string conversion
  
  INFOWINDOW.setOptions({
    content: FRAG.firstElementChild,
    position: mark.center,
    pixelOffset: new google.maps.Size(0,-30)
  });
  INFOWINDOW.open(MAP);
}


function clickVenueMarker(mark){
  const INFOWINDOW = qwikInfowindow;
  const MAP = qwikMap;
  MAP.panTo(mark.marker.getPosition());
  const TEMPLATE = document.getElementById("infowindow-venue");
  const FRAG = TEMPLATE.content.cloneNode(true);

  const LINK = FRAG.getElementById("map-mark-venue-link");
  LINK.textContent = mark.name;
  LINK.href = "";
  LINK.addEventListener('click', (event) => {
    clickMapMarkVenue(event, mark.key);
  });

  const SPAN = FRAG.getElementById("map-mark-venue-players");
  SPAN.textContent = mark.num + '';       // + '' is a string conversion

  INFOWINDOW.setOptions({
    content: FRAG.firstElementChild,
    position: mark.center,
    pixelOffset: new google.maps.Size(0,-30)
  });
  INFOWINDOW.open(MAP);
}



function clickSearchMarker(place){
  const MAP = qwikMap;
  MAP.panTo(place.geometry.location);
  showInfowindowPlace(place);
}


function clickPOI(placeId, latLng){
  const MAP = qwikMap;
  MAP.panTo(latLng);
  showInfowindowPlaceId(placeId);
}


function showInfowindowPlace(place){
  const MAP = qwikMap;
  const INFOWINDOW = qwikInfowindow;
  const TEMPLATE = document.getElementById("infowindow-poi");
  const FRAG = TEMPLATE.content.cloneNode(true);

  FRAG.getElementById("poi-name").textContent = place.name;
  const LINK = FRAG.getElementById("poi-link");
  LINK.setAttribute("placeid", place.place_id);
  LINK.setAttribute("venuename", place.name);
  
  INFOWINDOW.setOptions({
    content: FRAG.firstElementChild,
    position: place.geometry.location,
    pixelOffset: new google.maps.Size(0,-24)
  });
  INFOWINDOW.open(MAP);
}


function showInfowindowPlaceId(placeId){
  const MAP = qwikMap;   
  const PLACE_SERVICES = new google.maps.places.PlacesService(MAP);
  const REQUEST = { placeId: placeId, fields: ['place_id', 'name', 'geometry']};
  PLACE_SERVICES.getDetails(REQUEST, (place, status) => {
    if (status === "OK") {
      showInfowindowPlace(place);
    } else {
      console.log(status);
    }
  });
}


function clickCreateVenue(event){
  event.preventDefault();
  let placeId = event.target.getAttribute("placeid");
  let name = event.target.getAttribute("venuename");
    
  // add a new option to venueSelect and select it
  let venueSelect = document.getElementById('venue-select');
  let option = document.createElement('option');
  option.value = placeId;
  option.text = name;
  venueSelect.add(option);
  venueSelect.value = placeId;

  showMap(false);
}


function clickMapIcon(event){
  const MAP = qwikMap;
  const ELEMENT = event.target;
  const COORDS = gLatLng(ELEMENT.dataset.lat, ELEMENT.dataset.lng);
  const MAP_ELEMENT = showMapBelowForm(event.target);
  google.maps.event.trigger(MAP,'resize');
  MAP.setCenter(COORDS);
  MAP.setZoom(15);
}




/******************************************************************************
 * A String of regions (locality|admin1|country) for which Marks have already
 * been obtained. 
 * @param marks Object 
 * @param suffix String 
 * @return String of regions (locality|admin1|country)
 *****************************************************************************/
function getAvoidable(){
  const AVOID = new Set();
  for (let [key, mark] of QWIK_MARKS){
    let i = key.indexOf("|");
    if (i>0){
      AVOID.add(key.slice(i+1));
    }
  }
  return Array.from(AVOID).join(":");
}


/******************************************************************************
 * Surveys all QWIK_MARKS and fetches missing sub-Marks from inside the Map
 * viewport.
 * @global QWIK_MARKS Map(marker-keys : Marks)
 * @return String Map(marker-keys : Set(sub-marker-keys)) for visible Marks
 *****************************************************************************/
function fetchVisible(game){
  const MAP = qwikMap;
  const BOUNDS = MAP.getBounds();
  for(const [KEY, MARK] of QWIK_MARKS){
    if(!QWIK_REGION.has(KEY)
    && BOUNDS.contains(MARK.center)){
      fetchMarks(game, null, null, KEY, superKey(KEY));  
    }
  }
}


/******************************************************************************
 * A Map of marker-keys to Set(sub-marker-keys) for Markers inside the Map
 * viewport. This is the visible portion of the QWIK_REGION global.
 * @global QWIK_REGION Map(marker-keys : Set(sub-marker-keys))
 * @return String Map(marker-keys : Set(sub-marker-keys)) for visible Marks
 *****************************************************************************/
function visibleRegion(){
  const VISIBLE = new Map();
  const REGION = QWIK_REGION;
  const MAP = qwikMap;
  const BOUNDS = MAP.getBounds();
  for (const [SUPER_KEY, SUB_KEYS] of REGION){
    for (const KEY of SUB_KEYS){
      const MARK = QWIK_MARKS.get(KEY);
      if(BOUNDS.contains(MARK.center)){
        if(!VISIBLE.has(SUPER_KEY)){ VISIBLE.set(SUPER_KEY, new Set()); }
        VISIBLE.get(SUPER_KEY).add(KEY);
      }
    }
  }
  return VISIBLE;
}


/******************************************************************************
 * Shows Markers on the Map representing qwikgame Venues and Venue clusters.
 * The basic idea is to preferentially show the cluster markers that represent
 * the largest number of sub-Markers (and to hide the sub-Markers). And, when
 * the Map is zoomed in to an area represented by a cluster Marker, the cluster
 * Marker is hidden (and the sub-Markers shown) at some suitable point.
 *
 * JSON requests are made to the server to obtain Marker data for the current
 * Map view-port. This data is stored in the global variables QWIK_MARKS 
 * Map(key:mark) and QWIK_REGION Map(key:Set(sub-key)). 
 
 * Key steps in the process include:
 * - a call to visibleRegion() returns the visible portion of the QWIK_REGION
 *   data structure. Each cluster Marker key is mapped to a Set of sub-Marker
 *   keys.
 * - These cluster Markers are sorted by size (the number of sub-Markers).
 *   This list is processed from largest to smallest to show or hide the
 *   each Marker.
 * - The largest cluster Marker is set to be visible. Each of it's
 *   super-Markers and subMarkers are set to be hidden, and removed from the 
 *   sorted list. This step is then repeated with the next largest Marker
 *   (in the reduced list).
 * - An exception to the prior step is made when the physical area
 *   represented by the cluster Marker is larger that of the Map view-port.
 *   In this case the cluster Marker is hidden and removed from the sorted
 *   list - while the subMarkers remain in the sorted list (to be processed
 *   in turn)
 *
 * @global QWIK_MARKS a Map of marker-keys to Marker-data
 * @global QWIK_REGION a Map of marker-keys to a Set of sub-marker-keys
 * @return null
 *****************************************************************************/
function showMarks(game){
  const MAP = qwikMap;
  // Use the diagonal distance across the Map as a proxy of Map area
  const MAP_BOUNDS = MAP.getBounds();
  const NE = MAP_BOUNDS.getNorthEast();
  const SW = MAP_BOUNDS.getSouthWest();
  const MAP_AREA = haversineDistance(NE.lat(), NE.lng(), SW.lat(), SW.lng());
  
  // create a Set of Region Keys, sorted by the number of sub-Regions within  
  const VISIBLE = visibleRegion();                  // a Map of visible regions 
  const KEYS = Array.from(VISIBLE.keys());
  KEYS.sort(function(a,b){
    return QWIK_REGION.get(b).size - QWIK_REGION.get(a).size
  });
  const SORTED = new Set(KEYS);
  
  while (SORTED.size > 0) {
    const KEY = SORTED.keys().next().value;
    SORTED.delete(KEY);
    
    const MARK = QWIK_MARKS.get(KEY);
    const REGION_AREA = haversineDistance(MARK.n, MARK.e, MARK.s, MARK.w);
    if(REGION_AREA > MAP_AREA){             // should the subMarkers be shown? 
      MARK.marker.setVisible(false);
      hideSuperMarkers(KEY, SORTED);
      showSubMarkers(VISIBLE.get(KEY), game);
    } else if(VISIBLE.get(KEY).size === 1){      // is there only 1 subMarker?
      MARK.marker.setVisible(false);
      showSubMarkers(VISIBLE.get(KEY), game);
    } else {         // otherwise show this Marker and hide super & sub Markers
      MARK.marker.setVisible(true);
      hideSuperMarkers(KEY, SORTED);
      hideSubMarkers(KEY, SORTED);
    }
  }
}


/******************************************************************************
 * Shows Markers from the supplied Set of Mark keys.
 * Iterates thru the Marks:
 * - Venue Markers are set visible
 * - Region Marks are ignored other than to fetch the sub-marks if an entry
 *   does not already exist in QWIK_REGION.
 * @param marks
 * @param game
 * @global QWIK_REGION a Map of marker-keys to a Set of sub-marker-keys
 * @return null
 *****************************************************************************/
function showSubMarkers(KEYS, game){
  for(const KEY of KEYS){
    if(!QWIK_REGION.has(KEY)){   // ignore Regions Marks already in QWIK_REGION
      if (KEY.split("|").length === 4){                         // Venue Marker
        const MARK = QWIK_MARKS.get(KEY);
        const MARKER = MARK.marker;
        if(typeof MARKER !== "undefined"){
          MARKER.setVisible(true);
        }
      } else {                                                 // Region Marker
        fetchMarks(game, null, null, KEY, superKey(KEY));
      }
    }
  }
}
  
  
// hide all superMarkers of key and remove each key from keySet
function hideSuperMarkers(key, keySet){
  for(const K of superKeys(key)){
    keySet.delete(K);
    const MARK = QWIK_MARKS.get(K);
    if (MARK){
      MARK.marker.setVisible(false);
    }
  }    
}


// hide all subMarkers of key and remove each key from keySet
function hideSubMarkers(key, keySet){
  if(!QWIK_REGION.has(key)){ return; }
  for (const K of QWIK_REGION.get(key)){
    keySet.delete(K);
    const MARK = QWIK_MARKS.get(K);
    if (MARK){
      MARK.marker.setVisible(false);
    }
    hideSubMarkers(K, keySet);
  }
}
  

function superKeys(key){
  const KEYS = [];
  let k = key.split("|");
  while (k.length > 0){
    k.shift();
    KEYS.push(k.join("|"));
  }
  return KEYS;
}


const META_HUGE      = 1/3;
const META_SMALL     = 1/300;
const META_TINY      = 1/400;
const META_MICRO     = 1/500;
const META_MINISCULE = 1/600;

function preFetch(avoidable, game){
  const MAP = qwikMap;
  const CENTER = MAP.getCenter();
  const BOUNDS = MAP.getBounds();
  const AREA = degArea(BOUNDS);
  const INNER = expand(BOUNDS, CENTER, 0.5);
  const OUTER = expand(BOUNDS, CENTER, 2.0);
  for (let [key, mark] of QWIK_MARKS){
    let isMeta = key.split('|').length < 4;
    if(isMeta && mark){
      let preZoom = INNER.contains(mark.center);
      let prePan  = OUTER.contains(MAP.center) && !BOUNDS.contains(MAP.center);
      let smallMeta = mark.area > META_SMALL * AREA;
      let tinyMeta  = mark.area > META_TINY * AREA;
      if((tinyMeta && preZoom)                  // pre-fetch marks for zoom-in
      || (smallMeta && prePan)){                // pre-fetch marks for pan
        fetchMarks(game, null, null, key, avoidable);
      }
    }
  }
}

  
function metaMarksComparator(marks){
  return function(a,b){
    let ordA = a.split('|').length;
    let ordB = b.split('|').length;
    if (ordA === ordB){
      return marks.get(a).num - marks.get(b).num;    
    } else {
      return ordA - ordB;
    }
  }
}


function markNumComparator(marks){
  return function(a,b){
    let numA = marks.get(a).num;
    let numB = marks.get(b).num;
    return ordA - ordB;
  }
}



/******************************************************************************
*************************** JSON FUNCTIONS ************************************
******************************************************************************/


/******************************************************************************
 * Initiates a JSON call to obtain Map Markers for Venues in the locality
 * indicated by the lat-lng coordinates or region.
 * Load on server and bandwidth can be minimised by providing a string of
 * region keys (locality|admin1:country) for marks already held in QWIK_MARKS
 * and are thus avoidable.
 * A null placeholder is added to QWIK_MARKS here in fetchMarks() and removed
 * in revieveMarks(). Multiple identical calls to fetchMarks() are thus
 * averted during the time taken by the json call and response (see showMarks())
 * @param game      String game to filter venue Markers
 * @param lat       Float latitude
 * @param lng       Float longitude
 * @param region    String [[locality|]admin1|]country
 * @param avoidable String
 * @return 
 *****************************************************************************/
function fetchMarks(game, lat, lng, region, avoidable){
  if(game === null || typeof game === 'undefined'){ return; }
  if(region !== null && region.split("|").length > 3){ return; }
  if(region !== null && avoidable.includes(region)){ return; }
  const PARAMS = region === null ? 
                 {game:game, lat:lat, lng:lng, avoidable:avoidable} :
                 {game:game, region:region, avoidable:avoidable} ;              
  const ESC = encodeURIComponent;
  const QUERY = Object.keys(PARAMS).map(k => ESC(k) + '=' + ESC(PARAMS[k])).join('&');
  const PATH = 'json/venue.marks.php?'+QUERY;
  qwikJSON(PATH, receiveMarks);
  // report to console
  const LOC = region ? region : "lat:"+lat.toFixed(2)+" lng:"+lng.toFixed(2);
  console.log("fetching marks for "+LOC);
    
  if(region !== null
    && !QWIK_REGION.has(region)){
    QWIK_REGION.set(region, new Set()); // a placeholder to prevent duplication
  }
}


/******************************************************************************
 * A callback function to process the JSON response to fetchMarks().
 * A placeholder added to QWIK_MARKS in fetchMarks() is replaced here in
 * receiveMarks().
 * @param json
 * @return null
 *****************************************************************************/
function receiveMarks(json){
  if(typeof json.status === 'undefined' || json.status === null){ return; }
  const COUNTRY = json.country !== null ? json.country : '';
  const ADMIN1 = json.admin1 !== null ? json.admin1+'|' : '';
  const LOCALITY = json.locality !== null ? json.locality+'|' : '';
  switch (json.status){
    case 'OK':
      if(typeof json.game === 'undefined' || json.game === null){ return ; }
      if(typeof json.marks === 'undefined' || json.marks === null){ return; }
      const GAME = json.game;
      const MAP = qwikMap;
      const BOUNDS = MAP.getBounds();
      const NEW_MARKS = endowMarks(new Map(Object.entries(json.marks)));
      for(let [key, mark] of NEW_MARKS){
        const SUPER_KEY = superKey(key);
        if (typeof mark !== 'undefined'){
          QWIK_MARKS.set(key, mark);
          mark.marker.setVisible(true);
          if(BOUNDS.contains(mark.center)){   // if visible fetch subMarks ASAP
            fetchMarks(GAME, null, null, key, SUPER_KEY);
          }
        };
        if(SUPER_KEY.length > 0){
          if(!QWIK_REGION.has(SUPER_KEY)){ QWIK_REGION.set(SUPER_KEY, new Set()); }
          QWIK_REGION.get(SUPER_KEY).add(key);
        }
      }
      console.log("received "+NEW_MARKS.size+" marks for "+LOCALITY+ADMIN1+COUNTRY);
      break;
    default:
  }
}


function superKey(key){
  let i = key.indexOf("|");
  return (i>0) ? key.slice(i+1) : '';
}


/******************************************************************************
 * Endows raw Marks received by JSON withn additions required to organize and 
 * show map Markers.
 * The endowments include:
 * - a LatLng center, a google.maps.Marker, and a google.,aps.InfoWindow
 * - a label for venueMarks
 * - google.maps.Bounds, degree Area and qwik icon for metaMarks
 * @param marks Map of key:Marks to be endowed
 * @return Map of endowed key:Marks
 *****************************************************************************/
function endowMarks(marks){
  if (!marks){ return {}; }
  for (let [key, mark] of marks){
    if (isNaN(mark.lat) || isNaN(mark.lng)){
      console.log("Warning: Received mark without lat-lng ".key);
    } else {
      mark.center = gLatLng(mark.lat, mark.lng);
      mark.marker = markMarker(mark.center);    
      mark.key = key;
      let keys = key.split('|');
      mark.name = keys[0];
      let isVenue = keys.length === 4;
      if(isVenue){
        mark.marker.setIcon(VENUE_ICON);
        google.maps.event.addListener(mark.marker, 'click', () => {
          clickVenueMarker(mark);
        });      
      } else {  // metaMark
        mark.marker.setIcon(REGION_ICON);
        mark.bounds = markBounds(mark);
        mark.area = degArea(mark.bounds);
        google.maps.event.addListener(mark.marker, 'click', () => {
          clickRegionMarker(mark);
        }); 
      }
    }
  }
  return marks;
}


/******************************************************************************
 * Creates a Marker 
 * @param map google.maps.Map to receive the Marker
 * @param position LatLng coordinates for Marker placement on map
 * @return google.maps.Marker Object
 *****************************************************************************/
function markMarker(position){
  const MAP = qwikMap;
  const MARKER_OPTIONS = {position:position, visible:false, map:MAP};
  return new google.maps.Marker(MARKER_OPTIONS);
}



/******************************************************************************
********************* GEO HELPER FUNCTIONS ************************************
******************************************************************************/


function gLatLng(lat, lng){
  return new google.maps.LatLng(Number(lat), Number(lng));
}


function gBounds(sw=null, ne=null){
  return new google.maps.LatLngBounds(sw, ne);
}


function markBounds(mark){
  let ne = gLatLng(mark.n, mark.e);
  let sw = gLatLng(mark.s, mark.w);
  return gBounds(sw, ne);
}


function expand(bounds, center, factor=2.0){
  let ne = bounds.getNorthEast();
  let sw = bounds.getSouthWest();
  let lat = degDiff(ne.lat(), sw.lat()) / 2;
  let lng = degDiff(ne.lng(), sw.lng()) / 2;
  let n = degSum(center.lat(),  lat * factor);
  let e = degSum(center.lng(),  lng * factor);
  let w = degSum(center.lng(), -lng * factor);
  let s = degSum(center.lat(), -lat * factor);
  return gBounds(gLatLng(s, w), gLatLng(n, e));
}


function degSum(degA, degB){
  let sum = degA+degB;
  return sum>180 ? sum-360 : (sum<-180 ? sum+360 : sum);
}


function degDiff(degA, degB, short=true){
  let diff = Math.abs(degA-degB);
  return short ? diff : 360-diff;
}


function degDistance(llA, llB){
  let width = degDiff(llA.lng(), llB.lng());	
  let height = degDiff(llA.lat(), llB.lat());
  return Math.sqrt(width**2 + height**2);
}


function degArea(bounds){
  let ne = bounds.getNorthEast();
  let sw = bounds.getSouthWest();
  let width = degDiff(ne.lng(), sw.lng());	
  let height = degDiff(ne.lat(), sw.lat());
  return width * height;
}


function distanceComparator(center, marks){
  return function(a,b){
    let aMark = marks.get(a);
    let bMark = marks.get(b);
    let aCoords = gLatLng(aMark.lat, aMark.lng);
    let bCoords = gLatLng(bMark.lat, bMark.lng);
    let aDist = degDistance(center, aCoords);
    let bDist = degDistance(center, bCoords);
    return aDist - bDist  // closest first
  }
}


function sizeComparator(marks){
  return function(a,b){
    let aMark = marks.get(a);
    let bMark = marks.get(b);
    return bMark.num - aMark.num;  // largest first 
  }
}


 
/**
 * Calculates the haversine distance between point A, and B.
 * @param google.maps.LatLng latlngA point A
 * @param google.maps.LatLng latlngB point B
 * https://stackoverflow.com/a/48805273/1438864
 */
const haversineDistance = (latA, lngA, latB, lngB) => {
  const toRadian = angle => (Math.PI / 180) * angle;
  const distance = (a, b) => (Math.PI / 180) * (a - b);
  const RADIUS_OF_EARTH_IN_KM = 6371;

  const dLat = distance(latB, latA);
  const dLng = distance(lngB, lngA);

  latA = toRadian(latA);
  latB = toRadian(latB);

  // Haversine Formula
  const a =
    Math.pow(Math.sin(dLat / 2), 2) +
    Math.pow(Math.sin(dLng / 2), 2) * Math.cos(latA) * Math.cos(latB);
  const c = 2 * Math.asin(Math.sqrt(a));

  let finalDistance = RADIUS_OF_EARTH_IN_KM * c;

  return finalDistance;
};


