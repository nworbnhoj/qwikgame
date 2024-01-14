function showDetail() {
  [].forEach.call(document.querySelectorAll('[name=list]'), function(radio){
    document.getElementById(radio.dataset.id).className = radio.checked? '' : 'hidden';
  })
}

window.onload = function() {
  [].forEach.call(document.querySelectorAll('[name=list]'), function(button) {
    button.onclick = showDetail;
  })
}