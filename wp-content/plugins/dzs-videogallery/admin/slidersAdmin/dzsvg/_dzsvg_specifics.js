
export function dzsvg_slidersAdmin_specifics_init(){



  var $ = jQuery;

  $('*[data-aux-name="vimeo_source"]').on('change', handle_change);


  $('*[data-aux-name="vimeo_source"]').trigger('change');

  function handle_change(){
    var $t = $(this);
    console.log($t);

    if($t.attr('data-aux-name')=='vimeo_source'){

      var theVal = $t.val();

      if(theVal.indexOf('showcase')>-1){

        $('body').addClass('slidersAdmin--visible-for--dzsvg--sliders-admin--vimeo-api-requires-user-id');
      }else{

        $('body').removeClass('slidersAdmin--visible-for--dzsvg--sliders-admin--vimeo-api-requires-user-id');
      }
    }
  }
}