// additional logic for hour and all_day toggles when an all_week is toggled

if (typeof toggleAllWeek == "undefined") {

  function toggleAllWeek(event) {
    try {
      let button = event.currentTarget;
      var checked = button.firstElementChild.checked;
      let detail = button.closest('div.field');
      detail.querySelectorAll('.all_day').forEach(function(button) {
        button.firstElementChild.checked = checked;
      })
      detail.querySelectorAll('div.hour_grid').forEach(function(radio_block) {
        for (hour of radio_block.children) {
          hour.firstElementChild.checked = checked;
        }
      })
    } catch (e) {
      console.log(e);
    }
  }

  window.addEventListener("load", function() {
    document.querySelectorAll('label.toggle.all_week').forEach(function(all_week) {
      all_week.addEventListener("click", toggleAllWeek);
    });
  });

}