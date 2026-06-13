docReady(event => {
    initPage();
    try {
        const PLACE_SELECT = document.getElementById('id_place');
        PLACE_SELECT.addEventListener('change', changePlace);
        const PLACE_FIELD = PLACE_SELECT.parentElement
        const MAP_ELEMENT = document.getElementById("map");
        const SHOWMAP_RADIO = document.getElementById('id_place_1');
        const SHOWMAP_DIV = SHOWMAP_RADIO.parentElement.parentElement;
        SHOWMAP_DIV.insertAdjacentElement('afterend', MAP_ELEMENT);
        SHOWMAP_RADIO.removeEventListener('click', form_shut);
        MAP_ELEMENT.addEventListener('change', (event) => {
            event.stopPropagation()
        });
    } catch (error) {
        console.log("Warning: failed to relocate map - missing map|form|game");
        return null;
    }
});
winReady(event => {});

function initPage() {
    // document.getElementById('name').focus();
}

function changePlace(event) {
    showMap(event.target.value === 'show-map');
}

function setPlace(mark) {
    const PLACEID = mark.placeid;
    const PLACE_SELECT = document.getElementById('id_place');
    let option = PLACE_SELECT.querySelector("[value='" + PLACEID + "']")
    if (!option) {
        // clone the SHOW_MAP option to accomodate the Map selected place
        const SHOWMAP_RADIO = PLACE_SELECT.querySelector("[value='show-map']")
        const SHOWMAP_DIV = SHOWMAP_RADIO.parentElement.parentElement;
        const SHOWMAP_LABEL = SHOWMAP_DIV.querySelector('label');
        const CLONE = SHOWMAP_DIV.cloneNode(true);
        SHOWMAP_RADIO.id = 'id_place_showmap';
        SHOWMAP_LABEL.setAttribute('for', SHOWMAP_RADIO.id);
        SHOWMAP_DIV.parentElement.insertBefore(CLONE, SHOWMAP_DIV.nextElementSibling);
        CLONE.querySelector('label').lastChild.data = mark.name;
        radio = CLONE.querySelector("input[type='radio']");
        radio.setAttribute('value', PLACEID);
        radio.setAttribute('checked', 'true');
    }
    PLACE_SELECT.value = PLACEID
    PLACE_SELECT.dispatchEvent(new Event('change'));
    showMap(false);
}

function closeMap() {
    const PLACE_SELECT = document.getElementById('id_place');
    PLACE_SELECT.value = 'ANY'
    showMap(false);
}