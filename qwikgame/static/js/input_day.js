// additional logic for hour toggles when an all_day is toggled

if (typeof toggleAllDay == "undefined") {

  function isAllDay(all_day) {
    if ('updatePromise' in all_day){
      return all_day.updatePromise;
    }
    console.warn("isAllDay() called before init");
    return Promise.resolve(false);
  }

  // update an AllDay toggle to be consistent with the Hour checkboxes
  function updateAllDay(all_day) {
    all_day.updatePromise = new Promise((resolve, reject) => {
      try {
        const FIELD = all_day.closest('div.field');
        // uncheck all_day if a single hour is unchecked
        if (FIELD.querySelector("label.hour input[type='checkbox']:not(:checked):not(:disabled)")){
          toggle_uncheck(all_day);
          resolve(false);
        } else {
          toggle_check(all_day);
          resolve(true);
        }
      } catch (e) {
        console.log(e);
      }
    });
    return all_day.updatePromise;
  }

  // update the Hour toggles to be consistent with the AllDay toggle
  function toggleAllDay(event) {
    try {
      const ALL_DAY = event.currentTarget;
      const ALL_DAY_CHECKED = toggle_checked(ALL_DAY);
      const ON_DAY = ALL_DAY.closest('div.on_day');
      const BY_HOUR = ON_DAY.querySelector('div.by_hour');
      BY_HOUR.querySelectorAll("input[type='checkbox']:not(:disabled)").forEach((hour) => {
        hour.checked = ALL_DAY_CHECKED;
      });
      ALL_DAY.updatePromise = Promise.resolve(ALL_DAY_CHECKED);
    } catch (e) {
      console.log(e);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    // initialize all label.toggle.all_day
    document.querySelectorAll('label.toggle.all_day').forEach((all_day) => {
      updateAllDay(all_day)
    });
    // setup event listeners to keep Hours & Days in sync
    document.querySelectorAll('label.toggle.all_day').forEach((all_day) => {
      // propagate a click on an AllDay toggle DOWN to the Hour toggles
      all_day.addEventListener("click", toggleAllDay);
      const ON_DAY = all_day.closest('.on_day');
      // propagate a click on an Hour toggle UP to the AllDay toggle
      ON_DAY.querySelectorAll('label.toggle.hour').forEach((hour) => {
        hour.addEventListener("click", () => { updateAllDay(all_day); });
      });
    });
  });
}