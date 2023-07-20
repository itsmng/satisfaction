/**
 *
 * @param root_doc
 * @param id
 */
function plugin_satisfaction_load_defaultvalue(root_doc, default_value){
    var value = $('input[name="default_value"]').val() || Math.floor((default_value/2).toFixed(0));

    if(value > default_value) {
        value = default_value;
    }

    $.ajax({
        url: root_doc+'/ajax/satisfaction.php',
        type: 'POST',
        data: '&action_default_value&default_value='+ default_value + '&value=' + value,
        dataType: 'html',
        success: function (code_html, statut) {
            $('#default_value').html(code_html);
        },

    });
}

/**
 * Launch the interaction implementation of numeric_scale_with_nc
 */
function registreNumericScale() {
  (function(){
    document.querySelectorAll('.numeric_scale_with_nc[required]')
      .forEach(function(question) {
        question.querySelectorAll('input[type="radio"]')
          .forEach(function(answere) {
            answere.addEventListener('change', function() {
              question.setAttribute('data-valid', 'true');
            });
            if(answere.checked)
              question.setAttribute('data-valid', 'true');
          });
    });
  })();
}
window.addEventListener('load', registreNumericScale);

