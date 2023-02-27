const darkSwitch = document.getElementById('darkSwitch');
initTheme();
window.addEventListener('load', () => {
  if (darkSwitch) {
    initTheme();
    darkSwitch.addEventListener('change', () => {
      resetTheme();
    });
  }
});
$(document).ready(function () {
  $('.table-container tr').on('click', function () {
    $('#' + $(this).data('display')).toggle();
  });
  $('#table-log').DataTable({
    "order": [$('#table-log').data('orderingIndex'), 'desc'],
    "stateSave": true,
    "stateSaveCallback": function (settings, data) {
      window.localStorage.setItem("datatable", JSON.stringify(data));
    },
    "stateLoadCallback": function (settings) {
      var data = JSON.parse(window.localStorage.getItem("datatable"));
      if (data) data.start = 0;
      return data;
    }
  });
  $('#delete-log, #clean-log, #delete-all-log').click(function () {
    return confirm('Are you sure?');
  });
});
