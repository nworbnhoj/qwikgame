

// A general DOM document ready function
docReady(event => {
    refreshData();
});


//////////////// EVENT ACTIONS ////////////////////////////

function refreshData(){
  refreshRecords();
  refreshOptions();
  refreshDatalists();
}


function refreshRecords(){
  for (var base of document.querySelectorAll('div.base.json')) {
    if(!activeUndo(base)){
      replicateBase(base);
    }
  }
}


function refreshOptions(){
  for (var select of document.querySelectorAll('select.json')) {
    fillOptions(select);
  }
  for (var optgroup of document.querySelectorAll('optgroup.json')) {
    fillOptions(optgroup);
  }
}


function refreshDatalists(){
  for (var datalist of document.querySelectorAll('datalist')) {
    fillDatalist(datalist);
  }
}




///////////////// JSON functions ///////////////////


function initJSON(parentElement){
    addListeners(parentElement.querySelectorAll('button.help'), 'click', clickButtonHelp);

    // call moreInitJSON() if it has been defined somewhere
    if (typeof moreInitJSON == 'function') { moreInitJSON(parentElement); }
}


function setInnerJSON(json, element){
    element.innerHTML = json;
    initJSON(element);
}


function replaceRecords(json, base){
    if(window.activeUndo(base)){ return; }
    
    var parentNode = base.parentNode;
    if(!parentNode.contains(document.activeElement)){
      while (parentNode.childNodes.length) {
        parentNode.removeChild(parentNode.firstChild);  // removes Listeners
      }
      parentNode.innerHTML = json.trim();
      parentNode.insertBefore(base, parentNode.firstChild);
      initJSON(parentNode);
    }
}


function replicateBase(base){
    var id = base.getAttribute('id');
    if (!id){
        console.log("Failed to replicate base: missing id attribute.");
        return false;
    }
    var baseHtml = base.outerHTML;
    var params = {html:baseHtml};
    var esc = encodeURIComponent;
    var path = 'api/listing/'+id+'/';
    const TOKEN = document.getElementsByName('csrfmiddlewaretoken').item(0).value;
    qwikJSON(path, params, TOKEN, replaceRecords, base);
}


function fillOptions(element){
    var id = element.getAttribute('id');
    if (!id){
        console.log("Failed to fill options: missing id attribute.");
        return false;
    }
    const FORM = element.closest("form");
    const GAME_ELEMENT = FORM.querySelector("[name=game]");
    let PARAM = (GAME_ELEMENT !== null) ? {'game': GAME_ELEMENT.value} : {} ;    
    var path = 'api/options/'+id+'/';
    const TOKEN = document.getElementsByName('csrfmiddlewaretoken').item(0).value;
    qwikJSON(path, PARAM, TOKEN, setInnerJSON, element);
}


function fillDatalist(datalist){
    var id = datalist.getAttribute('id');
    if (!id){
        console.log("Failed to fill datalist: missing id attribute.");
        return false;
    }
    var input = document.querySelector("[list='"+id+"']");
    if (!input){
        console.log("Failed to fill datalist: unable to find input with list=".id);
        return false;
    }
    var selectGame = input.form.querySelector(".select-game");
    if (selectGame){
        var game = selectGame.value;
        PARAM = {"game" : game};
    } else {
        console.log("Failed to fill datalist: unable to find .select-game");        
        PARAM = {};
    }
    var path = "api/options/"+id+"/";
    const TOKEN = document.getElementsByName('csrfmiddlewaretoken').item(0).value;
    qwikJSON(path, PARAM, TOKEN, setInnerJSON, datalist);
}


function qwikJSON(path, params, csrftoken, callback, element){
    var protocol = window.location.protocol;
    var host = window.location.host;
    var url = protocol + "//" + host + "/" + path;
    args = Array.prototype.slice.call(arguments);
    args.splice(1, 1);
    let json = JSON.stringify(params)
    var xhr = new XMLHttpRequest();
    xhr.callback = callback;
    xhr.arguments = args;
    xhr.onload = xhrSuccess;
    xhr.onerror = xhrError;
    xhr.open("POST", url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRFToken', csrftoken);
    xhr.send(JSON.stringify(params));
}


function xhrSuccess() {
  if (this.status == 200) {
    var args = this.arguments;
    var url = args[0].split("?")[0];
    var json = JSON.parse(this.responseText);
    args[0] = !json ? '' : json ;  // replace url with json in args
    this.callback.apply(this, args);
    var length = nFormatter(this.responseText.length, 1);
    console.log("json reply from "+url+" ("+length+")");
  } else {
    console.log("json request failed with status: " + this.status)
  }
}


function xhrError() {
    console.error("failed to get JSON: " . this.statusText);
}

