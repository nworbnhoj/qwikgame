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
const DUMMY = 'dummy';
const SEARCH_MARKERS = [];
const VENUE_ICON = "https://www.qwikgame.org/img/qwik.pin.30x50.png";
const CLUSTER_ICON = "https://www.qwikgame.org/img/qwik.cluster.24x24.png";
const PLACE_ICON = "https://www.qwikgame.org/img/qwik.place.24x24.png";


function venuesMap() {
    const MAP_ELEMENT = document.getElementById('map');
    const LAT = parseFloat(document.getElementById('lat').value);
    const LNG = parseFloat(document.getElementById('lng').value);
    const CENTER = (!isNaN(LAT) && !isNaN(LNG)) ? {lat: LAT, lng: LNG} : MSqC;
    const MAP_OPTIONS = {
        zoom: 10,
        center: CENTER,
        mapTypeID: 'ROADMAP',
        mapTypeControl: false,
        streetViewControl: false
    };
    const MAP = new google.maps.Map(MAP_ELEMENT, MAP_OPTIONS);
    const INFOWINDOW = new google.maps.InfoWindow({content: "<div></div>"});
    
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
        searchChangeHandler(MAP, SEARCHBOX.getPlaces(), INFOWINDOW);
    });
    
    MAP.addListener('idle', () => {
        mapIdleHandler(MAP, GAME, INFOWINDOW)
    });
    MAP.addListener('click', (event) => {
        clickHandler(event, MAP, INFOWINDOW)
    });
}


function mapIdleHandler(map, game, infowindow){
  const CENTER = map.getCenter();
  const LAT = Number((CENTER.lat()).toFixed(3));
  const LNG = Number((CENTER.lng()).toFixed(3));
  const AVOIDABLE = getAvoidable();
  fetchMarks(game, LAT, LNG, null, AVOIDABLE, map, infowindow);
  showMarks(game, map, infowindow);
//  preFetch(AVOIDABLE, map, game, infowindow);
}


function searchChangeHandler(map, places, infowindow){
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
      map: map,
      icon: PLACE_ICON,
      position: place.geometry.location
    });
    
    SEARCH_MARKERS.push(MARKER);
        
    google.maps.event.addListener(MARKER, 'click', () => {
      showInfowindowPlace(place, infowindow, map);
    });    

    if (place.geometry.viewport) { // Only geocodes have viewport.
      BOUNDS.union(place.geometry.viewport);
    } else {
      BOUNDS.extend(place.geometry.location);
    }
  });
  map.fitBounds(BOUNDS);
}


function markerClickHandler(map, marker, infoWindow){
  map.panTo(marker.getPosition());
  infoWindow.open(map, marker);
}


function clickHandler(event, map, infowindow){
  if (event.placeId) {
    map.panTo(event.latLng);
    infowindow.setPosition(event.latLng);
    showInfowindowPlaceId(event.placeId, infowindow, map);
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


function showInfowindowVenue(mark, map, infowindow){
  infowindow.setOptions({
    content: mark.info,
    position: mark.center,
    pixelOffset: new google.maps.Size(0,-30)
  });
  infowindow.open(map);
}


function showInfowindowPlace(place, infowindow, map){
  const POI = document.getElementById("infowindow-poi").cloneNode(true);
  POI.children['poi-name'].textContent = place.name;
  POI.children['poi-link'].venueName = place.name;
  infowindow.setOptions({
    content: POI,
    position: place.geometry.location,
    pixelOffset: new google.maps.Size(0,-24)
  });
  infowindow.open(map);
}


function showInfowindowPlaceId(placeId, infowindow, map){    
  const PLACE_SERVICES = new google.maps.places.PlacesService(map);
  const REQUEST = { placeId: placeId, fields: ['name', 'geometry']};
  PLACE_SERVICES.getDetails(REQUEST, (place, status) => {
    if (status === "OK") {
      showInfowindowPlace(place, infowindow, map);
    } else {
      console.log(status);
    }
  });
}


function clickCreateVenue(event){
  event.preventDefault();
  let placeId = event.target.placeId;
  let name = event.target.venueName;
    
  // add a new option to venueSelect and select it
  let venueSelect = document.getElementById('venue-select');
  let option = document.createElement('option');
  option.value = placeId;
  option.text = name;
  venueSelect.add(option);
  venueSelect.value = placeId;

  showMap(false);
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
 * Shows a maximum number of Markers in the visible part of a google Map.
 * The following general process is completed until max markers are shown...
 * 1. Visible marks are categorized as country, admin1, locality or venue.
 * 2. Marks are each mapped to their set of sub-marks (parent:set(children))
 * 3. Visible country markers are shown, or replaced by admin1 sub-marks
 * 4. Visible admin1 markers are shown, or replaced by locality sub-marks
 * 5. Visible locality markers are shown, or replaced by venue sub-marks
 * 6. Visible venue markers are shown
 * Also, sub-marks are obtained on demand as required with fetchMarks().
 * @param game String the qwik game to show venue markers for.
 * @param map google.maps.Map object to display the Markers
 * @param max Integer the maximum number of markers to show on map
 * @return null
 *****************************************************************************/
function showMarks(game, map, infowindow, max=30){
  const COUNTRY = 1;
  const ADMIN1 = 2;
  const LOCALITY = 3;
  const VENUE = 4;
  const MARKS = new Map();                           //           key:regionMap
  MARKS.set(COUNTRY, new Map());                     //     1   key:countryMark
  MARKS.set(ADMIN1, new Map());                      //     2    key:admin1Mark
  MARKS.set(LOCALITY, new Map());                    //     3  key:localityMark
  MARKS.set(VENUE, new Map());                       //     4     key:venueMark
  const FAMILY = new Map();                         // parentKey:Set(childKey)
  const BOUNDS = map.getBounds();
  for (let [key, mark] of QWIK_MARKS){              //       survey QWIK_MARKS
    let inView = BOUNDS.contains(mark.center);
    if(inView){                                    // categorize marks in view
      mark.marker.setVisible(false);                    // default NOT visible
      let index = key.split('|').length;
      MARKS.get(index).set(key, mark);
      let parent = parentKey(key);
      if(!FAMILY.has(parent)){ FAMILY.set(parent, new Set()); }
      FAMILY.get(parent).add(key);
    }
  }
  
  let visible = 0;
  for (let level=COUNTRY; level<VENUE; level++){
    const REGION = MARKS.get(level);
    let maxChildren = max - visible - REGION.size;
    for (let [key, mark] of REGION){
      const CHILDREN = FAMILY.get(key);                 // sub-regions of REGION 
      const FETCH_CHILDREN = typeof CHILDREN === 'undefined';
      const VISIBLE_B4 = mark.marker.getVisible();
      if(visible >= max){                                // enough marks already
          break;
      } else if(maxChildren <= 0){                 // enough sub-regions already
        mark.marker.setVisible(true);
      } else if(FETCH_CHILDREN){                 // fetch markers for sub-region
        mark.marker.setVisible(true);
        fetchMarks(game, mark.lat, mark.lng, key, parentKey(key), map, infowindow);
      } else if(CHILDREN.size === 0                // dont show empty sub-region
             || CHILDREN.size > maxChildren){// dont show overcrowded sub region
        mark.marker.setVisible(true);
      } else {                                        // show sub-region markers
        mark.marker.setVisible(false);
        let childKeys = FAMILY.get(key);
        for(let childKey of childKeys){
          QWIK_MARKS.get(childKey).marker.setVisible(true);
        }
        visible += childKeys.size;
        maxChildren -= childKeys.size;
      }
      visible += +mark.marker.getVisible() -VISIBLE_B4;
    }
  }
	
  // Show venue markers up to max
  for (let [key, venue] of MARKS.get(VENUE)){
    if(visible >= max){ break; } 
    if(!venue.marker.getVisible()){
      venue.marker.setVisible(true);
      visible++;
    }
  }
}


function parentKey(key){
  let i = key.indexOf("|");
  return (i>0) ? key.slice(i+1) : '';
}


const META_HUGE      = 1/3;
const META_SMALL     = 1/300;
const META_TINY      = 1/400;
const META_MICRO     = 1/500;
const META_MINISCULE = 1/600;

function preFetch(avoidable, map, game, infowindow){
  const CENTER = map.getCenter();
  const BOUNDS = map.getBounds();
  const AREA = degArea(BOUNDS);
  const INNER = expand(BOUNDS, CENTER, 0.5);
  const OUTER = expand(BOUNDS, CENTER, 2.0);
  for (let [key, mark] of QWIK_MARKS){
    let isMeta = key.split('|').length < 4;
    if(isMeta){
      let preZoom = INNER.contains(mark.center);
      let prePan  = OUTER.contains(map.center) && !BOUNDS.contains(map.center);
      let smallMeta = mark.area > META_SMALL * AREA;
      let tinyMeta  = mark.area > META_TINY * AREA;
      if((tinyMeta && preZoom)                  // pre-fetch marks for zoom-in
      || (smallMeta && prePan)){                // pre-fetch marks for pan
        fetchMarks(game, null, null, key, avoidable, map, infowindow);
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
 * A dummy mark is added to QWIK_MARKS here in fetchMarks() and removed in
 * reveiveMarks(). Multiple identical calls to fetchMarks() are thus averted
 * during the time taken by the json call and response (see showMarks())
 * @param game      String game to filter venue Markers
 * @param lat       Float latitude
 * @param lng       Float longitude
 * @param region    String [[locality|]admin1|]country
 * @param avoidable String
 * @param map       google.maps.Map object to display the Markers
 * @return 
 *****************************************************************************/
function fetchMarks(game, lat, lng, region, avoidable, map, infowindow){
  if(game === null || typeof game === 'undefined'){ return; }
  if(map === null || typeof map === 'undefined'){ return; }
  if(region !== null && avoidable.includes(region)){ return; }
  const PARAMS = region === null ? 
                 {game:game, lat:lat, lng:lng, avoidable:avoidable} :
                 {game:game, region:region, avoidable:avoidable} ;              
  const ESC = encodeURIComponent;
  const QUERY = Object.keys(PARAMS).map(k => ESC(k) + '=' + ESC(PARAMS[k])).join('&');
  const PATH = 'json/venue.marks.php?'+QUERY;
  qwikJSON(PATH, receiveMarks, map, infowindow);
  // report to console
  const LOC = region ? region : "lat:"+lat.toFixed(2)+" lng:"+lng.toFixed(2);
  console.log("fetching marks for "+LOC);
    
  if(region !== null){
    addDummyChildMark(region, lat, lng); // acts has a placeholder to prevent duplication 
  }
}


/******************************************************************************
 * A callback function to process the JSON response to fetchMarks().
 * A dummy mark added to QWIK_MARKS in fetchMarks() is removed here in
 * receiveMarks().
 * @param json 
 * @param map  google.maps.Map object to display the Markers
 * @return null
 *****************************************************************************/
function receiveMarks(json, map, infowindow){
  if(typeof json.status === 'undefined' || json.status === null){ return; }
  const COUNTRY = json.country !== null ? json.country : '';
  const ADMIN1 = json.admin1 !== null ? json.admin1+'|' : '';
  const LOCALITY = json.locality !== null ? json.locality+'|' : '';
  switch (json.status){
    case 'OK':
      if(typeof json.game === 'undefined' || json.game === null){ return ; }
      if(typeof json.marks === 'undefined' || json.marks === null){ return; }
      const GAME = json.game;
      const NEW_MARKS = endowMarks(new Map(Object.entries(json.marks)), map, infowindow);
      for(let [key, mark] of NEW_MARKS){
        addNewMark(key, mark);
      }
      console.log("received "+NEW_MARKS.size+" marks for "+LOCALITY+ADMIN1+COUNTRY);
      QWIK_MARKS.delete(DUMMY+'|'+LOCALITY+ADMIN1+COUNTRY);
      showMarks(GAME, map, infowindow);
      break;
    default:
  }
}



/******************************************************************************
 * Adds a new Mark to QWIK_MARKS, after hiding and disabling any existing
 * key:Mark mapping.
 * @param key String [[locality|]admin1|]country
 * @param mark Map()
 * @return null
 *****************************************************************************/
function addNewMark(key, mark){
  if(QWIK_MARKS.has(key)){
    let oldMark = QWIK_MARKS.get(key);
    oldMark.marker.setVisible(false);
    oldMark.marker.setMap(null);
    oldMark.infoWindow.close();
  }
  QWIK_MARKS.set(key, mark);
}


/******************************************************************************
 * Adds a DUMMY Mark to QWIK_MARKS to act as a placehlder during JSON latency.
 * A dummy mark is added to QWIK_MARKS byt fetchMarks() and removed in
 * receiveMarks(). Multiple identical calls to fetchMarks() are thus averted
 * during the time taken by the json call and response (see showMarks())
 * @param key String [[locality|]admin1|]country
 * @param lat Float latitude
 * @param lng Float longitude
 * @return Map null
 *****************************************************************************/
function addDummyChildMark(key, lat, lng){
  let dummy = {lat:lat, lng:lng}
  dummy.center = gLatLng(lat, lng);
  dummy.marker = new google.maps.Marker();
  dummy.infoWindow = new google.maps.InfoWindow();
  addNewMark(DUMMY+"|"+key, dummy);
}


/******************************************************************************
 * Endows raw Marks received by JSON with additions required to organize and 
 * show map Markers.
 * The endowments include:
 * - a LatLng center, a google.maps.Marker, and a google.,aps.InfoWindow
 * - a label for venueMarks
 * - google.maps.Bounds, degree Area and qwik icon for metaMarks
 * @param marks Map of key:Marks to be endowed
 * @param map google.maps.Map to receive the Marker
 * @return Map of endowed key:Marks
 *****************************************************************************/
function endowMarks(marks, map, infowindow){
  if (!marks || !map ){ return {}; }
  for (let [key, mark] of marks){
    mark.center = gLatLng(mark.lat, mark.lng);
    mark.marker = markMarker(map, mark.center);
    
    google.maps.event.addListener(mark.marker, 'click', () => {
      showInfowindowVenue(mark, map, infowindow);
    });
    
    let isVenue = key.split('|').length === 4;
    if(isVenue){
//      mark.marker.setLabel(mark.num + '');       // + '' is a string conversion
      mark.marker.setIcon(VENUE_ICON);
    } else {  // metaMark
      mark.bounds = markBounds(mark);
      mark.area = degArea(mark.bounds);
      mark.marker.setIcon(CLUSTER_ICON);
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
function markMarker(map, position){
  const MARKER_OPTIONS = {position:position, visible:false, map:map};
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


