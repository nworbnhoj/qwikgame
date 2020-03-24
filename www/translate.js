ready(event => {

  for (var element of document.querySelectorAll('.phrase'))  { element.addEventListener('click', showEdit, false); }
  for (var element of document.querySelectorAll('.pending')) { element.addEventListener('click', showEdit, false); }

});


function showEdit(){
  toggle(nextSibling(this, '.edit-phrase'));
}


// returns the next sibling element matching selector, or null otherwise
function nextSibling(element, selector) {
  if (!element) return null;
  var sibling = element.nextElementSibling;
  if (!sibling || !selector || sibling.matches(selector)) return sibling;
  return nextSibling(sibling, selector);
};



// toggle the element visibility
function toggle(element){
    if(window.getComputedStyle(element).display !== 'none') {
        element.style.display = 'none';
        return;
    }
    element.style.display = 'block';
}

