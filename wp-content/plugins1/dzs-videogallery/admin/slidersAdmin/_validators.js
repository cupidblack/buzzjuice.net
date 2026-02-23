

exports.realtimeValidatorsInit = function($){



  // -- custom validators
  $(document).on('keyup', '*[data-aux-name="vimeo_source"]', (e)=>{
    var isValid = true;
    var $t = $(e.currentTarget);
    var val = e.currentTarget.value;

    if(val.indexOf('manage/folder')>-1){
      isValid=false;
    }

    if(isValid){
      $t.removeClass('is-not-valid');
    }else{

      $t.addClass('is-not-valid');
    }
  })

}