///////////////// Service Worker functions ///////////////////


// Registering Service Worker
if('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('static/sw.js');
        console.log("serviceWorker registered.");
    });
};


function getServiceWorker(){
    if(!'serviceWorker' in navigator){
        console.log("failed to get navigator.serviceWorker");
        return;
    }
    var worker = navigator.serviceWorker.controller;  
    if(!worker){
        console.log("failed to get navigator.serviceWorker.controller");
        return;
    }
    return worker;
}


function clearCache(key){
    var worker = getServiceWorker();
    if (worker){
        worker.postMessage({'command': 'clearCache', 'key': key});
    }
}