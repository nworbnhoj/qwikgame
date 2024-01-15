function showDetail() {
  var width_600 = window.matchMedia("only screen and (max-width: 600px)").matches;
  var width_768 = window.matchMedia("only screen and (max-width: 768px)").matches;
  // var width_992 = window.matchMedia("only screen and (max-width: 992px)").matches;
  // var width_1200 = window.matchMedia("only screen and (max-width: 1200px)").matches;
  var list_bar = document.getElementById("list_bar");
  var detail = document.getElementById("detail");
  if (width_600 || width_768) {           // mobile
    list_bar.style.display = "none";
    detail.style.display = "flex";
  } else {                                // desktop
    list_bar.style.display = "flex";
    detail.style.display = "flex";    
  }

  [].forEach.call(document.querySelectorAll('[name=list]'), function(radio){
    document.getElementById(radio.dataset.id).className = radio.checked? '' : 'hidden';
  })
}

window.onload = function() {
  [].forEach.call(document.querySelectorAll('[name=list]'), function(list_radio) {
    list_radio.onclick = showDetail;
  })
}